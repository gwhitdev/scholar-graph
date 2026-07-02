import { Link, router, useForm, useHttp, usePoll } from '@inertiajs/react';
import { SparklesIcon, Trash2Icon, XIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import {
    addPaper,
    removePaper,
} from '@/actions/App/Http/Controllers/CollectionController';
import {
    destroy,
    enrich,
    updateStatus,
} from '@/actions/App/Http/Controllers/PaperController';
import {
    colorTokenMap,
} from '@/components/CollectionsList';
import type { CollectionColor } from '@/components/CollectionsList';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Enrichment {
    tldr: string | null;
    tldr_source: 'semantic_scholar' | 'generated' | null;
    influential_citation_count: number | null;
    enriched_at: string | null;
}

interface Paper {
    id: number;
    openalex_id: string | null;
    title: string;
    abstract: string | null;
    year: number | null;
    authors: string[] | null;
    doi: string | null;
    venue: string | null;
    pages: string | null;
    cited_by_count: number | null;
    pivot: {
        status: string;
        added_at: string;
    };
    enrichment?: Enrichment | null;
}

interface Collection {
    id: number;
    name: string;
    color: CollectionColor;
    papers: { id: number }[];
}

interface PaperCardProps {
    projectId: number;
    paper: Paper;
    collections?: Collection[];
}

/** Stop polling for enrichment results after this many milliseconds. */
const ENRICHMENT_POLL_CAP_MS = 60000;

const statusLabels: Record<string, string> = {
    unread: 'Unread',
    reading: 'Reading',
    read: 'Read',
    excluded: 'Excluded',
};

export function PaperCard({ projectId, paper, collections = [] }: PaperCardProps) {
    const [enrichmentRequested, setEnrichmentRequested] = useState(false);
    const pollCapRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const { post: postAdd, processing: adding } = useForm({ paper_id: paper.id });
    const { post: postRemove, processing: removing } = useForm({});

    const { post, processing } = useHttp({});

    const { start, stop } = usePoll(
        5000,
        { only: ['papers'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (enrichmentRequested && paper.enrichment) {
            stop();

            if (pollCapRef.current) {
                clearTimeout(pollCapRef.current);
                pollCapRef.current = null;
            }
        }
    }, [enrichmentRequested, paper.enrichment, stop]);

    useEffect(() => {
        return () => {
            if (pollCapRef.current) {
                clearTimeout(pollCapRef.current);
            }
        };
    }, []);

    const requestEnrichment = () => {
        post(enrich.url({ project: projectId, paper: paper.id }), {
            onSuccess: () => {
                setEnrichmentRequested(true);
                start();

                pollCapRef.current = setTimeout(() => {
                    stop();
                    setEnrichmentRequested(false);
                }, ENRICHMENT_POLL_CAP_MS);
            },
        });
    };

    const handleStatusChange = (value: string) => {
        router.patch(
            updateStatus.url({ project: projectId, paper: paper.id }),
            { status: value },
            { preserveScroll: true },
        );
    };

    const paperCollections = collections.filter((collection) =>
        collection.papers.some((p) => p.id === paper.id),
    );

    const addableCollections = collections.filter(
        (collection) => !collection.papers.some((p) => p.id === paper.id),
    );

    const handleAddToCollection = (collectionId: string) => {
        postAdd(
            addPaper.url({ project: projectId, collection: Number(collectionId) }),
            { preserveScroll: true },
        );
    };

    const handleRemoveFromCollection = (collectionId: number) => {
        postRemove(
            removePaper.url({
                project: projectId,
                collection: collectionId,
                paper: paper.id,
            }),
            { preserveScroll: true },
        );
    };

    return (
        <article
            className="rounded-2xl border p-5"
            style={{
                background: 'var(--ws-panel)',
                borderColor: 'var(--ws-line)',
            }}
        >
            {/* Tag chip row */}
            <div
                className="flex flex-wrap items-center gap-2 font-mono text-[11.5px]"
                style={{ color: 'var(--ws-muted)' }}
            >
                {paper.venue && (
                    <span
                        className="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                        style={{
                            color: 'var(--ws-accent)',
                            background: 'var(--ws-soft)',
                        }}
                    >
                        {paper.venue}
                    </span>
                )}
                {paper.year && <span>{paper.year}</span>}
                {paper.venue && paper.year && (
                    <span
                        className="size-[3px] rounded-full"
                        style={{ background: 'var(--ws-faint)' }}
                        aria-hidden="true"
                    />
                )}
            </div>

            {/* Title */}
            <h3
                className="mt-3 font-serif text-[23px] font-medium leading-snug tracking-tight"
                style={{ color: 'var(--ws-fg)' }}
            >
                {paper.title}
            </h3>

            {/* Authors */}
            {paper.authors && paper.authors.length > 0 && (
                <div className="mt-1.5 text-[13px]" style={{ color: 'var(--ws-muted)' }}>
                    {paper.authors.join(', ')}
                </div>
            )}

            {/* TL;DR */}
            {paper.enrichment?.tldr && (
                <p className="mt-3 text-sm leading-relaxed" style={{ color: 'var(--ws-muted)' }}>
                    <span
                        className="mr-2 align-[1px] font-mono text-[10.5px] tracking-wide"
                        style={{ color: 'var(--ws-accent)' }}
                    >
                        TL;DR
                    </span>
                    {paper.enrichment.tldr}
                </p>
            )}

            {/* Status and collections controls */}
            <div className="mt-3 flex flex-wrap items-center gap-3">
                <div className="flex items-center gap-2">
                    <label
                        htmlFor={`paper-status-${paper.id}`}
                        className="text-xs"
                        style={{ color: 'var(--ws-muted)' }}
                    >
                        Status
                    </label>
                    <Select value={paper.pivot.status} onValueChange={handleStatusChange}>
                        <SelectTrigger
                            id={`paper-status-${paper.id}`}
                            className="h-7 w-32 border-[var(--ws-line)] text-xs"
                            aria-label={`Reading status for ${paper.title}`}
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {Object.entries(statusLabels).map(([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {collections.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2">
                        <label
                            htmlFor={`paper-collections-${paper.id}`}
                            className="text-xs"
                            style={{ color: 'var(--ws-muted)' }}
                        >
                            Collections
                        </label>
                        <Select
                            value=""
                            onValueChange={handleAddToCollection}
                            disabled={adding || addableCollections.length === 0}
                        >
                            <SelectTrigger
                                id={`paper-collections-${paper.id}`}
                                className="h-7 w-40 border-[var(--ws-line)] text-xs"
                                aria-label={`Add ${paper.title} to a collection`}
                            >
                                <SelectValue placeholder="Add to collection" />
                            </SelectTrigger>
                            <SelectContent>
                                {addableCollections.map((collection) => (
                                    <SelectItem key={collection.id} value={String(collection.id)}>
                                        <span className="flex items-center gap-2">
                                            <span
                                                className={`inline-block size-2 rounded-full ${colorTokenMap[collection.color]}`}
                                                aria-hidden="true"
                                            />
                                            {collection.name}
                                        </span>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {paperCollections.map((collection) => (
                            <span
                                key={collection.id}
                                className="flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
                                style={{
                                    background: 'var(--ws-soft)',
                                    color: 'var(--ws-muted)',
                                }}
                            >
                                <span
                                    className={`inline-block size-2 rounded-full ${colorTokenMap[collection.color]}`}
                                    aria-hidden="true"
                                />
                                {collection.name}
                                <button
                                    type="button"
                                    onClick={() => handleRemoveFromCollection(collection.id)}
                                    disabled={removing}
                                    aria-label={`Remove ${paper.title} from ${collection.name}`}
                                    className="ml-0.5 rounded-sm hover:opacity-70"
                                >
                                    <XIcon className="size-3" />
                                </button>
                            </span>
                        ))}
                    </div>
                )}
            </div>

            {/* Enrichment button */}
            {!paper.enrichment && (paper.doi || paper.abstract) && (
                <div className="mt-3">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={requestEnrichment}
                        disabled={processing || enrichmentRequested}
                        className="border-[var(--ws-line)] text-xs"
                    >
                        <SparklesIcon className="size-3" />
                        {enrichmentRequested ? 'Summary requested...' : 'Get AI Summary'}
                    </Button>
                </div>
            )}

            {/* Footer bar */}
            <div
                className="mt-4 flex items-center gap-3.5 border-t pt-3.5"
                style={{ borderColor: 'var(--ws-line)' }}
            >
                {paper.cited_by_count !== null && (
                    <span
                        className="flex items-center gap-1.5 text-[12.5px] font-semibold"
                        style={{ color: 'var(--ws-fg)' }}
                    >
                        <svg
                            width="13"
                            height="13"
                            viewBox="0 0 20 20"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M10 3l2 5 5 .4-3.8 3.3 1.2 5L10 14l-4.6 2.7 1.2-5L2.8 8.4 8 8z"
                                stroke="currentColor"
                                strokeWidth="1.3"
                                strokeLinejoin="round"
                            />
                        </svg>
                        {paper.cited_by_count.toLocaleString()}
                    </span>
                )}
                {paper.doi && (
                    <a
                        href={`https://doi.org/${paper.doi}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="font-mono text-[11px] underline hover:opacity-80"
                        style={{ color: 'var(--ws-faint)' }}
                    >
                        {paper.doi}
                    </a>
                )}
                <span className="flex-1" />
                {paper.enrichment && (
                    <button
                        type="button"
                        className="flex items-center gap-1.5 rounded-[9px] px-3 py-1.5 text-[12.5px] font-semibold transition-opacity hover:opacity-80"
                        style={{
                            background: 'var(--ws-soft)',
                            color: 'var(--ws-accent)',
                        }}
                    >
                        Read summary
                    </button>
                )}
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-7"
                    asChild
                >
                    <Link
                        href={destroy.url({ project: projectId, paper: paper.id })}
                        method="delete"
                        as="button"
                        aria-label={`Remove ${paper.title}`}
                    >
                        <Trash2Icon className="size-4" />
                    </Link>
                </Button>
            </div>
        </article>
    );
}
