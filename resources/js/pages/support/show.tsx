import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, SendIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

interface Ticket {
    id: number;
    type: string;
    subject: string;
    body: string;
    status: string;
    created_at: string;
    user: { id: number; name: string };
    messages: Array<{
        id: number;
        body: string;
        is_staff: boolean;
        user: { id: number; name: string };
        created_at: string;
    }>;
}

interface Props {
    ticket: Ticket;
    flash?: { success?: string };
}

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

export default function Show({ ticket, flash }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        body: '',
    });

    function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('support.tickets.reply', ticket.id), {
            onSuccess: () => reset(),
        });
    }

    return (
        <>
            <Head title={ticket.subject} />
            <div className="mx-auto max-w-4xl px-4 py-8">
                <Link
                    href={route('support.tickets.index')}
                    className="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeftIcon className="size-4" />
                    Back to tickets
                </Link>

                {flash?.success && (
                    <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                        {flash.success}
                    </div>
                )}

                {/* Ticket header */}
                <div className="mb-6">
                    <div className="mb-2 flex items-center gap-2">
                        <Badge variant="outline" className={statusColors[ticket.status] ?? ''}>
                            {statusLabels[ticket.status] ?? ticket.status}
                        </Badge>
                        <span className="text-sm text-muted-foreground">{typeLabels[ticket.type] ?? ticket.type}</span>
                    </div>
                    <h1 className="text-2xl font-bold">{ticket.subject}</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Opened by {ticket.user.name} on {new Date(ticket.created_at).toLocaleDateString()}
                    </p>
                </div>

                {/* Message thread */}
                <div className="space-y-4">
                    {ticket.messages.map((msg) => (
                        <div
                            key={msg.id}
                            className={cn(
                                'rounded-lg border p-4',
                                msg.is_staff
                                    ? 'border-primary/30 bg-primary/5'
                                    : 'bg-background',
                            )}
                        >
                            <div className="mb-2 flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <span className="font-medium text-sm">{msg.user.name}</span>
                                    {msg.is_staff && (
                                        <Badge variant="default" className="text-xs">Staff</Badge>
                                    )}
                                </div>
                                <time className="text-xs text-muted-foreground">
                                    {new Date(msg.created_at).toLocaleString()}
                                </time>
                            </div>
                            <p className="whitespace-pre-wrap text-sm">{msg.body}</p>
                        </div>
                    ))}
                </div>

                {/* Reply form */}
                {ticket.status !== 'closed' && (
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle>Reply</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={onSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="reply-body" className="sr-only">Your reply</Label>
                                    <Textarea
                                        id="reply-body"
                                        value={data.body}
                                        onChange={(e) => setData('body', e.target.value)}
                                        placeholder="Type your reply..."
                                        rows={4}
                                    />
                                    {errors.body && (
                                        <p className="text-sm text-destructive">{errors.body}</p>
                                    )}
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={processing || !data.body.trim()}>
                                        <SendIcon />
                                        Send Reply
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
