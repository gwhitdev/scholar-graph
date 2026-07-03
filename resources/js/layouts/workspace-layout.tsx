import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';

interface WorkspaceLayoutProps {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}

export default function WorkspaceLayout({ children }: WorkspaceLayoutProps) {
    return <div className="fixed inset-0 flex overflow-hidden">{children}</div>;
}
