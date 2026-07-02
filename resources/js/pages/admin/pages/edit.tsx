import { Head } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';

interface PageData {
    id: number;
    slug: string;
    title: string;
    content: { type: string; [key: string]: unknown }[] | null;
    status: string;
    seo_title: string | null;
    seo_description: string | null;
    og_image: string | null;
}

interface EditPageProps {
    page: PageData;
}

export default function EditPage({ page }: EditPageProps) {
    return (
        <>
            <Head title={`Edit: ${page.title}`} />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">Edit: {page.title}</h1>
            </div>
        </>
    );
}
