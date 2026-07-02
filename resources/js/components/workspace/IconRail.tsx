import { usePage } from '@inertiajs/react';
import { FolderOpen, Library, MessageSquare } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { User } from '@/types';

export type PanelKey = 'projects' | 'library' | 'discuss';

interface IconRailProps {
    appName: string;
    activePanel: PanelKey | null;
    onPanelToggle: (panel: PanelKey) => void;
}

const navItems: { key: PanelKey; icon: typeof FolderOpen; label: string }[] = [
    { key: 'projects', icon: FolderOpen, label: 'Projects' },
    { key: 'library', icon: Library, label: 'Library' },
    { key: 'discuss', icon: MessageSquare, label: 'Discussion' },
];

export function IconRail({ appName, activePanel, onPanelToggle }: IconRailProps) {
    const { auth } = usePage().props;
    const user = auth.user as User;
    const initials = user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    return (
        <>
            {/* Logo */}
            <div
                className="mb-3.5 flex size-[34px] items-center justify-center rounded-[10px] font-serif text-lg font-semibold"
                style={{
                    background: 'var(--ws-accent)',
                    color: 'var(--ws-onacc)',
                }}
                title={appName}
            >
                S
            </div>

            {/* Nav icons — toggle sidebar panels */}
            <div className="flex flex-col items-center gap-1.5">
                {navItems.map(({ key, icon: Icon, label }) => {
                    const isActive = activePanel === key;
                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() => onPanelToggle(key)}
                            className="flex size-11 items-center justify-center rounded-xl transition-colors"
                            style={{
                                background: isActive ? 'var(--ws-soft)' : 'transparent',
                                color: isActive ? 'var(--ws-accent)' : 'var(--ws-faint)',
                            }}
                            aria-label={label}
                            aria-pressed={isActive}
                        >
                            <Icon className="size-[19px]" />
                        </button>
                    );
                })}
            </div>

            <div className="flex-1" />

            {/* User avatar */}
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        type="button"
                        className="flex size-9 items-center justify-center rounded-full border text-xs font-semibold transition-colors hover:opacity-80"
                        style={{
                            background: 'var(--ws-panel2)',
                            borderColor: 'var(--ws-line)',
                            color: 'var(--ws-muted)',
                        }}
                        aria-label="User menu"
                    >
                        {initials}
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" side="right">
                    <DropdownMenuItem asChild>
                        <a href="/settings/profile">Profile</a>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <a href="/settings/appearance">Appearance</a>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <a href="/logout" method="post" as="button">
                            Log out
                        </a>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </>
    );
}
