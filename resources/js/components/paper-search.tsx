import { Form } from '@inertiajs/react';
import { SearchIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { search, store } from '@/actions/App/Http/Controllers/PaperController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
    semantic_scholar_id: string | null;
    title: string;
    abstract: string | null;
    year: number | null;
}

interface PaperSearchProps {
    projectId: number;
}

function buildSearchUrl(projectId: number, query: string): string {
    const url = new URL(search.url(projectId), window.location.origin);
    url.searchParams.set('query', query);
    url.searchParams.set('limit', '10');

    return url.pathname + url.search;
}

export function PaperSearch({ projectId }: PaperSearchProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<PaperResult[]>([]);
    const [hasSearched, setHasSearched] = useState(false);
    const [rateLimited, setRateLimited] = useState(false);
    const [loading, setLoading] = useState(false);
    const abortControllerRef = useRef<AbortController | null>(null);

    const doSearch = useCallback(
        async (searchQuery: string) => {
            // Cancel any in-flight request
            abortControllerRef.current?.abort();

            if (searchQuery.trim().length < 3) {
                setResults([]);
                setHasSearched(false);
                setRateLimited(false);

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

                if (response.status === 429) {
                    setResults([]);
                    setHasSearched(true);
                    setRateLimited(true);

                    return;
                }

                if (!response.ok) {
                    setResults([]);
                    setHasSearched(true);

                    return;
                }

                const papers: PaperResult[] = await response.json();
                setResults(Array.isArray(papers) ? papers : []);
                setHasSearched(true);
                setRateLimited(false);
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
                    Find papers on Semantic Scholar and add them to your
                    project.
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

                {rateLimited && (
                    <Alert variant="destructive">
                        <AlertTitle>Search limit reached</AlertTitle>
                        <AlertDescription>
                            Semantic Scholar is rate limiting your requests.
                            Please wait a moment and try again.
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-1 flex-col gap-2 overflow-y-auto">
                    {hasSearched && results.length === 0 && !loading && (
                        <p className="text-sm text-muted-foreground">
                            No papers found.
                        </p>
                    )}

                    {results.map((paper) => (
                        <div
                            key={paper.semantic_scholar_id ?? paper.title}
                            className="rounded-lg border p-3 transition-colors hover:bg-accent/50"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="min-w-0 flex-1">
                                    <h4 className="text-sm font-medium">
                                        {paper.title}
                                    </h4>
                                    {paper.year && (
                                        <p className="text-xs text-muted-foreground">
                                            {paper.year}
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
                                        name="semantic_scholar_id"
                                        value={paper.semantic_scholar_id ?? ''}
                                    />
                                    <Button type="submit" size="sm">
                                        Add
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
