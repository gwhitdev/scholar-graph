import { Head } from '@inertiajs/react';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/ProjectController';
import { ChatInput } from '@/components/chat-input';
import { ChatThread } from '@/components/chat-thread';
import { PaperCard } from '@/components/paper-card';
import { PaperSearch } from '@/components/paper-search';

interface Paper {
    id: number;
    semantic_scholar_id: string | null;
    title: string;
    abstract: string | null;
    year: number | null;
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

interface Project {
    id: number;
    name: string;
}

interface Props {
    project: Project;
    papers: Paper[];
    chatMessages: ChatMessage[];
    syntheses: Synthesis[];
}

export default function ProjectsShow({ project, papers, chatMessages }: Props) {
    return (
        <>
            <Head title={project.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <h1 className="text-2xl font-semibold">{project.name}</h1>

                <div className="grid min-h-0 flex-1 gap-4 md:grid-cols-2">
                    <div className="flex min-h-0 flex-col gap-4">
                        <PaperSearch projectId={project.id} />

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
                                    />
                                ))
                            )}
                        </div>
                    </div>

                    <div className="flex min-h-0 flex-col gap-4">
                        <ChatThread messages={chatMessages} />
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
