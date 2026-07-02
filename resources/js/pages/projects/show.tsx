import { Head, usePage } from '@inertiajs/react';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import { ChatInput } from '@/components/chat-input';
import { ChatThread } from '@/components/chat-thread';
import type { CollectionColor } from '@/components/CollectionsList';
import { PaperCard } from '@/components/paper-card';
import { PromptDrawer } from '@/components/prompt-drawer';
import {
    CommandBar,
    DiscussionPanel,
    IconRail,
    LibraryHeader,
    ProjectSidebar,
    WorkspaceShell,
} from '@/components/workspace';
import { useState } from 'react';

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
    assistant: {
        model: string;
    };
    openalex: {
        corpusLabel: string;
    };
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
    assistant,
    openalex,
}: Props) {
    const [promptDrawerOpen, setPromptDrawerOpen] = useState(false);
    const [sortBy, setSortBy] = useState('date');
    const appName = usePage().props.name as string ?? 'ScholarGraph';

    // Sort papers
    const sortedPapers = [...papers].sort((a, b) => {
        switch (sortBy) {
            case 'citations':
                return (b.cited_by_count ?? 0) - (a.cited_by_count ?? 0);
            case 'year':
                return (b.year ?? 0) - (a.year ?? 0);
            case 'date':
            default:
                return new Date(b.pivot.added_at).getTime() - new Date(a.pivot.added_at).getTime();
        }
    });

    const handleFindPapers = () => {
        // Focus the search input in the command bar
        const searchInput = document.getElementById('command-bar-search');
        searchInput?.focus();
    };

    return (
        <>
            <Head title={project.name} />
            <WorkspaceShell
                rail={<IconRail appName={appName} />}
                sidebar={
                    <ProjectSidebar
                        projectName={project.name}
                        projectId={project.id}
                        collections={collections}
                        collectionColors={collectionColors}
                        hasSearched={savedOpenAlexIds.length > 0}
                        paperCount={papers.length}
                        chatCount={chatMessages.length}
                        onFindPapers={handleFindPapers}
                        onEditPrompt={() => setPromptDrawerOpen(true)}
                    />
                }
                library={
                    <>
                        <CommandBar
                            projectId={project.id}
                            savedOpenAlexIds={savedOpenAlexIds}
                            corpusLabel={openalex.corpusLabel}
                        />
                        <div className="flex-1 overflow-y-auto">
                            <LibraryHeader
                                paperCount={papers.length}
                                sortBy={sortBy}
                                onSortChange={setSortBy}
                            />
                            <p
                                className="mx-10 mt-3 max-w-[58ch] text-sm leading-relaxed"
                                style={{ color: 'var(--ws-muted)' }}
                            >
                                Sorted by {sortBy === 'date' ? 'date added' : sortBy}. Open a paper to read its AI summary, or ask the assistant to compare them.
                            </p>
                            <div className="mt-6 flex flex-col gap-4 px-10 pb-8">
                                {sortedPapers.length === 0 ? (
                                    <p className="text-sm" style={{ color: 'var(--ws-muted)' }}>
                                        No papers saved yet. Search and add papers to begin.
                                    </p>
                                ) : (
                                    sortedPapers.map((paper) => (
                                        <PaperCard
                                            key={paper.id}
                                            projectId={project.id}
                                            paper={paper}
                                            collections={collections}
                                        />
                                    ))
                                )}
                            </div>
                        </div>
                    </>
                }
                discussion={
                    <DiscussionPanel
                        assistantModel={assistant.model}
                        paperCount={papers.length}
                    >
                        <ChatThread messages={chatMessages} papers={papers} />
                        <ChatInput projectId={project.id} />
                    </DiscussionPanel>
                }
            />
            <PromptDrawer
                projectId={project.id}
                systemPrompt={project.system_prompt}
                useGlobalPrompt={project.use_global_prompt}
                globalSystemPrompt={globalSystemPrompt}
                globalNegativePrompt={globalNegativePrompt}
                negativePrompt={project.negative_prompt}
                open={promptDrawerOpen}
                onOpenChange={setPromptDrawerOpen}
            />
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
