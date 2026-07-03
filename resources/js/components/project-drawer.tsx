import { usePage } from '@inertiajs/react';
import { FolderOpen, Plus, X } from 'lucide-react';
import { useCallback, useState, type ReactNode } from 'react';
import { createContext, useContext } from 'react';

interface ProjectDrawerContextType {
    open: boolean;
    toggle: () => void;
    close: () => void;
}

const ProjectDrawerContext = createContext<ProjectDrawerContextType | null>(null);

export function useProjectDrawer() {
    const ctx = useContext(ProjectDrawerContext);
    if (!ctx) {
        throw new Error('useProjectDrawer must be used within ProjectDrawerProvider');
    }
    return ctx;
}

export function ProjectDrawerProvider({ children }: { children: ReactNode }) {
    const [open, setOpen] = useState(false);

    const toggle = useCallback(() => setOpen((prev) => !prev), []);
    const close = useCallback(() => setOpen(false), []);

    return (
        <ProjectDrawerContext.Provider value={{ open, toggle, close }}>
            {children}
            <AppProjectDrawer open={open} onClose={close} />
        </ProjectDrawerContext.Provider>
    );
}

/** Width of the expanded main sidebar (matches SIDEBAR_WIDTH in sidebar.tsx) */
const SIDEBAR_WIDTH = '16rem';

/**
 * Global overlay drawer that lists projects and allows creating new ones.
 * Rendered once at the app level, available on every page.
 *
 * The drawer is positioned directly to the right of the main AppSidebar
 * (no backdrop) so both sidebars form a single hover zone.
 * Moving the mouse off either sidebar closes the drawer.
 */
function AppProjectDrawer({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { allProjects } = usePage().props;
    const projects = (allProjects ?? []) as { id: number; name: string }[];

    return (
        <div
            className="fixed top-0 z-50 h-full overflow-hidden border-r border-sidebar-border shadow-lg transition-all duration-200 ease-in-out"
            style={{
                left: SIDEBAR_WIDTH,
                width: '280px',
                transform: open ? 'translateX(0)' : 'translateX(-100%)',
                opacity: open ? 1 : 0,
                pointerEvents: open ? 'auto' : 'none',
                background: 'var(--sidebar)',
            }}
            aria-hidden={!open}
            role="dialog"
            aria-label="Projects"
            onMouseLeave={open ? onClose : undefined}
        >
            <div className="flex h-full flex-col px-4 py-5">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <h2 className="font-mono text-[10.5px] uppercase tracking-widest text-muted-foreground">
                        Projects
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="flex size-7 items-center justify-center rounded-lg transition-colors hover:bg-accent"
                        aria-label="Close projects"
                    >
                        <X className="size-3.5 text-muted-foreground" />
                    </button>
                </div>

                {/* Create new project */}
                <a
                    href="/projects/create"
                    className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-3 py-2 text-[13px] font-semibold text-primary-foreground transition-opacity hover:opacity-90"
                >
                    <Plus className="size-4" />
                    New project
                </a>

                {/* Project list */}
                <div className="mt-5 flex-1 overflow-y-auto">
                    {projects.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No projects yet. Create one to get started.
                        </p>
                    ) : (
                        <ul className="flex flex-col gap-0.5">
                            {projects.map((project) => (
                                <li key={project.id}>
                                    <a
                                        href={`/projects/${project.id}`}
                                        className="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[13px] text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                    >
                                        <FolderOpen className="size-3.5 shrink-0" />
                                        <span className="truncate">{project.name}</span>
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    );
}
