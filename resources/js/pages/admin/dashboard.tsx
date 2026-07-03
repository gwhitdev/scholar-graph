import { Head } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';

interface DashboardProps {
    userCount: number;
    paperCount: number;
    savedPaperCount: number;
    topSearchTerms: { query: string; count: number }[];
    llmUsageTotals: { prompt_tokens: number; completion_tokens: number; cost_usd: number };
    apiUsageBySource: { internal: number; external: number };
    creditBalance: { limit: number | null; usage: number | null; remaining: number | null };
}

export default function Dashboard({
    userCount,
    paperCount,
    savedPaperCount,
    topSearchTerms,
    llmUsageTotals,
    apiUsageBySource,
    creditBalance,
}: DashboardProps) {
    const stats = [
        { label: 'Users', value: userCount },
        { label: 'Papers', value: paperCount },
        { label: 'Saved Papers', value: savedPaperCount },
        { label: 'Prompt Tokens', value: llmUsageTotals.prompt_tokens },
        { label: 'Completion Tokens', value: llmUsageTotals.completion_tokens },
        { label: 'Total Cost (USD)', value: llmUsageTotals.cost_usd.toFixed(4) },
        { label: 'Internal API Calls', value: apiUsageBySource.internal },
        { label: 'External API Calls', value: apiUsageBySource.external },
    ];

    return (
        <>
            <Head title="Admin Dashboard" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">Admin Dashboard</h1>

                {/* Stat tiles */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <p className="text-sm text-muted-foreground">{stat.label}</p>
                            <p className="text-2xl font-semibold">{stat.value}</p>
                        </div>
                    ))}
                </div>

                {/* Credit balance */}
                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 className="mb-2 text-lg font-semibold">OpenRouter Credits</h2>
                    {creditBalance.limit !== null ? (
                        <div className="space-y-1">
                            <p className="text-sm">
                                Limit: ${creditBalance.limit.toFixed(2)} | Used: $
                                {(creditBalance.usage ?? 0).toFixed(2)} | Remaining: $
                                {(creditBalance.remaining ?? 0).toFixed(2)}
                            </p>
                            <div className="h-2 w-full rounded-full bg-muted">
                                <div
                                    className="h-2 rounded-full bg-primary"
                                    style={{
                                        width: `${creditBalance.remaining !== null && creditBalance.limit > 0 ? ((creditBalance.remaining / creditBalance.limit) * 100).toFixed(1) : 0}%`,
                                    }}
                                />
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">Unlimited / n/a</p>
                    )}
                </div>

                {/* Top search terms */}
                {topSearchTerms.length > 0 && (
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="mb-2 text-lg font-semibold">Top Search Terms</h2>
                        <ul className="space-y-1 text-sm">
                            {topSearchTerms.map((term) => (
                                <li key={term.query} className="flex justify-between">
                                    <span>{term.query}</span>
                                    <span className="text-muted-foreground">{term.count}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </>
    );
}
