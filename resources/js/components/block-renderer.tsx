interface Block {
    type: string;
    [key: string]: unknown;
}

interface BlockRendererProps {
    blocks: Block[] | null;
}

export default function BlockRenderer({ blocks }: BlockRendererProps) {
    if (!blocks || blocks.length === 0) {
        return null;
    }

    return (
        <div className="space-y-4">
            {blocks.map((block, index) => (
                <div key={index}>
                    {block.type === 'heading' && (
                        <>
                            {block.level === 1 && (
                                <h1 className="text-3xl font-bold">{String(block.text ?? '')}</h1>
                            )}
                            {block.level === 2 && (
                                <h2 className="text-2xl font-semibold">{String(block.text ?? '')}</h2>
                            )}
                            {block.level === 3 && (
                                <h3 className="text-xl font-semibold">{String(block.text ?? '')}</h3>
                            )}
                            {(!block.level || Number(block.level) > 3) && (
                                <h4 className="text-lg font-semibold">{String(block.text ?? '')}</h4>
                            )}
                        </>
                    )}
                    {block.type === 'paragraph' && <p className="mb-4 leading-relaxed">{String(block.text ?? '')}</p>}
                    {block.type === 'image' && (
                        <figure>
                            <img
                                src={String(block.src ?? '')}
                                alt={String(block.alt ?? '')}
                                className="rounded-lg"
                            />
                            {block.caption && (
                                <figcaption className="mt-2 text-sm text-muted-foreground">
                                    {String(block.caption)}
                                </figcaption>
                            )}
                        </figure>
                    )}
                    {block.type === 'cta' && (
                        <a
                            href={String(block.href ?? '#')}
                            className="inline-block rounded-md bg-primary px-6 py-3 font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            {String(block.text ?? '')}
                        </a>
                    )}
                </div>
            ))}
        </div>
    );
}
