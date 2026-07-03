import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import * as supportTickets from '@/routes/support/tickets';

interface Props {
    errors?: Record<string, string>;
}

export default function Create({ errors }: Props) {
    const { data, setData, post, processing, errors: formErrors } = useForm({
        type: '',
        subject: '',
        body: '',
    });

    function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(supportTickets.store.url());
    }

    const combinedErrors = { ...errors, ...formErrors };

    return (
        <>
            <Head title="New Ticket" />
            <div className="mx-auto max-w-2xl px-4 py-8">
                <Link
                    href={supportTickets.index.url()}
                    className="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeftIcon className="size-4" />
                    Back to tickets
                </Link>

                <h1 className="mb-6 text-2xl font-bold">New Support Ticket</h1>

                <Card>
                    <CardHeader>
                        <CardTitle>Describe your issue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={onSubmit} className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) => setData('type', value)}
                                >
                                    <SelectTrigger id="type" className="w-full">
                                        <SelectValue placeholder="Select a type..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="bug">Bug</SelectItem>
                                        <SelectItem value="feature">Feature Request</SelectItem>
                                        <SelectItem value="support">Support</SelectItem>
                                        <SelectItem value="billing">Billing</SelectItem>
                                    </SelectContent>
                                </Select>
                                {combinedErrors.type && (
                                    <p className="text-sm text-destructive">{combinedErrors.type}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="subject">Subject</Label>
                                <Input
                                    id="subject"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="Brief summary of your issue"
                                />
                                {combinedErrors.subject && (
                                    <p className="text-sm text-destructive">{combinedErrors.subject}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="body">Description</Label>
                                <Textarea
                                    id="body"
                                    value={data.body}
                                    onChange={(e) => setData('body', e.target.value)}
                                    placeholder="Provide as much detail as possible..."
                                    rows={6}
                                />
                                {combinedErrors.body && (
                                    <p className="text-sm text-destructive">{combinedErrors.body}</p>
                                )}
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Submit Ticket
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
