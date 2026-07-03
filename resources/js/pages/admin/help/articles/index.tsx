import { Head, Link } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import * as adminArticles from '@/routes/admin/help-articles';

interface Article {
    id: number;
    slug: string;
    title: string;
    status: string;
    sort: number;
    help_category: { id: number; title: string };
    created_at: string;
}

interface ArticlesIndexProps {
    articles: Article[];
}

export default function ArticlesIndex({ articles }: ArticlesIndexProps) {
    return (
        <>
            <Head title="Help Articles" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Help Articles</h1>
                    <Link href={adminArticles.create.url()}>
                        <Button>New Article</Button>
                    </Link>
                </div>
                <p className="text-muted-foreground">{articles.length} article(s)</p>
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b">
                            <tr>
                                <th className="px-4 py-2">Title</th>
                                <th className="px-4 py-2">Category</th>
                                <th className="px-4 py-2">Status</th>
                                <th className="px-4 py-2">Sort</th>
                                <th className="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {articles.map((article) => (
                                <tr key={article.id} className="border-b">
                                    <td className="px-4 py-2 font-medium">{article.title}</td>
                                    <td className="px-4 py-2 text-muted-foreground">{article.help_category?.title}</td>
                                    <td className="px-4 py-2">
                                        <Badge variant={article.status === 'published' ? 'default' : 'secondary'}>
                                            {article.status}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-2">{article.sort}</td>
                                    <td className="px-4 py-2">
                                        <div className="flex gap-2">
                                            <Link href={adminArticles.edit.url({ help_article: article.id })}>
                                                <Button variant="outline" size="sm">
                                                    Edit
                                                </Button>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
