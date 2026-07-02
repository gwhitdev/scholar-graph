import { Form } from '@inertiajs/react';
import { SearchIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { search, store } from '@/actions/App/Http/Controllers/PaperController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

interface PaperResult {
    openalex_id: string | null;
    title: string;
    abstract: string | null;
    year: number | null;
    authors: string[];
    doi: string | null;
    venue: string | null;
    cited_by_count: number | null;
    referenced_works: string[];
}

interface PaperSearchProps {
    projectId: number;
    savedOpenAlexIds: string[];
}

function buildSearchUrl(projectId: number, query: string): string {
    const url = new URL(search.url(projectId), window.location.origin);
    url.searchParams.set('query', query);
    url.searchParams.set('limit', '10');

    return url.pathname + url.search;
}

export function PaperSearch({ projectId, savedOpenAlexIds }: PaperSearchProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<PaperResult[]>([]);
    const [hasSearched, setHasSearched] = useState(false);
    const [loading, setLoading] = useState(false);
    const abortControllerRef = useRef<AbortController | null>(null);

    const doSearch = useCallback(
        async (searchQuery: string) => {
            // Cancel any in-flight request
            abortControllerRef.current?.abort();

            if (searchQuery.trim().length < 3) {
                setResults([]);
                setHasSearched(false);

                return;
            }

            const controller = new AbortController();
            abortControllerRef.current = controller;
            setLoading(true);

            try {
                const url = buildSearchUrl(projectId, searchQuery);
                const response = await fetch(url, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    setResults([]);
                    setHasSearched(true);

                    return;
                }

                const papers: PaperResult[] = await response.json();
                setResults(Array.isArray(papers) ? papers : []);
                setHasSearched(true);
            } catch (err) {
                if (err instanceof DOMException && err.name === 'AbortError') {
                    return;
                }

                setResults([]);
                setHasSearched(true);
            } finally {
                setLoading(false);
            }
        },
        [projectId],
    );

    useEffect(() => {
        const timeout = setTimeout(() => {
            doSearch(query);
        }, 500);

        return () => {
            clearTimeout(timeout);
            abortControllerRef.current?.abort();
        };
    }, [query, doSearch]);

    return (
        <Card className="flex flex-1 flex-col">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <SearchIcon className="size-4" />
                    Search Papers
                </CardTitle>
                <CardDescription>
                    Find papers via OpenAlex and add them to your project.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col gap-4">
                <div className="relative">
                    <Input
                        type="search"
                        placeholder="Search by title, author, or keyword..."
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        aria-label="Search papers"
                    />
                    {loading && (
                        <span className="absolute top-1/2 right-3 -translate-y-1/2">
                            <Spinner />
                        </span>
                    )}
                </div>

                <div className="flex flex-1 flex-col gap-2 overflow-y-auto">
                    {hasSearched && results.length === 0 && !loading && (
                        <p className="text-sm text-muted-foreground">
                            No papers found.
                        </p>
                    )}

                    {results.map((paper) => {
                        const isAdded = paper.openalex_id
                            ? savedOpenAlexIds.includes(paper.openalex_id)
                            : false;

                        return (
                        <div
                            key={paper.openalex_id ?? paper.title}
                            className="rounded-lg border p-3 transition-colors hover:bg-accent/50"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="min-w-0 flex-1">
                                    <h4 className="text-sm font-medium">
                                        {paper.title}
                                    </h4>
                                    <div className="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                        {paper.year && (
                                            <span>{paper.year}</span>
                                        )}
                                        {paper.venue && (
                                            <span>{paper.venue}</span>
                                        )}
                                        {paper.doi && (
                                            <a
                                                href={`https://doi.org/${paper.doi}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="underline hover:text-foreground"
                                            >
                                                DOI: {paper.doi}
                                            </a>
                                        )}
                                        {paper.cited_by_count !== null && (
                                            <span>
                                                {paper.cited_by_count.toLocaleString()}{' '}
                                                citations
                                            </span>
                                        )}
                                    </div>
                                    {paper.authors.length > 0 && (
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {paper.authors.join(', ')}
                                        </p>
                                    )}
                                    {paper.abstract && (
                                        <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                            {paper.abstract}
                                        </p>
                                    )}
                                </div>
                                <Form
                                    {...store.form(projectId)}
                                    className="shrink-0"
                                    options={{ preserveScroll: true }}
                                >
                                    <input
                                        type="hidden"
                                        name="title"
                                        value={paper.title}
                                    />
                                    <input
                                        type="hidden"
                                        name="abstract"
                                        value={paper.abstract ?? ''}
                                    />
                                    <input
                                        type="hidden"
                                        name="year"
                                        value={paper.year ?? ''}
                                    />
                                    <input
                                        type="hidden"
                                        name="openalex_id"
                                        value={paper.openalex_id ?? ''}
                                    />
                                    <input
                                        type="hidden"
                                        name="doi"
                                        value={paper.doi ?? ''}
                                    />
                                    <input
                                        type="hidden"
                                        name="venue"
                                        value={paper.venue ?? ''}
                                    />
                                    <input
                                        type="hidden"
                                        name="cited_by_count"
                                        value={paper.cited_by_count ?? ''}
                                    />
                                    {paper.authors.map((author, idx) => (
                                        <input
                                            key={idx}
                                            type="hidden"
                                            name={`authors[${idx}]`}
                                            value={author}
                                        />
                                    ))}
                                    {paper.referenced_works.map((work, idx) => (
                                        <input
                                            key={idx}
                                            type="hidden"
                                            name={`referenced_works[${idx}]`}
                                            value={work}
                                        />
                                    ))}
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={isAdded}
                                        aria-label={
                                            isAdded
                                                ? `${paper.title} is already added`
                                                : `Add ${paper.title}`
                                        }
                                    >
                                        {isAdded ? 'Added ✓' : 'Add'}
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    )})}
                </div>
            </CardContent>
        </Card>
    );
}
