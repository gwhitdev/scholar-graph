import { AppContent } from '@/components/app-content';
import { HoverableSidebar } from '@/components/hoverable-sidebar';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { ProjectDrawerProvider } from '@/components/project-drawer';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <ProjectDrawerProvider>
            <AppShell variant="sidebar">
                <HoverableSidebar />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    {children}
                </AppContent>
            </AppShell>
        </ProjectDrawerProvider>
    );
}
