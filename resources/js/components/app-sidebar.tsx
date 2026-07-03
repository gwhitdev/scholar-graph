import { Link, usePage } from '@inertiajs/react';
import { BookOpen, FolderGit2, FolderOpen, HelpCircle, LayoutGrid, Shield, TicketIcon } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { useProjectDrawer } from '@/components/project-drawer';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const isAdmin = auth.is_admin === true;
    const { toggle: toggleProjectDrawer, close: closeProjectDrawer } = useProjectDrawer();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Projects',
            href: '#',
            icon: FolderOpen,
            onClick: toggleProjectDrawer,
        },
        {
            title: 'Support',
            href: '/support/tickets',
            icon: TicketIcon,
        },
        {
            title: 'Help',
            href: '/help',
            icon: HelpCircle,
        },
        ...(isAdmin
            ? [
                  {
                      title: 'Admin',
                      href: '/admin',
                      icon: Shield,
                  } as NavItem,
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar" className="border-r border-sidebar-border bg-sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} closeDrawer={closeProjectDrawer} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
