import { useForm } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { search } from '@/actions/App/Http/Controllers/PaperController';
import { storeByDoi } from '@/actions/App/Http/Controllers/PaperController';
import { store } from '@/actions/App/Http/Controllers/PaperController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface CommandBarProps {
    projectId: number;
    savedOpenAlexIds: string[];
    corpusLabel: string;
    onSearchFocus?: () => void;
}

function buildSearchUrl(projectId: number, query: string): string {
    const url = new URL(search.url(projectId), window.location.origin);
    url.searchParams.set('query', query);
    url.searchParams.set('limit', '10');
    return url.pathname + url.search;
}

export function CommandBar({
    projectId,
    savedOpenAlexIds,
    corpusLabel,
    onSearchFocus,
}: CommandBarProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<PaperResult[]>([]);
    const [hasSearched, setHasSearched] = useState(false);
    const [loading, setLoading] = useState(false);
    const [showResults, setShowResults] = useState(false);
    const [doiDialogOpen, setDoiDialogOpen] = useState(false);
    const abortControllerRef = useRef<AbortController | null>(null);
    const searchInputRef = useRef<HTMLInputElement>(null);

    const doiForm = useForm({ doi: '' });

    const doSearch = useCallback(
        async (searchQuery: string) => {
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

    const handleFocus = () => {
        setShowResults(true);
        onSearchFocus?.();
    };

    const handleBlur = () => {
        // Delay to allow click on results
        setTimeout(() => setShowResults(false), 200);
    };

    const handleAddPaper = (paper: PaperResult) => {
        const formData = {
            title: paper.title,
            abstract: paper.abstract ?? '',
            year: paper.year ?? '',
            openalex_id: paper.openalex_id ?? '',
            doi: paper.doi ?? '',
            venue: paper.venue ?? '',
            cited_by_count: paper.cited_by_count ?? '',
            authors: paper.authors,
            referenced_works: paper.referenced_works,
        };

        // Use Inertia's router to POST the form
        import('@inertiajs/react').then(({ router }) => {
            router.post(store.url(projectId), formData, {
                preserveScroll: true,
            });
        });
    };

    const handleDoiSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        doiForm.post(storeByDoi.url(projectId), {
            preserveScroll: true,
            onSuccess: () => {
                doiForm.reset();
                setDoiDialogOpen(false);
            },
        });
    };

    return (
        <div
            className="flex shrink-0 items-center gap-2.5 border-b px-5 py-4"
            style={{ borderColor: 'var(--ws-line)' }}
        >
            {/* Search input */}
            <div className="relative flex flex-1 items-center gap-2.5 rounded-[10px] border px-3 py-2.5">
                <Search
                    className="size-4 shrink-0"
                    style={{ color: 'var(--ws-faint)' }}
                    aria-hidden="true"
                />
                <Label htmlFor="command-bar-search" className="sr-only">
                    Search papers
                </Label>
                <input
                    ref={searchInputRef}
                    id="command-bar-search"
                    type="search"
                    placeholder={`Search ${corpusLabel} via OpenAlex…`}
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onFocus={handleFocus}
                    onBlur={handleBlur}
                    className="flex-1 border-none bg-transparent text-[13.5px] outline-none placeholder:text-[var(--ws-faint)]"
                    style={{ color: 'var(--ws-fg)' }}
                />
                {loading && <Spinner className="size-4" />}
                <kbd
                    className="rounded border px-1.5 py-0.5 font-mono text-[11px]"
                    style={{
                        color: 'var(--ws-faint)',
                        borderColor: 'var(--ws-line)',
                        background: 'var(--ws-panel)',
                    }}
                >
                    ⌘K
                </kbd>

                {/* Search results dropdown */}
                {showResults && hasSearched && (
                    <div
                        className="absolute top-full right-0 left-0 z-50 mt-1 max-h-80 overflow-y-auto rounded-[10px] border p-2 shadow-lg"
                        style={{
                            background: 'var(--ws-panel)',
                            borderColor: 'var(--ws-line)',
                        }}
                    >
                        {results.length === 0 ? (
                            <p
                                className="p-2 text-sm"
                                style={{ color: 'var(--ws-muted)' }}
                            >
                                No papers found.
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-1">
                                {results.map((paper) => {
                                    const isAdded = paper.openalex_id
                                        ? savedOpenAlexIds.includes(paper.openalex_id)
                                        : false;

                                    return (
                                        <li
                                            key={paper.openalex_id ?? paper.title}
                                            className="flex items-start justify-between gap-2 rounded-lg p-2 transition-colors hover:bg-[var(--ws-soft)]"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <h4
                                                    className="truncate text-sm font-medium"
                                                    style={{ color: 'var(--ws-fg)' }}
                                                >
                                                    {paper.title}
                                                </h4>
                                                <div
                                                    className="mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5 text-xs"
                                                    style={{ color: 'var(--ws-muted)' }}
                                                >
                                                    {paper.year && <span>{paper.year}</span>}
                                                    {paper.venue && <span>{paper.venue}</span>}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => handleAddPaper(paper)}
                                                disabled={isAdded}
                                                className="h-7 shrink-0 rounded-md px-2 text-xs font-medium transition-opacity hover:opacity-80"
                                                style={{
                                                    background: isAdded
                                                        ? 'var(--ws-soft)'
                                                        : 'var(--ws-accent)',
                                                    color: isAdded
                                                        ? 'var(--ws-muted)'
                                                        : 'var(--ws-onacc)',
                                                }}
                                                aria-label={
                                                    isAdded
                                                        ? `${paper.title} already added`
                                                        : `Add ${paper.title}`
                                                }
                                            >
                                                {isAdded ? 'Added ✓' : 'Add'}
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </div>
                )}
            </div>

            {/* Add via DOI button */}
            <Dialog open={doiDialogOpen} onOpenChange={setDoiDialogOpen}>
                <DialogTrigger asChild>
                    <button
                        type="button"
                        className="flex items-center gap-1.5 rounded-[10px] border px-3 py-2.5 text-[12.5px] font-semibold transition-opacity hover:opacity-80"
                        style={{
                            background: 'var(--ws-panel)',
                            borderColor: 'var(--ws-line)',
                            color: 'var(--ws-fg)',
                        }}
                    >
                        Add via DOI
                    </button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add paper by DOI</DialogTitle>
                        <DialogDescription>
                            Enter a DOI (e.g. 10.1234/example) to look up and add the paper.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleDoiSubmit} className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="doi-input">DOI</Label>
                            <Input
                                id="doi-input"
                                value={doiForm.data.doi}
                                onChange={(e) => doiForm.setData('doi', e.target.value)}
                                placeholder="10.1234/example"
                                required
                            />
                        </div>
                        <Button type="submit" disabled={doiForm.processing}>
                            {doiForm.processing ? 'Looking up...' : 'Add paper'}
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
