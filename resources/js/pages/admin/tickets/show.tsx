import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, SendIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import AdminNav from '@/components/admin-nav';
import * as adminTickets from '@/routes/admin/tickets';

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
    const replyForm = useForm({ body: '' });
    const statusForm = useForm({ status: ticket.status });

    function onReply(e: React.FormEvent) {
        e.preventDefault();
        replyForm.post(adminTickets.reply.url({ ticket: ticket.id }), {
            onSuccess: () => replyForm.reset(),
        });
    }

    function onStatusChange(value: string) {
        statusForm.setData('status', value);
        statusForm.patch(adminTickets.status.url({ ticket: ticket.id }));
    }

    return (
        <>
            <Head title={ticket.subject} />
            <AdminNav />
            <div className="mx-auto max-w-4xl px-4 py-8">
                <Link
                    href={adminTickets.index.url()}
                    className="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeftIcon className="size-4" />
                    Back to all tickets
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

                {/* Status control */}
                <Card className="mb-6">
                    <CardContent className="flex items-center gap-4 py-4">
                        <Label htmlFor="status" className="shrink-0 text-sm font-medium">Status:</Label>
                        <Select value={ticket.status} onValueChange={onStatusChange}>
                            <SelectTrigger id="status" className="w-44">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="open">Open</SelectItem>
                                <SelectItem value="in_progress">In Progress</SelectItem>
                                <SelectItem value="resolved">Resolved</SelectItem>
                                <SelectItem value="closed">Closed</SelectItem>
                            </SelectContent>
                        </Select>
                        {statusForm.processing && (
                            <span className="text-xs text-muted-foreground">Updating...</span>
                        )}
                    </CardContent>
                </Card>

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
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Staff Reply</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={onReply} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="admin-reply-body" className="sr-only">Staff reply</Label>
                                <Textarea
                                    id="admin-reply-body"
                                    value={replyForm.data.body}
                                    onChange={(e) => replyForm.setData('body', e.target.value)}
                                    placeholder="Type your staff reply..."
                                    rows={4}
                                />
                                {replyForm.errors.body && (
                                    <p className="text-sm text-destructive">{replyForm.errors.body}</p>
                                )}
                            </div>
                            <div className="flex justify-end">
                                <Button type="submit" disabled={replyForm.processing || !replyForm.data.body.trim()}>
                                    <SendIcon />
                                    Send Reply
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
