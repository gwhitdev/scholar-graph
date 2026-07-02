import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import billing from '@/routes/billing';

interface Transaction {
    id: number;
    delta: number;
    reason: string;
    balance_after: number;
    created_at: string;
}

interface Plan {
    id: number;
    slug: string;
    name: string;
    monthly_credit_allowance: number;
}

interface Props {
    plan: Plan | null;
    balance: number;
    transactions: Transaction[];
}

const reasonLabels: Record<string, string> = {
    monthly_grant: 'Monthly Grant',
    llm_spend: 'Synthesis',
    license_redeem: 'Licence Redemption',
    purchase: 'Purchase',
};

export default function Billing({ plan, balance, transactions }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
    });

    function handleRedeem(e: React.FormEvent) {
        e.preventDefault();
        post(billing.redeem.url(), {
            onSuccess: () => reset('code'),
        });
    }

    return (
        <>
            <Head title="Billing" />

            <h1 className="sr-only">Billing</h1>

            <div className="space-y-8">
                {/* Current plan & balance */}
                <Heading
                    variant="small"
                    title="Billing"
                    description="Manage your plan and credits"
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-lg border p-4">
                        <p className="text-sm text-muted-foreground">Current Plan</p>
                        <p className="text-xl font-semibold">
                            {plan?.name ?? 'Free'}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {plan?.monthly_credit_allowance ?? 50} credits/month
                        </p>
                    </div>
                    <div className="rounded-lg border p-4">
                        <p className="text-sm text-muted-foreground">Credit Balance</p>
                        <p className="text-xl font-semibold">{balance}</p>
                    </div>
                </div>

                {/* Buy credits */}
                <div className="space-y-4">
                    <Heading
                        variant="small"
                        title="Buy credits"
                        description="Purchase additional credits to keep synthesising."
                    />
                    <div className="flex gap-3">
                        <form action={billing.checkout.url()} method="post">
                            <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''} />
                            <input type="hidden" name="pack" value="starter" />
                            <Button type="submit" variant="outline">
                                Starter — 100 credits ($5)
                            </Button>
                        </form>
                        <form action={billing.checkout.url()} method="post">
                            <input type="hidden" name="_token" value={document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''} />
                            <input type="hidden" name="pack" value="pro" />
                            <Button type="submit" variant="outline">
                                Pro — 500 credits ($20)
                            </Button>
                        </form>
                    </div>
                </div>

                {/* Redeem form */}
                <div className="space-y-4">
                    <Heading
                        variant="small"
                        title="Redeem a licence key"
                        description="Enter a licence key to add credits or upgrade your plan."
                    />
                    <form onSubmit={handleRedeem} className="flex gap-2">
                        <div className="flex-1">
                            <Label htmlFor="code" className="sr-only">
                                Licence key
                            </Label>
                            <Input
                                id="code"
                                placeholder="XXXX-XXXX-XXXX-XXXX"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value)}
                            />
                            {errors.code && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.code}
                                </p>
                            )}
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Redeeming...' : 'Redeem'}
                        </Button>
                    </form>
                </div>

                {/* Transaction history */}
                {transactions.length > 0 && (
                    <div className="space-y-4">
                        <Heading
                            variant="small"
                            title="Transaction History"
                            description="Recent credit activity"
                        />
                        <div className="rounded-lg border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="px-4 py-2 text-left font-medium">
                                            Date
                                        </th>
                                        <th className="px-4 py-2 text-left font-medium">
                                            Reason
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Change
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Balance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.map((tx) => (
                                        <tr
                                            key={tx.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-2">
                                                {new Date(
                                                    tx.created_at,
                                                ).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-2">
                                                {reasonLabels[tx.reason] ??
                                                    tx.reason}
                                            </td>
                                            <td
                                                className={`px-4 py-2 text-right ${tx.delta > 0 ? 'text-green-600' : 'text-red-600'}`}
                                            >
                                                {tx.delta > 0 ? '+' : ''}
                                                {tx.delta}
                                            </td>
                                            <td className="px-4 py-2 text-right">
                                                {tx.balance_after}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
