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
    colorTokenMap
    
} from '@/components/CollectionsList';
import type {CollectionColor} from '@/components/CollectionsList';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
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

    // Stop polling once the enrichment lands; the button unmounts so the
    // requested flag needs no reset here.
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
        <Card>
            <CardHeader className="pb-2">
                <div className="flex items-start justify-between gap-2">
                    <CardTitle className="text-base leading-snug">
                        {paper.title}
                    </CardTitle>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-7 shrink-0"
                        asChild
                    >
                        <Link
                            href={destroy.url({
                                project: projectId,
                                paper: paper.id,
                            })}
                            method="delete"
                            as="button"
                            aria-label={`Remove ${paper.title}`}
                        >
                            <Trash2Icon className="size-4" />
                        </Link>
                    </Button>
                </div>
                <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                    {paper.year && (
                        <Badge variant="secondary">{paper.year}</Badge>
                    )}
                    {paper.venue && <span>{paper.venue}</span>}
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
                    {paper.pages && <span>pp. {paper.pages}</span>}
                    {paper.cited_by_count !== null && (
                        <span>
                            {paper.cited_by_count.toLocaleString()} citations
                        </span>
                    )}
                </div>
                {paper.authors && paper.authors.length > 0 && (
                    <p className="mt-1 text-xs text-muted-foreground">
                        {paper.authors.join(', ')}
                    </p>
                )}
            </CardHeader>
            {(paper.abstract || paper.enrichment || paper.doi) && (
                <CardContent className="space-y-3">
                    <div className="flex items-center gap-2">
                        <Label
                            htmlFor={`paper-status-${paper.id}`}
                            className="text-xs text-muted-foreground"
                        >
                            Status
                        </Label>
                        <Select
                            value={paper.pivot.status}
                            onValueChange={handleStatusChange}
                        >
                            <SelectTrigger
                                id={`paper-status-${paper.id}`}
                                className="h-7 w-32 text-xs"
                                aria-label={`Reading status for ${paper.title}`}
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(statusLabels).map(
                                    ([value, label]) => (
                                        <SelectItem
                                            key={value}
                                            value={value}
                                        >
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </div>
                    {collections.length > 0 && (
                        <div className="flex flex-wrap items-center gap-2">
                            <Label
                                htmlFor={`paper-collections-${paper.id}`}
                                className="text-xs text-muted-foreground"
                            >
                                Collections
                            </Label>
                            <Select
                                value=""
                                onValueChange={handleAddToCollection}
                                disabled={adding || addableCollections.length === 0}
                            >
                                <SelectTrigger
                                    id={`paper-collections-${paper.id}`}
                                    className="h-7 w-40 text-xs"
                                    aria-label={`Add ${paper.title} to a collection`}
                                >
                                    <SelectValue placeholder="Add to collection" />
                                </SelectTrigger>
                                <SelectContent>
                                    {addableCollections.map((collection) => (
                                        <SelectItem
                                            key={collection.id}
                                            value={String(collection.id)}
                                        >
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
                                <Badge
                                    key={collection.id}
                                    variant="secondary"
                                    className="flex items-center gap-1"
                                >
                                    <span
                                        className={`inline-block size-2 rounded-full ${colorTokenMap[collection.color]}`}
                                        aria-hidden="true"
                                    />
                                    {collection.name}
                                    <button
                                        type="button"
                                        onClick={() =>
                                            handleRemoveFromCollection(collection.id)
                                        }
                                        disabled={removing}
                                        aria-label={`Remove ${paper.title} from ${collection.name}`}
                                        className="ml-1 rounded-sm hover:text-destructive focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <XIcon className="size-3" />
                                    </button>
                                </Badge>
                            ))}
                        </div>
                    )}
                    {paper.enrichment && (
                        <div className="rounded-md bg-muted/50 p-3">
                            <p className="mb-1 flex items-center gap-1 text-xs font-medium">
                                <SparklesIcon className="size-3" />
                                TLDR
                                {paper.enrichment.tldr_source ===
                                    'generated' && (
                                    <span className="font-normal text-muted-foreground">
                                        · AI-generated from abstract
                                    </span>
                                )}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {paper.enrichment.tldr ??
                                    'No AI summary is available for this paper.'}
                            </p>
                            {paper.enrichment.influential_citation_count !==
                                null && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {paper.enrichment.influential_citation_count.toLocaleString()}{' '}
                                    influential citations
                                </p>
                            )}
                        </div>
                    )}
                    {paper.abstract && (
                        <p className="line-clamp-4 text-sm text-muted-foreground">
                            {paper.abstract}
                        </p>
                    )}
                    {!paper.enrichment && (paper.doi || paper.abstract) && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={requestEnrichment}
                            disabled={processing || enrichmentRequested}
                        >
                            <SparklesIcon className="size-3" />
                            {enrichmentRequested
                                ? 'Summary requested...'
                                : 'Get AI Summary'}
                        </Button>
                    )}
                </CardContent>
            )}
        </Card>
    );
}
