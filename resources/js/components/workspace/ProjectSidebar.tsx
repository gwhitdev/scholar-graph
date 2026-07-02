import { Link } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import { CollectionsList } from '@/components/CollectionsList';
import type { CollectionColor } from '@/components/CollectionsList';
import { WorkflowSteps } from '@/components/workspace/WorkflowSteps';

interface Collection {
    id: number;
    name: string;
    color: CollectionColor;
    papers: { id: number }[];
}

interface ProjectSidebarProps {
    projectName: string;
    projectId: number;
    collections: Collection[];
    collectionColors: CollectionColor[];
    hasSearched: boolean;
    paperCount: number;
    chatCount: number;
    onFindPapers: () => void;
    onEditPrompt: () => void;
}

export function ProjectSidebar({
    projectName,
    collections,
    collectionColors,
    hasSearched,
    paperCount,
    chatCount,
    onFindPapers,
    onEditPrompt,
    projectId,
}: ProjectSidebarProps) {
    return (
        <div className="flex h-full flex-col px-4 py-5">
            {/* Breadcrumb */}
            <div className="text-xs" style={{ color: 'var(--ws-faint)' }}>
                <Link href={projectsIndex()} className="hover:underline">
                    Projects
                </Link>{' '}
                /
            </div>

            {/* Project name */}
            <h2
                className="mt-1 font-serif text-[22px] font-medium leading-tight"
                style={{ color: 'var(--ws-fg)' }}
            >
                {projectName}
            </h2>

            {/* Find papers CTA */}
            <button
                type="button"
                onClick={onFindPapers}
                className="mt-4 flex w-full items-center justify-center gap-2 rounded-[10px] px-2.5 py-2.5 text-[13px] font-semibold transition-opacity hover:opacity-90"
                style={{
                    background: 'var(--ws-accent)',
                    color: 'var(--ws-onacc)',
                }}
            >
                <Search className="size-4" />
                Find papers
            </button>

            {/* Workflow */}
            <div className="mt-6">
                <div
                    className="mb-2.5 flex items-center gap-1.5 font-mono text-[10.5px] uppercase tracking-widest"
                    style={{ color: 'var(--ws-faint)' }}
                >
                    <svg
                        width="13"
                        height="13"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <circle cx="5.5" cy="5" r="1.9" stroke="currentColor" strokeWidth="1.4" />
                        <circle cx="14.5" cy="15" r="1.9" stroke="currentColor" strokeWidth="1.4" />
                        <path
                            d="M7.4 5h4.6a2.5 2.5 0 012.5 2.5V13"
                            stroke="currentColor"
                            strokeWidth="1.4"
                            strokeLinecap="round"
                        />
                    </svg>
                    Workflow
                </div>
                <WorkflowSteps
                    hasSearched={hasSearched}
                    paperCount={paperCount}
                    chatCount={chatCount}
                />
            </div>

            {/* Collections */}
            <div className="mt-6">
                <div
                    className="mb-2.5 flex items-center gap-1.5 font-mono text-[10.5px] uppercase tracking-widest"
                    style={{ color: 'var(--ws-faint)' }}
                >
                    <svg
                        width="13"
                        height="13"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M3 6.5A1.5 1.5 0 014.5 5h3l1.5 2h6A1.5 1.5 0 0116.5 8.5v6A1.5 1.5 0 0115 16H4.5A1.5 1.5 0 013 14.5z"
                            stroke="currentColor"
                            strokeWidth="1.4"
                            strokeLinejoin="round"
                        />
                    </svg>
                    Collections
                </div>
                <SidebarCollections
                    projectId={projectId}
                    collections={collections}
                    collectionColors={collectionColors}
                />
            </div>

            <div className="flex-1" />

            {/* Edit prompt footer */}
            <button
                type="button"
                onClick={onEditPrompt}
                className="flex items-center justify-between border-t pt-3.5 transition-opacity hover:opacity-80"
                style={{ borderColor: 'var(--ws-line)' }}
            >
                <span className="text-[12.5px]" style={{ color: 'var(--ws-muted)' }}>
                    Edit prompt
                </span>
                <svg
                    width="15"
                    height="15"
                    viewBox="0 0 20 20"
                    fill="none"
                    style={{ color: 'var(--ws-faint)' }}
                    aria-hidden="true"
                >
                    <circle cx="10" cy="10" r="2.2" stroke="currentColor" strokeWidth="1.5" />
                    <path
                        d="M10 4v1.6M10 14.4V16M4 10h1.6M14.4 10H16M5.8 5.8l1.1 1.1M13.1 13.1l1.1 1.1M14.2 5.8l-1.1 1.1M6.9 13.1l-1.1 1.1"
                        stroke="currentColor"
                        strokeWidth="1.3"
                        strokeLinecap="round"
                    />
                </svg>
            </button>
        </div>
    );
}

/**
 * Compact collections display for the sidebar.
 * Wraps the full CollectionsList but styled for sidebar context.
 */
function SidebarCollections({
    projectId,
    collections,
    collectionColors,
}: {
    projectId: number;
    collections: Collection[];
    collectionColors: CollectionColor[];
}) {
    if (collections.length === 0) {
        return (
            <div className="space-y-2">
                <p className="text-[13px]" style={{ color: 'var(--ws-muted)' }}>
                    No collections yet
                </p>
                <CollectionsList
                    projectId={projectId}
                    collections={collections}
                    collectionColors={collectionColors}
                />
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <ul className="flex flex-col gap-2">
                {collections.map((collection) => (
                    <li
                        key={collection.id}
                        className="flex items-center gap-2 text-[13px]"
                        style={{ color: 'var(--ws-muted)' }}
                    >
                        <span
                            className="size-2 shrink-0 rounded-sm"
                            style={{
                                background: `var(--collection-${collection.color}, var(--ws-accent))`,
                            }}
                            aria-hidden="true"
                        />
                        <span className="truncate">{collection.name}</span>
                        <span className="flex-1" />
                        <span className="text-[11.5px]" style={{ color: 'var(--ws-faint)' }}>
                            {collection.papers.length}
                        </span>
                    </li>
                ))}
            </ul>
            <CollectionsList
                projectId={projectId}
                collections={collections}
                collectionColors={collectionColors}
            />
        </div>
    );
}
