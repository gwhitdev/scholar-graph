import { Head, Link } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import * as adminCategories from '@/routes/admin/help-categories';

interface Category {
    id: number;
    slug: string;
    title: string;
    sort: number;
    articles_count: number;
    created_at: string;
}

interface CategoriesIndexProps {
    categories: Category[];
}

export default function CategoriesIndex({ categories }: CategoriesIndexProps) {
    return (
        <>
            <Head title="Help Categories" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Help Categories</h1>
                    <Link href={adminCategories.create.url()}>
                        <Button>New Category</Button>
                    </Link>
                </div>
                <p className="text-muted-foreground">{categories.length} category(ies)</p>
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b">
                            <tr>
                                <th className="px-4 py-2">Title</th>
                                <th className="px-4 py-2">Slug</th>
                                <th className="px-4 py-2">Sort</th>
                                <th className="px-4 py-2">Articles</th>
                                <th className="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {categories.map((cat) => (
                                <tr key={cat.id} className="border-b">
                                    <td className="px-4 py-2 font-medium">{cat.title}</td>
                                    <td className="px-4 py-2 text-muted-foreground">{cat.slug}</td>
                                    <td className="px-4 py-2">{cat.sort}</td>
                                    <td className="px-4 py-2">
                                        <Badge variant="secondary">{cat.articles_count}</Badge>
                                    </td>
                                    <td className="px-4 py-2">
                                        <div className="flex gap-2">
                                            <Link href={adminCategories.edit.url({ help_category: cat.id })}>
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
