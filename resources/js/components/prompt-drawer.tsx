import { useForm } from '@inertiajs/react';
import { ChevronDownIcon, ChevronUpIcon, SettingsIcon, XIcon } from 'lucide-react';
import { useState } from 'react';
import { update as promptUpdate } from '@/actions/App/Http/Controllers/PromptController';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { suggestedNegativePrompts, suggestedSystemPrompts } from '@/lib/prompt-suggestions';

interface PromptDrawerProps {
    projectId: number;
    systemPrompt: string | null;
    useGlobalPrompt: boolean;
    globalSystemPrompt: string | null;
    globalNegativePrompt: string | null;
    negativePrompt: string | null;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

export function PromptDrawer({
    projectId,
    systemPrompt,
    useGlobalPrompt,
    globalSystemPrompt,
    globalNegativePrompt,
    negativePrompt,
    open: controlledOpen,
    onOpenChange,
}: PromptDrawerProps) {
    const [internalOpen, setIsOpenInternal] = useState(false);
    const isControlled = controlledOpen !== undefined;
    const isOpen = isControlled ? controlledOpen : internalOpen;

    function setIsOpen(value: boolean) {
        if (isControlled) {
            onOpenChange?.(value);
        } else {
            setIsOpenInternal(value);
        }
    }
    const [showSuggestions, setShowSuggestions] = useState(false);
    const { data, setData, put, processing } = useForm({
        system_prompt: systemPrompt ?? '',
        use_global_prompt: useGlobalPrompt,
        negative_prompt: negativePrompt ?? '',
    });

    function submit() {
        put(promptUpdate.url(projectId), {
            preserveScroll: true,
        });
    }

    function appendSystemPrompt(prompt: string) {
        const current = data.system_prompt.trim();
        setData('system_prompt', current ? `${current}\n\n${prompt}` : prompt);
    }

    function appendNegativePrompt(prompt: string) {
        const current = data.negative_prompt.trim();
        setData('negative_prompt', current ? `${current}\n${prompt}` : prompt);
    }

    return (
        <div className="relative w-full">
            <Button
                variant="outline"
                size="sm"
                onClick={() => setIsOpen(!isOpen)}
                className="gap-2"
            >
                <SettingsIcon className="size-4" />
                Edit Prompt
                {isOpen ? (
                    <ChevronUpIcon className="size-4" />
                ) : (
                    <ChevronDownIcon className="size-4" />
                )}
            </Button>

            {isOpen && (
                <>
                    {/* Backdrop */}
                    <div
                        className="fixed inset-0 z-40 bg-black/50"
                        onClick={() => setIsOpen(false)}
                    />

                    {/* Drawer */}
                    <div className="absolute left-0 right-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-lg border bg-card p-4 shadow-lg">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-sm font-semibold">Prompt Settings</h3>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="size-6"
                                onClick={() => setIsOpen(false)}
                            >
                                <XIcon className="size-4" />
                            </Button>
                        </div>

                        {/* Global prompt toggle */}
                        <div className="mb-4 flex items-center gap-2">
                            <Checkbox
                                id="use-global"
                                checked={data.use_global_prompt}
                                onCheckedChange={(checked) =>
                                    setData('use_global_prompt', !!checked)
                                }
                            />
                            <label
                                htmlFor="use-global"
                                className="text-sm font-medium"
                            >
                                Include global prompt
                            </label>
                        </div>

                        {/* Global prompt display (read-only) */}
                        {data.use_global_prompt && globalSystemPrompt && (
                            <div className="mb-4">
                                <label className="mb-1 block text-sm font-medium text-muted-foreground">
                                    Global Prompt (read-only)
                                </label>
                                <p className="mb-1 text-xs text-muted-foreground">
                                    Edit this in{' '}
                                    <a
                                        href="/settings/prompt"
                                        className="underline"
                                        target="_blank"
                                    >
                                        Settings
                                    </a>
                                    . It will be combined with your project prompt below.
                                </p>
                                <div className="max-h-[100px] overflow-y-auto rounded-md bg-muted p-3 text-sm text-muted-foreground">
                                    {globalSystemPrompt}
                                </div>
                            </div>
                        )}

                        {/* Global negative prompt display (read-only) */}
                        {data.use_global_prompt && globalNegativePrompt && (
                            <div className="mb-4">
                                <label className="mb-1 block text-sm font-medium text-muted-foreground">
                                    Global Negative Prompt (read-only)
                                </label>
                                <div className="max-h-[80px] overflow-y-auto rounded-md bg-muted p-3 text-sm text-muted-foreground">
                                    {globalNegativePrompt}
                                </div>
                            </div>
                        )}

                        {/* Project-specific prompt */}
                        <div className="mb-4">
                            <label className="mb-1 block text-sm font-medium text-muted-foreground">
                                Project-Specific Prompt
                            </label>
                            <p className="mb-2 text-xs text-muted-foreground">
                                {data.use_global_prompt
                                    ? 'This will be appended to the global prompt above.'
                                    : 'This prompt applies only to this project.'}
                            </p>
                            <Textarea
                                value={data.system_prompt}
                                onChange={(e) =>
                                    setData('system_prompt', e.target.value)
                                }
                                placeholder="Enter a custom system prompt for this project..."
                                className="min-h-[100px] resize-y"
                            />
                        </div>

                        {/* Negative prompt */}
                        <div className="mb-4">
                            <label className="mb-1 block text-sm font-medium text-muted-foreground">
                                Negative Prompt
                            </label>
                            <p className="mb-2 text-xs text-muted-foreground">
                                Instructions for what the AI should NOT do.
                            </p>
                            <Textarea
                                value={data.negative_prompt}
                                onChange={(e) =>
                                    setData('negative_prompt', e.target.value)
                                }
                                placeholder="e.g., Do not use bullet points. Do not include a references section."
                                className="min-h-[80px] resize-y"
                            />
                        </div>

                        {/* Suggestions toggle */}
                        <div className="mb-4 border-t pt-4">
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setShowSuggestions(!showSuggestions)}
                                className="gap-1 px-0 text-muted-foreground hover:text-foreground"
                            >
                                {showSuggestions ? (
                                    <ChevronUpIcon className="size-4" />
                                ) : (
                                    <ChevronDownIcon className="size-4" />
                                )}
                                Suggested prompts
                            </Button>

                            {showSuggestions && (
                                <div className="mt-3 space-y-4">
                                    {/* System prompt suggestions */}
                                    <div>
                                        <p className="mb-2 text-xs font-medium text-muted-foreground">
                                            Click to add to your prompt:
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {suggestedSystemPrompts.map((suggestion) => (
                                                <button
                                                    key={suggestion.label}
                                                    type="button"
                                                    onClick={() =>
                                                        appendSystemPrompt(suggestion.prompt)
                                                    }
                                                    className="rounded-full border bg-background px-3 py-1 text-left text-xs transition-colors hover:border-primary hover:bg-primary/5"
                                                    title={suggestion.description}
                                                >
                                                    <span className="font-medium">
                                                        {suggestion.label}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Negative prompt suggestions */}
                                    <div>
                                        <p className="mb-2 text-xs font-medium text-muted-foreground">
                                            Click to add to negative prompt:
                                        </p>
                                        <div className="flex flex-wrap gap-2">
                                            {suggestedNegativePrompts.map((suggestion) => (
                                                <button
                                                    key={suggestion.label}
                                                    type="button"
                                                    onClick={() =>
                                                        appendNegativePrompt(suggestion.prompt)
                                                    }
                                                    className="rounded-full border bg-background px-3 py-1 text-left text-xs transition-colors hover:border-destructive hover:bg-destructive/5"
                                                    title={suggestion.description}
                                                >
                                                    <span className="font-medium">
                                                        {suggestion.label}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="flex justify-end">
                            <Button
                                onClick={submit}
                                disabled={processing}
                                size="sm"
                            >
                                {processing ? 'Saving...' : 'Save Prompt'}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}
