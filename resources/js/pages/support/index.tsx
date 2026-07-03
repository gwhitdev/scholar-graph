import { Head, Link } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Ticket {
    id: number;
    type: string;
    subject: string;
    status: string;
    messages_count: number;
    created_at: string;
}

interface Props {
    tickets: Ticket[];
    flash?: { success?: string };
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

export default function Index({ tickets, flash }: Props) {
    return (
        <>
            <Head title="Support Tickets" />
            <div className="mx-auto max-w-4xl px-4 py-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Support Tickets</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {tickets.length} ticket{tickets.length !== 1 ? 's' : ''}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route('support.tickets.create')}>
                            <PlusIcon />
                            New Ticket
                        </Link>
                    </Button>
                </div>

                {flash?.success && (
                    <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                        {flash.success}
                    </div>
                )}

                {tickets.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center py-12 text-center">
                            <p className="text-muted-foreground">No tickets yet.</p>
                            <Button className="mt-4" asChild>
                                <Link href={route('support.tickets.create')}>Create your first ticket</Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Your Tickets</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {tickets.map((ticket) => (
                                <Link
                                    key={ticket.id}
                                    href={route('support.tickets.show', ticket.id)}
                                    className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">{ticket.subject}</p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {new Date(ticket.created_at).toLocaleDateString()} · {ticket.messages_count} message{ticket.messages_count !== 1 ? 's' : ''}
                                        </p>
                                    </div>
                                    <div className="ml-4 flex shrink-0 items-center gap-2">
                                        <Badge variant="outline" className={typeColors[ticket.type] ?? ''}>
                                            {typeLabels[ticket.type] ?? ticket.type}
                                        </Badge>
                                        <Badge variant="outline" className={statusColors[ticket.status] ?? ''}>
                                            {statusLabels[ticket.status] ?? ticket.status}
                                        </Badge>
                                    </div>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
