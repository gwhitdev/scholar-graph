<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SemanticScholarService
{
    /** Cache TTL for enrichment and recommendation lookups: 24 hours. */
    protected const ENRICH_TTL = 86400;

    public function __construct(
        protected string $baseUrl,
    ) {}

    /**
     * Fetch enrichment data (TLDR + influential citation count) for a DOI.
     *
     * Returns null on any failure (including rate limits) so callers can
     * retry later. Only successful results are cached; Cache::remember would
     * cache the null and permanently block retries.
     *
     * @return array{tldr: string|null, influential_citation_count: int|null}|null
     */
    public function enrich(string $doi): ?array
    {
        $cacheKey = "semantic_scholar:enrich:{$doi}";

        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(30)
                ->get($this->baseUrl.'/graph/v1/paper/DOI:'.$doi, [
                    'fields' => 'tldr,influentialCitationCount',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $result = [
            'tldr' => $response->json('tldr.text'),
            'influential_citation_count' => $response->json('influentialCitationCount'),
        ];

        Cache::put($cacheKey, $result, self::ENRICH_TTL);

        return $result;
    }

    /**
     * Fetch recommended papers related to a Semantic Scholar paper ID.
     *
     * @return array<int, array{semantic_scholar_id: string|null, title: string, year: int|null, authors: list<string>}>
     */
    public function getRelatedPapers(string $semanticScholarId, int $limit = 5): array
    {
        $cacheKey = "semantic_scholar:related:{$semanticScholarId}:{$limit}";

        return Cache::remember($cacheKey, self::ENRICH_TTL, function () use ($semanticScholarId, $limit) {
            $response = Http::timeout(30)
                ->get($this->baseUrl.'/graph/v1/paper/'.$semanticScholarId.'/recommendations', [
                    'fields' => 'title,year,authors',
                    'limit' => $limit,
                ]);

            $response->throw();

            /** @var array<int, array<string, mixed>> $papers */
            $papers = $response->json('recommendedPapers', []);

            return collect($papers)
                ->map(fn (array $paper): array => [
                    'semantic_scholar_id' => $paper['paperId'] ?? null,
                    'title' => $paper['title'] ?? 'Untitled',
                    'year' => $paper['year'] ?? null,
                    'authors' => collect($paper['authors'] ?? [])
                        ->pluck('name')
                        ->filter()
                        ->values()
                        ->all(),
                ])
                ->all();
        });
    }
}
