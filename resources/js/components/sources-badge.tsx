import { BookOpenIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

interface SourcesBadgeProps {
    count: number;
}

export function SourcesBadge({ count }: SourcesBadgeProps) {
    if (count <= 0) {
        return null;
    }

    return (
        <Badge variant="outline" className="gap-1">
            <BookOpenIcon className="size-3" />
            {count} source{count === 1 ? '' : 's'}
        </Badge>
    );
}
