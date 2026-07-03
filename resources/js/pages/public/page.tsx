import { Head } from '@inertiajs/react';
import BlockRenderer from '@/components/block-renderer';

interface PageData {
    title: string;
    slug: string;
    content: { type: string; [key: string]: unknown }[] | null;
    seo_title: string | null;
    seo_description: string | null;
    og_image: string | null;
}

interface PublicPageProps {
    page: PageData;
}

export default function PublicPage({ page }: PublicPageProps) {
    return (
        <>
            <Head>
                <title>{page.seo_title ?? page.title}</title>
                {page.seo_description && <meta name="description" content={page.seo_description} />}
                {page.og_image && <meta property="og:image" content={page.og_image} />}
            </Head>
            <main className="mx-auto max-w-4xl px-4 py-12">
                <h1 className="mb-8 text-4xl font-bold">{page.title}</h1>
                <BlockRenderer blocks={page.content} />
            </main>
        </>
    );
}
