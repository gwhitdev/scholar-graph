import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';

interface WorkspaceLayoutProps {
    breadcrumbs?: BreadcrumbItem[];
    children: ReactNode;
}

export default function WorkspaceLayout({ children }: WorkspaceLayoutProps) {
    return <div className="flex h-dvh w-full overflow-hidden">{children}</div>;
}
