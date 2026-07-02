<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenAlexSearchService
{
    /** Cache TTL for search results: 1 hour. */
    protected const SEARCH_TTL = 3600;

    /** Cache TTL for individual work lookups: 24 hours. */
    protected const WORK_TTL = 86400;

    public function __construct(
        protected string $baseUrl,
        protected string $mailto,
    ) {}

    /**
     * Search for works.
     *
     * @return array<int, array{openalex_id: string|null, doi: string|null, title: string, abstract: string|null, year: int|null, authors: list<string>, venue: string|null, cited_by_count: int|null, referenced_works: list<string>}>
     */
    public function search(string $query, int $limit = 10, int $page = 1): array
    {
        $cacheKey = "openalex:search:{$query}:{$limit}:{$page}";

        return Cache::remember($cacheKey, self::SEARCH_TTL, function () use ($query, $limit, $page) {
            $response = $this->request()
                ->get($this->baseUrl.'/works', array_merge([
                    'search' => $query,
                    'per_page' => $limit,
                    'page' => $page,
                ], $this->mailtoParam()));

            $response->throw();

            /** @var array<int, array<string, mixed>> $works */
            $works = $response->json('results', []);

            return collect($works)
                ->map(fn (array $work) => $this->normalizeWork($work))
                ->all();
        });
    }

    /**
     * Fetch a single work by its OpenAlex ID.
     *
     * @return array{openalex_id: string|null, doi: string|null, title: string, abstract: string|null, year: int|null, authors: list<string>, venue: string|null, cited_by_count: int|null, referenced_works: list<string>}
     */
    public function getWork(string $openAlexId): array
    {
        $cacheKey = "openalex:work:{$openAlexId}";

        return Cache::remember($cacheKey, self::WORK_TTL, function () use ($openAlexId) {
            $response = $this->request()
                ->get($this->baseUrl.'/works/'.$openAlexId, $this->mailtoParam());

            $response->throw();

            return $this->normalizeWork($response->json());
        });
    }

    /**
     * Reconstruct a plain-text abstract from an OpenAlex inverted index.
     *
     * The index maps each word to the list of positions it occupies, so the
     * position->word map must be built and sorted before joining.
     *
     * @param  array<string, list<int>>  $invertedIndex
     */
    public function reconstructAbstract(array $invertedIndex): string
    {
        $wordsByPosition = [];

        foreach ($invertedIndex as $word => $positions) {
            foreach ($positions as $position) {
                $wordsByPosition[$position] = $word;
            }
        }

        ksort($wordsByPosition);

        return implode(' ', $wordsByPosition);
    }

    /**
     * Build the base HTTP request, identifying the app for OpenAlex's polite
     * pool via the User-Agent header when a mailto address is configured.
     */
    protected function request(): PendingRequest
    {
        $request = Http::timeout(30);

        if ($this->mailto !== '') {
            $request = $request->withHeaders([
                'User-Agent' => "ScholarGraph (mailto:{$this->mailto})",
            ]);
        }

        return $request;
    }

    /**
     * The mailto query parameter for the polite pool, omitted when unset.
     *
     * @return array<string, string>
     */
    protected function mailtoParam(): array
    {
        return $this->mailto !== '' ? ['mailto' => $this->mailto] : [];
    }

    /**
     * Normalize a raw OpenAlex work response.
     *
     * @param  array<string, mixed>  $work
     * @return array{openalex_id: string|null, doi: string|null, title: string, abstract: string|null, year: int|null, authors: list<string>, venue: string|null, cited_by_count: int|null, referenced_works: list<string>}
     */
    protected function normalizeWork(array $work): array
    {
        /** @var list<string> $authors */
        $authors = collect($work['authorships'] ?? [])
            ->map(fn (array $authorship) => $authorship['author']['display_name'] ?? null)
            ->filter()
            ->values()
            ->all();

        /** @var list<string> $referencedWorks */
        $referencedWorks = collect($work['referenced_works'] ?? [])
            ->map(fn (string $url) => $this->stripOpenAlexPrefix($url))
            ->all();

        $invertedIndex = $work['abstract_inverted_index'] ?? null;

        return [
            'openalex_id' => isset($work['id']) ? $this->stripOpenAlexPrefix($work['id']) : null,
            'doi' => isset($work['doi']) ? str_replace('https://doi.org/', '', $work['doi']) : null,
            'title' => $work['title'] ?? 'Untitled',
            'abstract' => $invertedIndex ? $this->reconstructAbstract($invertedIndex) : null,
            'year' => $work['publication_year'] ?? null,
            'authors' => $authors,
            'venue' => $work['primary_location']['source']['display_name'] ?? null,
            'cited_by_count' => $work['cited_by_count'] ?? null,
            'referenced_works' => $referencedWorks,
        ];
    }

    /**
     * Strip the OpenAlex URL prefix from an entity URL, leaving the bare ID.
     */
    protected function stripOpenAlexPrefix(string $url): string
    {
        return str_replace('https://openalex.org/', '', $url);
    }
}
