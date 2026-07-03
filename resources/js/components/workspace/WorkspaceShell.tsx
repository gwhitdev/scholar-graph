import type { ReactNode } from 'react';

interface WorkspaceShellProps {
    library: ReactNode;
    discussion: ReactNode;
    discussionExpanded?: boolean;
}

export function WorkspaceShell({
    library,
    discussion,
    discussionExpanded = false,
}: WorkspaceShellProps) {
    return (
        <div className="workspace flex h-full w-full overflow-hidden">
            {/* Library area */}
            {!discussionExpanded && (
                <div className="flex min-w-0 flex-1 flex-col">{library}</div>
            )}

            {/* Discussion panel */}
            <div
                className="flex shrink-0 flex-col border-l border-[var(--ws-line)]"
                style={{
                    width: discussionExpanded ? '100%' : '470px',
                    maxWidth: discussionExpanded ? '100%' : '470px',
                    height: '100%',
                    background: 'var(--ws-panel)',
                }}
            >
                {discussion}
            </div>
        </div>
    );
}
