<?php

namespace App\Services;

use App\Exceptions\SemanticScholarRateLimitException;
use Illuminate\Support\Facades\Http;

class SemanticScholarService
{
    public function __construct(
        protected string $baseUrl,
    ) {}

    /**
     * Search for papers.
     *
     * @return array<int, array{semantic_scholar_id: string|null, title: string, abstract: string|null, year: int|null, raw_metadata: array<string, mixed>}>
     *
     * @throws SemanticScholarRateLimitException
     */
    public function search(string $query, int $limit = 10): array
    {
        $response = Http::timeout(30)
            ->get($this->baseUrl.'/graph/v1/paper/search', [
                'query' => $query,
                'fields' => 'title,abstract,year,externalIds',
                'limit' => $limit,
            ]);

        if ($response->status() === 429) {
            throw new SemanticScholarRateLimitException('Semantic Scholar rate limit exceeded.');
        }

        $response->throw();

        return collect($response->json('data', []))
            ->map(fn (array $paper) => [
                'semantic_scholar_id' => $paper['paperId'] ?? null,
                'title' => $paper['title'] ?? 'Untitled',
                'abstract' => $paper['abstract'] ?? null,
                'year' => $paper['year'] ?? null,
                'raw_metadata' => $paper,
            ])
            ->all();
    }

    /**
     * Fetch a single paper by ID.
     *
     * @return array{semantic_scholar_id: string|null, title: string, abstract: string|null, year: int|null, raw_metadata: array<string, mixed>}
     *
     * @throws SemanticScholarRateLimitException
     */
    public function fetchPaper(string $semanticScholarId): array
    {
        $response = Http::timeout(30)
            ->get($this->baseUrl.'/graph/v1/paper/'.$semanticScholarId, [
                'fields' => 'title,abstract,year,externalIds',
            ]);

        if ($response->status() === 429) {
            throw new SemanticScholarRateLimitException('Semantic Scholar rate limit exceeded.');
        }

        $response->throw();

        $paper = $response->json();

        return [
            'semantic_scholar_id' => $paper['paperId'] ?? null,
            'title' => $paper['title'] ?? 'Untitled',
            'abstract' => $paper['abstract'] ?? null,
            'year' => $paper['year'] ?? null,
            'raw_metadata' => $paper,
        ];
    }
}
