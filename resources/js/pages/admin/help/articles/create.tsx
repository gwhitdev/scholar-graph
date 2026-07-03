import { Head, useForm } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as adminArticles from '@/routes/admin/help-articles';

interface Category {
    id: number;
    title: string;
}

interface CreateArticleProps {
    categories: Category[];
}

export default function CreateArticle({ categories }: CreateArticleProps) {
    const form = useForm({
        help_category_id: categories[0]?.id ?? '',
        slug: '',
        title: '',
        content: [],
        sort: 0,
    });

    function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(adminArticles.store.url());
    }

    return (
        <>
            <Head title="New Help Article" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">New Help Article</h1>
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
                        Create Article
                    </Button>
                </form>
            </div>
        </>
    );
}
