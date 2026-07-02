import type { ReactNode } from 'react';

interface WorkspaceShellProps {
    rail: ReactNode;
    sidebar: ReactNode;
    library: ReactNode;
    discussion: ReactNode;
}

export function WorkspaceShell({ rail, sidebar, library, discussion }: WorkspaceShellProps) {
    return (
        <div className="workspace flex h-full w-full overflow-hidden">
            <div
                className="flex shrink-0 flex-col border-r border-[var(--ws-line)]"
                style={{ width: '56px', background: 'var(--ws-panel)' }}
            >
                {rail}
            </div>
            <div
                className="flex shrink-0 flex-col border-r border-[var(--ws-line)]"
                style={{ width: '246px', background: 'var(--ws-panel)' }}
            >
                {sidebar}
            </div>
            <div className="flex min-w-0 flex-1 flex-col">{library}</div>
            <div
                className="flex shrink-0 flex-col border-l border-[var(--ws-line)]"
                style={{ width: '378px', background: 'var(--ws-panel)' }}
            >
                {discussion}
            </div>
        </div>
    );
}
