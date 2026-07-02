import { Head } from '@inertiajs/react';

interface UserUsage {
    id: number;
    name: string;
    email: string;
    projects_count: number;
    total_prompt_tokens: number;
    total_completion_tokens: number;
    total_cost_usd: number;
}

interface UsersProps {
    perUserUsage: UserUsage[];
}

export default function Users({ perUserUsage }: UsersProps) {
    return (
        <>
            <Head title="Admin - Users" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">Users</h1>

                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full text-sm">
                        <thead className="border-b border-sidebar-border/70 bg-muted/50 dark:border-sidebar-border">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Name</th>
                                <th className="px-4 py-3 text-left font-medium">Email</th>
                                <th className="px-4 py-3 text-right font-medium">Projects</th>
                                <th className="px-4 py-3 text-right font-medium">Prompt Tokens</th>
                                <th className="px-4 py-3 text-right font-medium">Completion Tokens</th>
                                <th className="px-4 py-3 text-right font-medium">Cost (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {perUserUsage.map((user) => (
                                <tr
                                    key={user.id}
                                    className="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                                >
                                    <td className="px-4 py-3">{user.name}</td>
                                    <td className="px-4 py-3">{user.email}</td>
                                    <td className="px-4 py-3 text-right">{user.projects_count}</td>
                                    <td className="px-4 py-3 text-right">
                                        {user.total_prompt_tokens.toLocaleString()}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {user.total_completion_tokens.toLocaleString()}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        ${user.total_cost_usd.toFixed(4)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
