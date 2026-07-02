import { Link, useHttp, usePoll } from '@inertiajs/react';
import { SparklesIcon, Trash2Icon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import {
    destroy,
    enrich,
} from '@/actions/App/Http/Controllers/PaperController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Enrichment {
    tldr: string | null;
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
    enrichment?: Enrichment | null;
}

interface PaperCardProps {
    projectId: number;
    paper: Paper;
}

/** Stop polling for enrichment results after this many milliseconds. */
const ENRICHMENT_POLL_CAP_MS = 60000;

export function PaperCard({ projectId, paper }: PaperCardProps) {
    const [enrichmentRequested, setEnrichmentRequested] = useState(false);
    const pollCapRef = useRef<ReturnType<typeof setTimeout> | null>(null);

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
            {(paper.abstract || paper.enrichment?.tldr || paper.doi) && (
                <CardContent className="space-y-3">
                    {paper.enrichment?.tldr && (
                        <div className="rounded-md bg-muted/50 p-3">
                            <p className="mb-1 flex items-center gap-1 text-xs font-medium">
                                <SparklesIcon className="size-3" />
                                TLDR
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {paper.enrichment.tldr}
                            </p>
                        </div>
                    )}
                    {paper.abstract && (
                        <p className="line-clamp-4 text-sm text-muted-foreground">
                            {paper.abstract}
                        </p>
                    )}
                    {!paper.enrichment && paper.doi && (
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
