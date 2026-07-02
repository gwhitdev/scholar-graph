import { Link, useForm } from '@inertiajs/react';
import { ArrowDownIcon, ArrowUpIcon } from 'lucide-react';
import { useState } from 'react';
import {
    destroy,
    reorder,
    store,
    update,
} from '@/actions/App/Http/Controllers/CollectionController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export const collectionColors = ['sage', 'teal', 'slate', 'clay', 'amber', 'plum'] as const;

export type CollectionColor = (typeof collectionColors)[number];

export const colorTokenMap: Record<CollectionColor, string> = {
    sage: 'bg-emerald-300',
    teal: 'bg-teal-400',
    slate: 'bg-slate-400',
    clay: 'bg-orange-300',
    amber: 'bg-amber-300',
    plum: 'bg-purple-300',
};

interface Collection {
    id: number;
    name: string;
    color: CollectionColor;
    papers: { id: number }[];
}

interface CollectionsListProps {
    projectId: number;
    collections: Collection[];
}

interface CollectionFormData {
    name: string;
    color: CollectionColor;
}

function CollectionDot({ color }: { color: CollectionColor }) {
    return (
        <span
            className={`inline-block size-3 shrink-0 rounded-full ${colorTokenMap[color]}`}
            aria-hidden="true"
        />
    );
}

export function CollectionsList({ projectId, collections }: CollectionsListProps) {
    const [editingCollection, setEditingCollection] = useState<Collection | null>(null);
    const [createDialogOpen, setCreateDialogOpen] = useState(false);

    const createForm = useForm<CollectionFormData>({
        name: '',
        color: 'sage',
    });

    const editForm = useForm<CollectionFormData>({
        name: '',
        color: 'sage',
    });

    const reorderForm = useForm({
        collection_ids: collections.map((collection) => collection.id),
    });

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(store.url({ project: projectId }), {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setCreateDialogOpen(false);
            },
        });
    };

    const startEditing = (collection: Collection) => {
        setEditingCollection(collection);
        editForm.setData({ name: collection.name, color: collection.color });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!editingCollection) {
            return;
        }

        editForm.patch(
            update.url({ project: projectId, collection: editingCollection.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    editForm.reset();
                    setEditingCollection(null);
                },
            },
        );
    };

    const submitReorder = (orderedCollections: Collection[]) => {
        reorderForm.setData(
            'collection_ids',
            orderedCollections.map((collection) => collection.id),
        );

        reorderForm.patch(reorder.url({ project: projectId }), {
            preserveScroll: true,
        });
    };

    const moveCollection = (index: number, direction: 'up' | 'down') => {
        const newIndex = direction === 'up' ? index - 1 : index + 1;

        if (newIndex < 0 || newIndex >= collections.length) {
            return;
        }

        const reordered = [...collections];
        const [moved] = reordered.splice(index, 1);
        reordered.splice(newIndex, 0, moved);

        submitReorder(reordered);
    };

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-medium">Collections</h2>
                <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
                    <DialogTrigger asChild>
                        <Button variant="outline" size="sm">
                            New collection
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={handleCreateSubmit}>
                            <DialogHeader>
                                <DialogTitle>Create collection</DialogTitle>
                                <DialogDescription>
                                    Add a named, colour-tagged group for papers in this project.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-4 py-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="collection-name">Name</Label>
                                    <Input
                                        id="collection-name"
                                        value={createForm.data.name}
                                        onChange={(e) =>
                                            createForm.setData('name', e.target.value)
                                        }
                                        placeholder="e.g. Methods papers"
                                        required
                                        maxLength={100}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="collection-color">Colour</Label>
                                    <Select
                                        value={createForm.data.color}
                                        onValueChange={(value: CollectionColor) =>
                                            createForm.setData('color', value)
                                        }
                                    >
                                        <SelectTrigger id="collection-color">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {collectionColors.map((color) => (
                                                <SelectItem key={color} value={color}>
                                                    <span className="flex items-center gap-2">
                                                        <CollectionDot color={color} />
                                                        <span className="capitalize">{color}</span>
                                                    </span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit" disabled={createForm.processing}>
                                    Create
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {collections.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No collections yet. Create one to group papers.
                </p>
            ) : (
                <ul className="space-y-2">
                    {collections.map((collection, index) => (
                        <li
                            key={collection.id}
                            className="flex items-center justify-between gap-2 rounded-md border border-sidebar-border/70 p-2"
                        >
                            <div className="flex min-w-0 items-center gap-2">
                                <CollectionDot color={collection.color} />
                                <span className="truncate text-sm font-medium">
                                    {collection.name}
                                </span>
                                <Badge variant="secondary">{collection.papers.length}</Badge>
                            </div>
                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-7"
                                    disabled={index === 0 || reorderForm.processing}
                                    onClick={() => moveCollection(index, 'up')}
                                    aria-label={`Move ${collection.name} up`}
                                >
                                    <ArrowUpIcon className="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-7"
                                    disabled={
                                        index === collections.length - 1 || reorderForm.processing
                                    }
                                    onClick={() => moveCollection(index, 'down')}
                                    aria-label={`Move ${collection.name} down`}
                                >
                                    <ArrowDownIcon className="size-4" />
                                </Button>
                                <Dialog
                                    open={editingCollection?.id === collection.id}
                                    onOpenChange={(open) => {
                                        if (!open) {
                                            setEditingCollection(null);
                                        }
                                    }}
                                >
                                    <DialogTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => startEditing(collection)}
                                        >
                                            Edit
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <form onSubmit={handleEditSubmit}>
                                            <DialogHeader>
                                                <DialogTitle>Edit collection</DialogTitle>
                                                <DialogDescription>
                                                    Rename or recolour this collection.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="grid gap-4 py-4">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="edit-collection-name">
                                                        Name
                                                    </Label>
                                                    <Input
                                                        id="edit-collection-name"
                                                        value={editForm.data.name}
                                                        onChange={(e) =>
                                                            editForm.setData('name', e.target.value)
                                                        }
                                                        required
                                                        maxLength={100}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="edit-collection-color">
                                                        Colour
                                                    </Label>
                                                    <Select
                                                        value={editForm.data.color}
                                                        onValueChange={(value: CollectionColor) =>
                                                            editForm.setData('color', value)
                                                        }
                                                    >
                                                        <SelectTrigger id="edit-collection-color">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {collectionColors.map((color) => (
                                                                <SelectItem key={color} value={color}>
                                                                    <span className="flex items-center gap-2">
                                                                        <CollectionDot color={color} />
                                                                        <span className="capitalize">
                                                                            {color}
                                                                        </span>
                                                                    </span>
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button type="submit" disabled={editForm.processing}>
                                                    Save
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                                <Button variant="ghost" size="sm" asChild>
                                    <Link
                                        href={destroy.url({
                                            project: projectId,
                                            collection: collection.id,
                                        })}
                                        method="delete"
                                        as="button"
                                        preserveScroll
                                        aria-label={`Delete ${collection.name}`}
                                    >
                                        Delete
                                    </Link>
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
