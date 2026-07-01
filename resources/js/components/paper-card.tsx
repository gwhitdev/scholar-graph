import { Link } from '@inertiajs/react';
import { Trash2Icon } from 'lucide-react';
import { destroy } from '@/actions/App/Http/Controllers/PaperController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Paper {
    id: number;
    semantic_scholar_id: string | null;
    title: string;
    abstract: string | null;
    year: number | null;
    authors: string[] | null;
    doi: string | null;
    venue: string | null;
    pages: string | null;
}

interface PaperCardProps {
    projectId: number;
    paper: Paper;
}

export function PaperCard({ projectId, paper }: PaperCardProps) {
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
                </div>
                {paper.authors && paper.authors.length > 0 && (
                    <p className="mt-1 text-xs text-muted-foreground">
                        {paper.authors.join(', ')}
                    </p>
                )}
            </CardHeader>
            {paper.abstract && (
                <CardContent>
                    <p className="line-clamp-4 text-sm text-muted-foreground">
                        {paper.abstract}
                    </p>
                </CardContent>
            )}
        </Card>
    );
}
