import { Maximize2, Minimize2 } from 'lucide-react';
import type { ReactNode } from 'react';

interface DiscussionPanelProps {
    assistantModel: string;
    paperCount: number;
    expanded: boolean;
    onToggleExpand: () => void;
    children: ReactNode;
}

export function DiscussionPanel({
    assistantModel,
    paperCount,
    expanded,
    onToggleExpand,
    children,
}: DiscussionPanelProps) {
    return (
        <div className="flex h-full flex-col">
            {/* Header */}
            <div
                className="flex shrink-0 items-center justify-between border-b px-5 py-4"
                style={{ borderColor: 'var(--ws-line)' }}
            >
                <div className="flex items-center gap-2">
                    <h3
                        className="font-serif text-[19px] font-medium"
                        style={{ color: 'var(--ws-fg)' }}
                    >
                        Discussion
                    </h3>
                    <span
                        className="rounded-full border px-2 py-0.5 text-[11.5px]"
                        style={{
                            color: 'var(--ws-muted)',
                            background: 'var(--ws-panel2)',
                            borderColor: 'var(--ws-line)',
                        }}
                    >
                        grounded in {paperCount} {paperCount === 1 ? 'paper' : 'papers'}
                    </span>
                </div>
                <div className="flex items-center gap-3">
                    <span
                        className="font-mono text-[11px]"
                        style={{ color: 'var(--ws-faint)' }}
                    >
                        {assistantModel}
                    </span>
                    <button
                        type="button"
                        onClick={onToggleExpand}
                        className="flex size-7 items-center justify-center rounded-lg transition-colors hover:bg-[var(--ws-soft)]"
                        aria-label={expanded ? 'Collapse discussion panel' : 'Expand discussion panel'}
                    >
                        {expanded ? (
                            <Minimize2
                                className="size-3.5"
                                style={{ color: 'var(--ws-faint)' }}
                            />
                        ) : (
                            <Maximize2
                                className="size-3.5"
                                style={{ color: 'var(--ws-faint)' }}
                            />
                        )}
                    </button>
                </div>
            </div>

            {/* Content (ChatThread + ChatInput) */}
            <div className="flex min-h-0 flex-1 flex-col">{children}</div>
        </div>
    );
}
