import type { ReactNode } from 'react';

interface WorkspaceShellProps {
    rail: ReactNode;
    sidebar?: ReactNode;
    sidebarOpen?: boolean;
    onSidebarClose?: () => void;
    library: ReactNode;
    discussion: ReactNode;
    discussionExpanded?: boolean;
}

export function WorkspaceShell({
    rail,
    sidebar,
    sidebarOpen = false,
    onSidebarClose,
    library,
    discussion,
    discussionExpanded = false,
}: WorkspaceShellProps) {
    return (
        <div className="workspace flex h-full w-full overflow-hidden">
            {/* Icon rail — hidden when discussion is expanded */}
            {!discussionExpanded && (
                <div
                    className="flex shrink-0 flex-col items-center border-r border-[var(--ws-line)] py-4"
                    style={{ width: '68px', background: 'var(--ws-panel)' }}
                >
                    {rail}
                </div>
            )}

            {/* Expandable sidebar */}
            {!discussionExpanded && (
                <div
                    className="shrink-0 overflow-hidden border-r border-[var(--ws-line)] transition-all duration-200 ease-in-out"
                    style={{
                        width: sidebarOpen ? '260px' : '0px',
                        opacity: sidebarOpen ? 1 : 0,
                        background: 'var(--ws-panel)',
                    }}
                    aria-hidden={!sidebarOpen}
                >
                    {sidebarOpen && sidebar && <div className="flex h-full flex-col">{sidebar}</div>}
                </div>
            )}

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
