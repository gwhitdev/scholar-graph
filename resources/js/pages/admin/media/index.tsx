import { Head } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';

interface MediaItem {
    id: number;
    filename: string;
    mime: string;
    size: number;
    alt: string | null;
    path: string;
}

interface MediaIndexProps {
    media: {
        data: MediaItem[];
        current_page: number;
        last_page: number;
    };
}

export default function MediaIndex({ media }: MediaIndexProps) {
    return (
        <>
            <Head title="Media Library" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">Media Library</h1>
                <p className="text-muted-foreground">{media.data.length} file(s)</p>
            </div>
        </>
    );
}
