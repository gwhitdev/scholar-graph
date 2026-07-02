import { Head } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';

export default function CreatePage() {
    return (
        <>
            <Head title="Create Page" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">Create Page</h1>
            </div>
        </>
    );
}
