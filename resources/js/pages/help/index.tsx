import { Head, useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import * as helpRoutes from '@/routes/help';

interface Article {
    id: number;
    slug: string;
    title: string;
    status: string;
    help_category: { id: number; slug: string; title: string };
}

interface Category {
    id: number;
    slug: string;
    title: string;
    sort: number;
    articles: Article[];
}

interface HelpIndexProps {
    categories: Category[];
    articles?: Article[];
    search?: string;
}

export default function HelpIndex({ categories, articles, search }: HelpIndexProps) {
    const form = useForm({ q: search ?? '' });

    function onSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.get(helpRoutes.search.url(), { preserveState: true });
    }

    const isSearching = search !== undefined && search !== '';

    return (
        <>
            <Head title="Help Centre" />
            <div className="mx-auto flex max-w-6xl gap-8 px-4 py-12">
                {/* Category sidebar */}
                <aside className="hidden w-64 shrink-0 md:block" aria-label="Help categories">
                    <nav className="space-y-1">
                        {categories.map((cat) => (
                            <a
                                key={cat.id}
                                href={`#cat-${cat.slug}`}
                                className={cn(
                                    'block rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-muted',
                                    'text-foreground',
                                )}
                            >
                                {cat.title}
                            </a>
                        ))}
                    </nav>
                </aside>

                {/* Main content */}
                <main className="flex-1">
                    <h1 className="mb-6 text-3xl font-bold">Help Centre</h1>

                    {/* Search */}
                    <form onSubmit={onSubmit} className="mb-8">
                        <label htmlFor="help-search" className="sr-only">
                            Search help articles
                        </label>
                        <Input
                            id="help-search"
                            type="search"
                            placeholder="Search articles..."
                            value={form.q}
                            onChange={(e) => form.setData('q', e.target.value)}
                        />
                    </form>

                    {/* Search results */}
                    {isSearching && articles && (
                        <section className="mb-8">
                            <h2 className="mb-4 text-xl font-semibold">
                                {articles.length} result{articles.length === 1 ? '' : 's'} for &ldquo;{search}&rdquo;
                            </h2>
                            {articles.length === 0 && (
                                <p className="text-muted-foreground">No articles found matching your search.</p>
                            )}
                            <ul className="space-y-3">
                                {articles.map((article) => (
                                    <li key={article.id}>
                                        <a
                                            href={helpRoutes.show.url({ category: article.help_category.slug, article: article.slug })}
                                            className="block rounded-lg border p-4 transition-colors hover:bg-muted"
                                        >
                                            <span className="font-medium">{article.title}</span>
                                            <span className="ml-2 text-sm text-muted-foreground">
                                                {article.help_category.title}
                                            </span>
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {/* Categories with articles */}
                    {!isSearching &&
                        categories.map((cat) => (
                            <section key={cat.id} id={`cat-${cat.slug}`} className="mb-10">
                                <h2 className="mb-4 text-xl font-semibold">{cat.title}</h2>
                                {cat.articles.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">No articles in this category yet.</p>
                                ) : (
                                    <ul className="space-y-2">
                                        {cat.articles.map((article) => (
                                            <li key={article.id}>
                                                <a
                                                    href={helpRoutes.show.url({ category: cat.slug, article: article.slug })}
                                                    className="block rounded-lg border p-4 transition-colors hover:bg-muted"
                                                >
                                                    {article.title}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                        ))}
                </main>
            </div>
        </>
    );
}
