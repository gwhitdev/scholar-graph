import { Head } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';

interface ModelUsage {
    model: string;
    prompt_tokens: number;
    completion_tokens: number;
    cost_usd: number;
}

interface UsageProps {
    apiUsageBySource: { internal: number; external: number };
    llmUsageByModel: ModelUsage[];
    llmUsageTotals: { prompt_tokens: number; completion_tokens: number; cost_usd: number };
}

export default function Usage({ apiUsageBySource, llmUsageByModel, llmUsageTotals }: UsageProps) {
    return (
        <>
            <Head title="Admin - Usage" />
                        <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">API & LLM Usage</h1>

                {/* API usage summary */}
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">Internal API Calls</p>
                        <p className="text-2xl font-semibold">{apiUsageBySource.internal}</p>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <p className="text-sm text-muted-foreground">External API Calls</p>
                        <p className="text-2xl font-semibold">{apiUsageBySource.external}</p>
                    </div>
                </div>

                {/* LLM totals */}
                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h2 className="mb-3 text-lg font-semibold">LLM Totals</h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div>
                            <p className="text-sm text-muted-foreground">Prompt Tokens</p>
                            <p className="text-xl font-semibold">
                                {llmUsageTotals.prompt_tokens.toLocaleString()}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Completion Tokens</p>
                            <p className="text-xl font-semibold">
                                {llmUsageTotals.completion_tokens.toLocaleString()}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Total Cost (USD)</p>
                            <p className="text-xl font-semibold">
                                ${llmUsageTotals.cost_usd.toFixed(4)}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Usage by model */}
                {llmUsageByModel.length > 0 && (
                    <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <table className="w-full text-sm">
                            <thead className="border-b border-sidebar-border/70 bg-muted/50 dark:border-sidebar-border">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium">Model</th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Prompt Tokens
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Completion Tokens
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">Cost (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {llmUsageByModel.map((m) => (
                                    <tr
                                        key={m.model}
                                        className="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                                    >
                                        <td className="px-4 py-3">{m.model}</td>
                                        <td className="px-4 py-3 text-right">
                                            {m.prompt_tokens.toLocaleString()}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {m.completion_tokens.toLocaleString()}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            ${m.cost_usd.toFixed(4)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
