import { Link } from '@inertiajs/react';
import admin from '@/routes/admin';
import licenses from '@/routes/admin/licenses';
import usage from '@/routes/admin/usage';
import users from '@/routes/admin/users';
import { cn } from '@/lib/utils';
import { useCurrentUrl } from '@/hooks/use-current-url';

const navItems = [
    { label: 'Dashboard', href: admin.index.url() },
    { label: 'Users', href: users.index.url() },
    { label: 'Usage', href: usage.index.url() },
    { label: 'Licences', href: licenses.index.url() },
];

export default function AdminNav() {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <nav className="flex gap-1 border-b px-6 py-2" aria-label="Admin">
            {navItems.map((item) => (
                <Link
                    key={item.href}
                    href={item.href}
                    className={cn(
                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted',
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-muted text-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    {item.label}
                </Link>
            ))}
        </nav>
    );
}
