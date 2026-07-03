import { Head, useForm } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Category {
    id: number;
    title: string;
}

interface Article {
    id: number;
    help_category_id: number;
    slug: string;
    title: string;
    content: { type: string; [key: string]: unknown }[] | null;
    status: string;
    sort: number;
}

interface EditArticleProps {
    article: Article;
    categories: Category[];
}

export default function EditArticle({ article, categories }: EditArticleProps) {
    const form = useForm({
        help_category_id: article.help_category_id,
        slug: article.slug,
        title: article.title,
        content: article.content ?? [],
        sort: article.sort,
    });

    function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.put(route('admin.help-articles.update', article.id));
    }

    return (
        <>
            <Head title={`Edit: ${article.title}`} />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">Edit: {article.title}</h1>
                <form onSubmit={onSubmit} className="max-w-lg space-y-4">
                    <div>
                        <Label htmlFor="help_category_id">Category</Label>
                        <select
                            id="help_category_id"
                            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            value={form.help_category_id}
                            onChange={(e) => form.setData('help_category_id', e.target.value)}
                        >
                            {categories.map((cat) => (
                                <option key={cat.id} value={cat.id}>
                                    {cat.title}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={form.title}
                            onChange={(e) => form.setData('title', e.target.value)}
                        />
                        {form.errors.title && <p className="mt-1 text-sm text-destructive">{form.errors.title}</p>}
                    </div>
                    <div>
                        <Label htmlFor="slug">Slug</Label>
                        <Input
                            id="slug"
                            value={form.slug}
                            onChange={(e) => form.setData('slug', e.target.value)}
                        />
                        {form.errors.slug && <p className="mt-1 text-sm text-destructive">{form.errors.slug}</p>}
                    </div>
                    <div>
                        <Label htmlFor="sort">Sort Order</Label>
                        <Input
                            id="sort"
                            type="number"
                            value={form.sort}
                            onChange={(e) => form.setData('sort', Number(e.target.value))}
                        />
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        Update Article
                    </Button>
                </form>
            </div>
        </>
    );
}
