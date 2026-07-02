import { Head } from '@inertiajs/react';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import { ChatInput } from '@/components/chat-input';
import { ChatThread } from '@/components/chat-thread';
import { CollectionsList  } from '@/components/CollectionsList';
import type {CollectionColor} from '@/components/CollectionsList';
import { PaperCard } from '@/components/paper-card';
import { PaperSearch } from '@/components/paper-search';
import { PromptDrawer } from '@/components/prompt-drawer';

interface Paper {
    id: number;
    openalex_id: string | null;
    title: string;
    abstract: string | null;
    year: number | null;
    authors: string[] | null;
    doi: string | null;
    venue: string | null;
    pages: string | null;
    cited_by_count: number | null;
    pivot: {
        status: string;
        added_at: string;
    };
    enrichment?: {
        tldr: string | null;
        tldr_source: 'semantic_scholar' | 'generated' | null;
        influential_citation_count: number | null;
        enriched_at: string | null;
    } | null;
}

interface ChatMessage {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    synthesis?: {
        paper_ids: number[] | null;
    } | null;
}

interface Synthesis {
    id: number;
    question: string;
    answer: string;
    paper_ids: number[] | null;
    model_used: string | null;
    created_at: string;
}

interface Collection {
    id: number;
    name: string;
    color: CollectionColor;
    papers: { id: number }[];
}

interface Project {
    id: number;
    name: string;
    system_prompt: string | null;
    use_global_prompt: boolean;
    negative_prompt: string | null;
}

interface Props {
    project: Project;
    papers: Paper[];
    savedOpenAlexIds: string[];
    chatMessages: ChatMessage[];
    syntheses: Synthesis[];
    collections: Collection[];
    collectionColors: CollectionColor[];
    globalSystemPrompt: string | null;
    globalNegativePrompt: string | null;
}

export default function ProjectsShow({
    project,
    papers,
    savedOpenAlexIds,
    chatMessages,
    collections,
    collectionColors,
    globalSystemPrompt,
    globalNegativePrompt,
}: Props) {
    return (
        <>
            <Head title={project.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{project.name}</h1>
                    <PromptDrawer
                        projectId={project.id}
                        systemPrompt={project.system_prompt}
                        useGlobalPrompt={project.use_global_prompt}
                        globalSystemPrompt={globalSystemPrompt}
                        globalNegativePrompt={globalNegativePrompt}
                        negativePrompt={project.negative_prompt}
                    />
                </div>

                <div className="grid min-h-0 flex-1 gap-4 md:grid-cols-2">
                    <div className="flex min-h-0 flex-col gap-4">
                        <PaperSearch
                            projectId={project.id}
                            savedOpenAlexIds={savedOpenAlexIds}
                        />

                        <div className="flex flex-1 flex-col gap-2 overflow-y-auto rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                            <h2 className="text-sm font-medium">
                                Saved Papers
                            </h2>
                            {papers.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No papers saved yet. Search and add papers
                                    to begin.
                                </p>
                            ) : (
                                papers.map((paper) => (
                                    <PaperCard
                                        key={paper.id}
                                        projectId={project.id}
                                        paper={paper}
                                        collections={collections}
                                    />
                                ))
                            )}
                        </div>

                        <CollectionsList
                            projectId={project.id}
                            collections={collections}
                            collectionColors={collectionColors}
                        />
                    </div>

                    <div className="flex min-h-0 flex-col gap-4">
                        <ChatThread messages={chatMessages} papers={papers} />
                        <ChatInput projectId={project.id} />
                    </div>
                </div>
            </div>
        </>
    );
}

ProjectsShow.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: projectsIndex(),
        },
        {
            title: 'Project',
        },
    ],
};
