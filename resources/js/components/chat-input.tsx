import { useForm } from '@inertiajs/react';
import { Loader2Icon } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { store } from '@/actions/App/Http/Controllers/ChatController';

interface ChatInputProps {
    projectId: number;
}

export function ChatInput({ projectId }: ChatInputProps) {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        question: '',
    });

    function submit() {
        if (!data.question.trim() || processing) {
            return;
        }

        post(store.url(projectId), {
            preserveScroll: true,
            onSuccess: () => reset('question'),
        });
    }

    function handleKeyDown(event: React.KeyboardEvent<HTMLTextAreaElement>) {
        if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
            event.preventDefault();
            submit();
        }
    }

    useEffect(() => {
        const textarea = textareaRef.current;

        if (!textarea) {
            return;
        }

        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;
    }, [data.question]);

    return (
        <div className="shrink-0 px-4 pt-3 pb-4">
            <div
                className="rounded-[13px] border px-3.5 py-3"
                style={{
                    background: 'var(--ws-panel2)',
                    borderColor: 'var(--ws-line)',
                }}
            >
                <textarea
                    ref={textareaRef}
                    value={data.question}
                    onChange={(e) => setData('question', e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder="Ask anything about your papers…"
                    rows={1}
                    disabled={processing}
                    aria-label="Chat question"
                    className="max-h-40 w-full resize-none border-none bg-transparent text-[13.5px] leading-relaxed outline-none placeholder:text-[var(--ws-faint)]"
                    style={{ color: 'var(--ws-fg)' }}
                />
                <div className="mt-4 flex items-center justify-between">
                    <span
                        className="font-mono text-[11px]"
                        style={{ color: 'var(--ws-faint)' }}
                    >
                        ⌘ + ↵ to send
                    </span>
                    <button
                        type="button"
                        onClick={submit}
                        disabled={processing || !data.question.trim()}
                        className="flex size-[30px] items-center justify-center rounded-[9px] transition-opacity hover:opacity-90 disabled:opacity-40"
                        style={{
                            background: 'var(--ws-accent)',
                            color: 'var(--ws-onacc)',
                        }}
                        aria-label="Send message"
                    >
                        {processing ? (
                            <Loader2Icon className="size-4 animate-spin" />
                        ) : (
                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 16V4M10 4l-5 5M10 4l5 5"
                                    stroke="currentColor"
                                    strokeWidth="1.7"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        )}
                    </button>
                </div>
            </div>
            {errors.question && (
                <p className="mt-1 text-sm text-destructive" role="alert">
                    {errors.question}
                </p>
            )}
        </div>
    );
}
