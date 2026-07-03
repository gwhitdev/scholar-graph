import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({ items = [], closeDrawer }: { items: NavItem[]; closeDrawer?: () => void }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="font-mono text-[10.5px] uppercase tracking-widest text-muted-foreground">
                Platform
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const active = isCurrentUrl(item.href);
                    const handleClick = (e: React.MouseEvent) => {
                        if (item.onClick) {
                            e.preventDefault();
                            item.onClick();
                        } else {
                            closeDrawer?.();
                        }
                    };
                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={active}
                                tooltip={{ children: item.title }}
                                className={`text-[13px] ${active ? 'bg-accent font-semibold text-accent-foreground' : 'text-muted-foreground hover:text-foreground'} rounded-lg`}
                            >
                                <Link href={item.href} prefetch onClick={handleClick}>
                                    {item.icon && <item.icon className="size-[19px]" />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
