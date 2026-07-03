import { Head } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';

interface PageItem {
    id: number;
    slug: string;
    title: string;
    status: string;
    published_at: string | null;
    created_at: string;
    author: { id: number; name: string } | null;
}

interface PagesIndexProps {
    pages: {
        data: PageItem[];
        current_page: number;
        last_page: number;
    };
}

export default function PagesIndex({ pages }: PagesIndexProps) {
    return (
        <>
            <Head title="CMS Pages" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">CMS Pages</h1>
                <p className="text-muted-foreground">{pages.data.length} page(s)</p>
            </div>
        </>
    );
}
