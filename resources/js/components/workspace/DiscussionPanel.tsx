import type { ReactNode } from 'react';

interface DiscussionPanelProps {
    assistantModel: string;
    paperCount: number;
    children: ReactNode;
}

export function DiscussionPanel({
    assistantModel,
    paperCount,
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
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 20 20"
                        fill="none"
                        style={{ color: 'var(--ws-accent)' }}
                        aria-hidden="true"
                    >
                        <path
                            d="M4 5.5h12v8H9.5L6 16.5V13.5H4z"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinejoin="round"
                        />
                    </svg>
                    <h3
                        className="font-serif text-[19px] font-medium"
                        style={{ color: 'var(--ws-fg)' }}
                    >
                        Discussion
                    </h3>
                    <span
                        className="rounded-full border px-2 py-0.5 text-[11px]"
                        style={{
                            color: 'var(--ws-muted)',
                            background: 'var(--ws-panel2)',
                            borderColor: 'var(--ws-line)',
                        }}
                    >
                        {paperCount} {paperCount === 1 ? 'paper' : 'papers'}
                    </span>
                    <span
                        className="rounded-full border px-2 py-0.5 text-[11px]"
                        style={{
                            color: 'var(--ws-muted)',
                            background: 'var(--ws-panel2)',
                            borderColor: 'var(--ws-line)',
                        }}
                    >
                        {assistantModel}
                    </span>
                </div>
            </div>

            {/* Content (ChatThread + ChatInput) */}
            <div className="flex min-h-0 flex-1 flex-col">{children}</div>
        </div>
    );
}
