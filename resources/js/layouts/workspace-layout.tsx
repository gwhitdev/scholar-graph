import { HoverableSidebar } from '@/components/hoverable-sidebar';
import { AppShell } from '@/components/app-shell';
import { ProjectDrawerProvider } from '@/components/project-drawer';
import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';

interface WorkspaceLayoutProps {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}

export default function WorkspaceLayout({ children }: WorkspaceLayoutProps) {
    return (
        <ProjectDrawerProvider>
            <AppShell>
                <HoverableSidebar />
                <div className="flex min-h-svh flex-1 flex-col overflow-hidden">
                    {children}
                </div>
            </AppShell>
        </ProjectDrawerProvider>
    );
}
