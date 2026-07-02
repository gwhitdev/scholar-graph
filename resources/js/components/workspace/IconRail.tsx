import { Link, usePage } from '@inertiajs/react';
import { FolderOpen } from 'lucide-react';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { User } from '@/types';

interface IconRailProps {
    appName: string;
}

export function IconRail({ appName }: IconRailProps) {
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
                className="mb-3 flex size-8 items-center justify-center rounded-[9px] font-serif text-base font-semibold"
                style={{
                    background: 'var(--ws-accent)',
                    color: 'var(--ws-onacc)',
                }}
                title={appName}
            >
                S
            </div>

            {/* Nav icons */}
            <Link
                href={projectsIndex()}
                className="flex size-9 items-center justify-center rounded-[10px] transition-colors hover:opacity-80"
                style={{ background: 'var(--ws-soft)', color: 'var(--ws-accent)' }}
                aria-label="Projects"
            >
                <FolderOpen className="size-4" />
            </Link>

            <div className="flex-1" />

            {/* User avatar */}
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <button
                        type="button"
                        className="flex size-8 items-center justify-center rounded-full border text-xs font-bold transition-colors hover:opacity-80"
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
                        <Link href="/settings/profile">Profile</Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link href="/settings/appearance">Appearance</Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <Link href="/logout" method="post" as="button">
                            Log out
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </>
    );
}
