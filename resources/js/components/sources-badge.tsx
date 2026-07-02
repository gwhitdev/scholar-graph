import { BookOpenIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface Paper {
    id: number;
    title: string;
}

interface SourcesBadgeProps {
    paperIds: number[] | null;
    papers: Paper[];
}

export function SourcesBadge({ paperIds, papers }: SourcesBadgeProps) {
    if (!paperIds || paperIds.length === 0) {
        return null;
    }

    const sourcePapers = paperIds
        .map((id) => papers.find((p) => p.id === id))
        .filter(Boolean) as Paper[];

    if (sourcePapers.length === 0) {
        return (
            <Badge variant="outline" className="gap-1">
                <BookOpenIcon className="size-3" />
                {paperIds.length} source{paperIds.length === 1 ? '' : 's'}
            </Badge>
        );
    }

    return (
        <div className="flex flex-wrap gap-1">
            {sourcePapers.map((paper) => (
                <Badge
                    key={paper.id}
                    variant="outline"
                    className="gap-1 text-xs"
                    title={paper.title}
                >
                    <BookOpenIcon className="size-3 shrink-0" />
                    <span className="max-w-32 truncate">{paper.title}</span>
                </Badge>
            ))}
        </div>
    );
}
