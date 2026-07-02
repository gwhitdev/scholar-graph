import { Head, usePage } from '@inertiajs/react';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import { ChatInput } from '@/components/chat-input';
import { ChatThread } from '@/components/chat-thread';
import type { CollectionColor } from '@/components/CollectionsList';
import { PaperCard } from '@/components/paper-card';
import { PromptDrawer } from '@/components/prompt-drawer';
import {
    CommandBar,
    ContentHeader,
    DiscussionPanel,
    IconRail,
    LibraryHeader,
    ProjectSidebar,
    WorkspaceShell,
} from '@/components/workspace';
import type { PanelKey } from '@/components/workspace/IconRail';
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

interface AllProject {
    id: number;
    name: string;
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
    allProjects: AllProject[];
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
    allProjects,
}: Props) {
    const [promptDrawerOpen, setPromptDrawerOpen] = useState(false);
    const [sortBy, setSortBy] = useState('date');
    const [activePanel, setActivePanel] = useState<PanelKey | null>(null);
    const [discussionExpanded, setDiscussionExpanded] = useState(false);
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

    // Calculate total citations
    const totalCitations = papers.reduce((sum, p) => sum + (p.cited_by_count ?? 0), 0);

    const handlePanelToggle = (panel: PanelKey) => {
        setActivePanel((prev) => (prev === panel ? null : panel));
    };

    const handleFindPapers = () => {
        // Focus the search input in the command bar
        const searchInput = document.getElementById('command-bar-search');
        searchInput?.focus();
    };

    return (
        <>
            <Head title={project.name} />
            <WorkspaceShell
                rail={
                    <IconRail
                        appName={appName}
                        activePanel={activePanel}
                        onPanelToggle={handlePanelToggle}
                    />
                }
                sidebar={
                    <ProjectSidebar
                        projectName={project.name}
                        projectId={project.id}
                        allProjects={allProjects}
                        collections={collections}
                        collectionColors={collectionColors}
                        onFindPapers={handleFindPapers}
                        onEditPrompt={() => setPromptDrawerOpen(true)}
                        onClose={() => setActivePanel(null)}
                    />
                }
                sidebarOpen={activePanel !== null}
                onSidebarClose={() => setActivePanel(null)}
                library={
                    <>
                        <ContentHeader
                            projectName={project.name}
                            hasSearched={savedOpenAlexIds.length > 0}
                            paperCount={papers.length}
                            chatCount={chatMessages.length}
                            onEditPrompt={() => setPromptDrawerOpen(true)}
                        />
                        <CommandBar
                            projectId={project.id}
                            savedOpenAlexIds={savedOpenAlexIds}
                            corpusLabel={openalex.corpusLabel}
                        />
                        <div className="flex flex-1 flex-col overflow-y-auto">
                            <LibraryHeader
                                paperCount={papers.length}
                                totalCitations={totalCitations}
                                sortBy={sortBy}
                                onSortChange={setSortBy}
                            />
                            <div className="flex flex-1 flex-col px-8 pb-8">
                                {sortedPapers.length === 0 ? (
                                    <p className="mt-6 text-sm" style={{ color: 'var(--ws-muted)' }}>
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
                        expanded={discussionExpanded}
                        onToggleExpand={() => setDiscussionExpanded((prev) => !prev)}
                    >
                        <ChatThread messages={chatMessages} papers={papers} />
                        <ChatInput projectId={project.id} />
                    </DiscussionPanel>
                }
                discussionExpanded={discussionExpanded}
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
