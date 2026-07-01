<?php

namespace App\Services;

use App\Exceptions\SemanticScholarRateLimitException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SemanticScholarService
{
    /** Cache TTL for search results: 1 hour. */
    protected const SEARCH_TTL = 3600;

    /** Cache TTL for individual paper lookups: 24 hours. */
    protected const PAPER_TTL = 86400;

    public function __construct(
        protected string $baseUrl,
    ) {}

    /**
     * Search for papers.
     *
     * @return array<int, array{semantic_scholar_id: string|null, title: string, abstract: string|null, year: int|null, authors: list<string>, doi: string|null, venue: string|null, pages: string|null, raw_metadata: array<string, mixed>}>
     *
     * @throws SemanticScholarRateLimitException
     */
    public function search(string $query, int $limit = 10): array
    {
        $cacheKey = "semantic_scholar:search:{$query}:{$limit}";

        return Cache::remember($cacheKey, self::SEARCH_TTL, function () use ($query, $limit) {
            $response = Http::timeout(30)
                ->get($this->baseUrl.'/graph/v1/paper/search', [
                    'query' => $query,
                    'fields' => 'title,abstract,year,authors,externalIds,venue,journal',
                    'limit' => $limit,
                ]);

            if ($response->status() === 429) {
                throw new SemanticScholarRateLimitException('Semantic Scholar rate limit exceeded.');
            }

            $response->throw();

            /** @var array<int, array<string, mixed>> $papers */
            $papers = $response->json('data', []);

            return collect($papers)
                ->map(fn (array $paper) => $this->normalizePaper($paper))
                ->all();
        });
    }

    /**
     * Fetch a single paper by ID.
     *
     * @return array{semantic_scholar_id: string|null, title: string, abstract: string|null, year: int|null, authors: list<string>, doi: string|null, venue: string|null, pages: string|null, raw_metadata: array<string, mixed>}
     *
     * @throws SemanticScholarRateLimitException
     */
    public function fetchPaper(string $semanticScholarId): array
    {
        $cacheKey = "semantic_scholar:paper:{$semanticScholarId}";

        return Cache::remember($cacheKey, self::PAPER_TTL, function () use ($semanticScholarId) {
            $response = Http::timeout(30)
                ->get($this->baseUrl.'/graph/v1/paper/'.$semanticScholarId, [
                    'fields' => 'title,abstract,year,authors,externalIds,venue,journal',
                ]);

            if ($response->status() === 429) {
                throw new SemanticScholarRateLimitException('Semantic Scholar rate limit exceeded.');
            }

            $response->throw();

            $paper = $response->json();

            return $this->normalizePaper($paper);
        });
    }

    /**
     * Normalize a raw Semantic Scholar API paper response.
     *
     * @param  array<string, mixed>  $paper
     * @return array{semantic_scholar_id: string|null, title: string, abstract: string|null, year: int|null, authors: list<string>, doi: string|null, venue: string|null, pages: string|null, raw_metadata: array<string, mixed>}
     */
    protected function normalizePaper(array $paper): array
    {
        /** @var list<string> $authors */
        $authors = collect($paper['authors'] ?? [])
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return [
            'semantic_scholar_id' => $paper['paperId'] ?? null,
            'title' => $paper['title'] ?? 'Untitled',
            'abstract' => $paper['abstract'] ?? null,
            'year' => $paper['year'] ?? null,
            'authors' => $authors,
            'doi' => $paper['externalIds']['DOI'] ?? null,
            'venue' => $paper['venue'] ?? $paper['journal']['name'] ?? null,
            'pages' => $paper['journal']['pages'] ?? null,
            'raw_metadata' => $paper,
        ];
    }
}
