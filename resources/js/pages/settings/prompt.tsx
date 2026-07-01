import { Head, useForm } from '@inertiajs/react';
import { ChevronDownIcon, ChevronUpIcon } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { update as promptSettingsUpdate } from '@/routes/prompt';

interface Props {
    globalSystemPrompt: string | null;
    globalNegativePrompt: string | null;
    defaultPrompt: string;
}

export default function PromptSettings({
    globalSystemPrompt,
    globalNegativePrompt,
    defaultPrompt,
}: Props) {
    const [showDefault, setShowDefault] = useState(false);
    const { data, setData, put, processing } = useForm({
        global_system_prompt: globalSystemPrompt ?? '',
        global_negative_prompt: globalNegativePrompt ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        put(promptSettingsUpdate.url(), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Prompt settings" />

            <h1 className="sr-only">Prompt settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Global system prompt"
                    description="This prompt is used across all your projects when enabled. It instructs the AI on how to respond."
                />

                <form onSubmit={submit} className="space-y-6">
                    <div>
                        <label
                            htmlFor="global-prompt"
                            className="mb-1 block text-sm font-medium"
                        >
                            Global System Prompt
                        </label>
                        <Textarea
                            id="global-prompt"
                            value={data.global_system_prompt}
                            onChange={(e) =>
                                setData('global_system_prompt', e.target.value)
                            }
                            placeholder="You are a helpful research assistant..."
                            className="min-h-[200px] resize-y"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Leave empty to use the default system prompt for each
                            project.
                        </p>
                    </div>

                    <div>
                        <label
                            htmlFor="global-negative-prompt"
                            className="mb-1 block text-sm font-medium"
                        >
                            Global Negative Prompt
                        </label>
                        <Textarea
                            id="global-negative-prompt"
                            value={data.global_negative_prompt}
                            onChange={(e) =>
                                setData('global_negative_prompt', e.target.value)
                            }
                            placeholder="e.g., Do not use bullet points. Do not include a references section."
                            className="min-h-[120px] resize-y"
                        />
                        <p className="mt-1 text-xs text-muted-foreground">
                            Instructions for what the AI should NOT do. Applied across all projects.
                        </p>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Prompts'}
                        </Button>
                    </div>
                </form>

                <div className="rounded-lg border bg-muted/50 p-4">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setShowDefault(!showDefault)}
                        className="gap-1 px-0 text-muted-foreground hover:text-foreground"
                    >
                        {showDefault ? (
                            <ChevronUpIcon className="size-4" />
                        ) : (
                            <ChevronDownIcon className="size-4" />
                        )}
                        Preview default system prompt
                    </Button>
                    {showDefault && (
                        <pre className="mt-2 whitespace-pre-wrap rounded-md bg-muted p-3 text-xs text-muted-foreground">
                            {defaultPrompt}
                        </pre>
                    )}
                </div>
            </div>
        </>
    );
}

PromptSettings.layout = {
    breadcrumbs: [
        {
            title: 'Prompt settings',
            href: '/settings/prompt',
        },
    ],
};
