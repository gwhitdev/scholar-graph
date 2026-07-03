import { Head, Link } from '@inertiajs/react';
import BlockRenderer from '@/components/block-renderer';

interface ArticleData {
    id: number;
    slug: string;
    title: string;
    content: { type: string; [key: string]: unknown }[] | null;
    status: string;
}

interface CategoryData {
    id: number;
    slug: string;
    title: string;
}

interface HelpShowProps {
    category: CategoryData;
    article: ArticleData;
}

export default function HelpShow({ category, article }: HelpShowProps) {
    return (
        <>
            <Head title={article.title} />
            <main className="mx-auto max-w-4xl px-4 py-12">
                <nav className="mb-6 text-sm text-muted-foreground" aria-label="Breadcrumb">
                    <Link href={route('help.index')} className="hover:underline">
                        Help
                    </Link>
                    <span className="mx-2">/</span>
                    <span>{category.title}</span>
                </nav>

                <h1 className="mb-8 text-3xl font-bold">{article.title}</h1>
                <BlockRenderer blocks={article.content} />
            </main>
        </>
    );
}
