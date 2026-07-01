import { useForm } from '@inertiajs/react';
import { SendIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { store } from '@/actions/App/Http/Controllers/ChatController';
import { Button } from '@/components/ui/button';

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
        <div className="flex flex-col gap-2">
            <div className="relative">
                <textarea
                    ref={textareaRef}
                    value={data.question}
                    onChange={(e) => setData('question', e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder="Ask anything about your papers... (Ctrl/Cmd + Enter to send)"
                    rows={1}
                    disabled={processing}
                    aria-label="Chat question"
                    className="flex max-h-40 min-h-[60px] w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50"
                />
                <Button
                    type="button"
                    size="icon"
                    className="absolute right-2 bottom-2"
                    disabled={processing || !data.question.trim()}
                    onClick={submit}
                    aria-label="Send message"
                >
                    <SendIcon className="size-4" />
                </Button>
            </div>
            {errors.question && (
                <p className="text-sm text-destructive" role="alert">
                    {errors.question}
                </p>
            )}
        </div>
    );
}
