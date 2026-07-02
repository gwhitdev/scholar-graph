import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface LibraryHeaderProps {
    paperCount: number;
    totalCitations: number;
    sortBy: string;
    onSortChange: (value: string) => void;
}

export function LibraryHeader({
    paperCount,
    totalCitations,
    sortBy,
    onSortChange,
}: LibraryHeaderProps) {
    return (
        <div className="flex items-baseline justify-between px-8 pt-7">
            <h3
                className="font-serif text-[19px] font-medium"
                style={{ color: 'var(--ws-fg)' }}
            >
                Your library
            </h3>
            <div className="flex items-center gap-3">
                <span
                    className="font-mono text-[12px]"
                    style={{ color: 'var(--ws-faint)' }}
                >
                    {paperCount} {paperCount === 1 ? 'paper' : 'papers'}{' · '}
                    {totalCitations.toLocaleString()} citations
                </span>
                <div className="flex items-center gap-1.5 text-[12.5px]">
                    <span style={{ color: 'var(--ws-muted)' }}>Sort</span>
                    <Select value={sortBy} onValueChange={onSortChange}>
                        <SelectTrigger
                            className="h-auto border-none bg-transparent p-0 text-[12.5px] font-semibold shadow-none"
                            style={{ color: 'var(--ws-fg)' }}
                            aria-label="Sort papers by"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="date">Date added</SelectItem>
                            <SelectItem value="citations">Citations</SelectItem>
                            <SelectItem value="year">Year</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>
    );
}
