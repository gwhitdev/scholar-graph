import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { useState } from 'react';
import AdminNav from '@/components/admin-nav';

interface Ticket {
    id: number;
    type: string;
    subject: string;
    status: string;
    user: { id: number; name: string };
    messages: Array<{ id: number }>;
    created_at: string;
}

interface Props {
    tickets: Ticket[];
}

const typeColors: Record<string, string> = {
    bug: 'bg-destructive/15 text-destructive border-destructive/20',
    feature: 'bg-primary/15 text-primary border-primary/20',
    support: 'bg-secondary text-secondary-foreground border-secondary/20',
    billing: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
};

const statusColors: Record<string, string> = {
    open: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
    in_progress: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800',
    resolved: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800/50 dark:text-gray-300 dark:border-gray-700',
    closed: 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-gray-800/30 dark:text-gray-500 dark:border-gray-700',
};

const statusLabels: Record<string, string> = {
    open: 'Open',
    in_progress: 'In Progress',
    resolved: 'Resolved',
    closed: 'Closed',
};

const typeLabels: Record<string, string> = {
    bug: 'Bug',
    feature: 'Feature',
    support: 'Support',
    billing: 'Billing',
};

const statusFilters = ['all', 'open', 'in_progress', 'resolved', 'closed'] as const;

export default function Index({ tickets }: Props) {
    const [statusFilter, setStatusFilter] = useState<string>('all');

    const filtered = statusFilter === 'all'
        ? tickets
        : tickets.filter((t) => t.status === statusFilter);

    return (
        <>
            <Head title="All Tickets" />
            <AdminNav />
            <div className="mx-auto max-w-6xl px-4 py-8">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold">Support Tickets</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {filtered.length} of {tickets.length} ticket{tickets.length !== 1 ? 's' : ''}
                    </p>
                </div>

                {/* Status filter tabs */}
                <div className="mb-4 flex gap-1 overflow-x-auto">
                    {statusFilters.map((status) => (
                        <button
                            key={status}
                            onClick={() => setStatusFilter(status)}
                            className={cn(
                                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors hover:bg-muted',
                                statusFilter === status
                                    ? 'bg-muted text-foreground'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {status === 'all' ? 'All' : statusLabels[status] ?? status}
                        </button>
                    ))}
                </div>

                {filtered.length === 0 ? (
                    <Card>
                        <CardContent className="py-12 text-center text-muted-foreground">
                            No tickets found.
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Tickets</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="pb-2 pr-4 font-medium">Subject</th>
                                            <th className="pb-2 pr-4 font-medium">User</th>
                                            <th className="pb-2 pr-4 font-medium">Type</th>
                                            <th className="pb-2 pr-4 font-medium">Status</th>
                                            <th className="pb-2 pr-4 font-medium">Messages</th>
                                            <th className="pb-2 font-medium">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((ticket) => (
                                            <tr key={ticket.id} className="border-b last:border-0">
                                                <td className="py-3 pr-4">
                                                    <Link
                                                        href={route('admin.tickets.show', ticket.id)}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {ticket.subject}
                                                    </Link>
                                                </td>
                                                <td className="py-3 pr-4 text-muted-foreground">{ticket.user.name}</td>
                                                <td className="py-3 pr-4">
                                                    <Badge variant="outline" className={typeColors[ticket.type] ?? ''}>
                                                        {typeLabels[ticket.type] ?? ticket.type}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <Badge variant="outline" className={statusColors[ticket.status] ?? ''}>
                                                        {statusLabels[ticket.status] ?? ticket.status}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 pr-4 text-muted-foreground">{ticket.messages.length}</td>
                                                <td className="py-3 text-muted-foreground">
                                                    {new Date(ticket.created_at).toLocaleDateString()}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
