import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface LibraryHeaderProps {
    paperCount: number;
    sortBy: string;
    onSortChange: (value: string) => void;
}

export function LibraryHeader({ paperCount, sortBy, onSortChange }: LibraryHeaderProps) {
    return (
        <div className="flex items-end justify-between px-10 pt-7">
            <div>
                <div
                    className="flex items-center gap-1.5 font-mono text-[11px] uppercase tracking-widest"
                    style={{ color: 'var(--ws-accent)' }}
                >
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M10 3l7 3.5-7 3.5-7-3.5z"
                            stroke="currentColor"
                            strokeWidth="1.4"
                            strokeLinejoin="round"
                        />
                        <path
                            d="M3 10l7 3.5 7-3.5M3 13.3l7 3.5 7-3.5"
                            stroke="currentColor"
                            strokeWidth="1.4"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                    Library
                </div>
                <h1
                    className="mt-1.5 font-serif text-[29px] font-medium tracking-tight"
                    style={{ color: 'var(--ws-fg)' }}
                >
                    {paperCount} {paperCount === 1 ? 'paper' : 'papers'} collected
                </h1>
            </div>

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
    );
}
