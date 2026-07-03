import { Head, useForm } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function CreateCategory() {
    const form = useForm({ slug: '', title: '', sort: 0 });

    function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.post(route('admin.help-categories.store'));
    }

    return (
        <>
            <Head title="New Help Category" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">New Help Category</h1>
                <form onSubmit={onSubmit} className="max-w-md space-y-4">
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
                        Create Category
                    </Button>
                </form>
            </div>
        </>
    );
}
