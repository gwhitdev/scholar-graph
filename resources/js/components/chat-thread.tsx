import { UserIcon, BotIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { SourcesBadge } from '@/components/sources-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Paper {
    id: number;
    title: string;
}

interface ChatMessage {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    synthesis?: {
        paper_ids: number[] | null;
    } | null;
}

interface ChatThreadProps {
    messages: ChatMessage[];
    papers: Paper[];
}

export function ChatThread({ messages, papers }: ChatThreadProps) {
    const scrollRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
        }
    }, [messages]);

    return (
        <Card className="flex flex-1 flex-col">
            <CardHeader>
                <CardTitle>Discussion</CardTitle>
            </CardHeader>
            <CardContent
                ref={scrollRef}
                className="flex flex-1 flex-col gap-4 overflow-y-auto"
            >
                {messages.length === 0 && (
                    <p className="text-sm text-muted-foreground">
                        Ask a question about your papers to get started.
                    </p>
                )}

                {messages.map((message) => {
                    const isUser = message.role === 'user';

                    return (
                        <div
                            key={message.id}
                            className={`flex gap-3 ${isUser ? 'flex-row-reverse' : ''}`}
                        >
                            <div
                                className={`flex size-8 shrink-0 items-center justify-center rounded-full border ${
                                    isUser
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-muted-foreground'
                                }`}
                                aria-hidden="true"
                            >
                                {isUser ? (
                                    <UserIcon className="size-4" />
                                ) : (
                                    <BotIcon className="size-4" />
                                )}
                            </div>
                            <div
                                className={`flex max-w-[80%] flex-col gap-1 ${
                                    isUser ? 'items-end' : 'items-start'
                                }`}
                            >
                                <div
                                    className={`rounded-lg px-4 py-2 text-sm ${
                                        isUser
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted text-muted-foreground'
                                    }`}
                                >
                                    {message.content}
                                </div>
                                {!isUser && message.synthesis && (
                                    <SourcesBadge
                                        paperIds={
                                            message.synthesis.paper_ids
                                        }
                                        papers={papers}
                                    />
                                )}
                            </div>
                        </div>
                    );
                })}
            </CardContent>
        </Card>
    );
}
