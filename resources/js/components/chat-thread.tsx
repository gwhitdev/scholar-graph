import { useEffect, useRef } from 'react';
import ReactMarkdown from 'react-markdown';
import { SourcesBadge } from '@/components/sources-badge';

interface Paper {
    id: number;
    title: string;
    authors: string[] | null;
    doi: string | null;
    venue: string | null;
    pages: string | null;
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
        <div
            ref={scrollRef}
            className="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto px-5 py-5"
        >
            {messages.length === 0 && (
                <p className="text-[13.5px]" style={{ color: 'var(--ws-faint)' }}>
                    Ask anything about your papers to get started.
                </p>
            )}

            {messages.map((message) => {
                const isUser = message.role === 'user';

                if (isUser) {
                    return (
                        <div
                            key={message.id}
                            className="max-w-[84%] self-end rounded-[14px_14px_4px_14px] px-3.5 py-2.5 text-[13.5px] leading-relaxed"
                            style={{
                                background: 'var(--ws-soft)',
                                color: 'var(--ws-fg)',
                            }}
                        >
                            {message.content}
                        </div>
                    );
                }

                return (
                    <div key={message.id} className="max-w-[90%] self-start">
                        <div className="mb-2 flex items-center gap-2">
                            <span
                                className="flex size-[22px] items-center justify-center rounded-[7px] font-serif text-[13px] font-semibold"
                                style={{
                                    background: 'var(--ws-accent)',
                                    color: 'var(--ws-onacc)',
                                }}
                                aria-hidden="true"
                            >
                                S
                            </span>
                            <span
                                className="text-xs font-semibold"
                                style={{ color: 'var(--ws-muted)' }}
                            >
                                Assistant
                            </span>
                        </div>
                        <div
                            className="rounded-[4px_14px_14px_14px] border px-4 py-3 text-[13.5px] leading-relaxed"
                            style={{
                                background: 'var(--ws-panel2)',
                                borderColor: 'var(--ws-line)',
                                color: 'var(--ws-fg)',
                            }}
                        >
                            <div className="prose prose-sm dark:prose-invert max-w-none prose-headings:mt-2 prose-headings:mb-1 prose-p:my-1 prose-ul:my-1 prose-ol:my-1 prose-li:my-0.5 prose-pre:my-1">
                                <ReactMarkdown
                                    components={{
                                        p: ({ children }) => (
                                            <p className="my-1.5 last:mb-0">
                                                {children}
                                            </p>
                                        ),
                                        h2: ({ children }) => (
                                            <h2 className="mt-3 mb-1 text-base font-semibold">
                                                {children}
                                            </h2>
                                        ),
                                        h3: ({ children }) => (
                                            <h3 className="mt-2 mb-1 text-sm font-semibold">
                                                {children}
                                            </h3>
                                        ),
                                        ul: ({ children }) => (
                                            <ul className="my-1.5 ml-4 list-disc">
                                                {children}
                                            </ul>
                                        ),
                                        ol: ({ children }) => (
                                            <ol className="my-1.5 ml-4 list-decimal">
                                                {children}
                                            </ol>
                                        ),
                                    }}
                                >
                                    {message.content}
                                </ReactMarkdown>
                            </div>
                        </div>
                        {message.synthesis && (
                            <div className="mt-2">
                                <SourcesBadge
                                    paperIds={message.synthesis.paper_ids}
                                    papers={papers}
                                />
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
