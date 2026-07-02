import { Head, Link, useForm } from '@inertiajs/react';
import { PlusIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    show,
    store,
} from '@/actions/App/Http/Controllers/ProjectController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Project {
    id: number;
    name: string;
}

interface Props {
    projects: Project[];
}

export default function ProjectsIndex({ projects }: Props) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(store.url(), {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    }

    return (
        <>
            <Head title="Projects" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Projects</h1>
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <PlusIcon />
                                New Project
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <form onSubmit={submit}>
                                <DialogHeader>
                                    <DialogTitle>Create Project</DialogTitle>
                                    <DialogDescription>
                                        Give your research project a name.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) =>
                                                setData('name', e.target.value)
                                            }
                                            disabled={processing}
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">
                                                {errors.name}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button type="submit" disabled={processing}>
                                        Create
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {projects.length === 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>No projects yet</CardTitle>
                            <CardDescription>
                                Create a project to start collecting papers.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <div className="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {projects.map((project) => (
                            <Card key={project.id}>
                                <CardHeader>
                                    <CardTitle>
                                        <Link
                                            href={show.url(project.id)}
                                            className="hover:underline"
                                            prefetch
                                        >
                                            {project.name}
                                        </Link>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex justify-end">
                                    <Link
                                        href={destroy.url(project.id)}
                                        method="delete"
                                        as="button"
                                        className="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium text-destructive hover:underline"
                                    >
                                        <Trash2Icon className="size-4" />
                                        Delete
                                    </Link>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
