import { Head, useForm } from '@inertiajs/react';
import AdminNav from '@/components/admin-nav';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import licenses from '@/routes/admin/licenses';

interface LicenseKeyData {
    id: number;
    code: string;
    credits: number | null;
    redeemed_at: string | null;
    expires_at: string | null;
    created_at: string;
    plan: { name: string } | null;
    redeemed_by_user: { name: string; email: string } | null;
}

interface Props {
    licenseKeys: LicenseKeyData[];
}

export default function AdminLicenses({ licenseKeys }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        count: '5',
        credits: '100',
        plan_id: '',
        expires_at: '',
    });

    function handleMint(e: React.FormEvent) {
        e.preventDefault();
        post(licenses.store.url(), {
            onSuccess: () => reset(),
        });
    }

    return (
        <>
            <Head title="Licence Keys" />
            <AdminNav />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                <h1 className="text-2xl font-bold">Licence Keys</h1>

                {/* Mint form */}
                <form
                    onSubmit={handleMint}
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div>
                        <Label htmlFor="count">Count</Label>
                        <Input
                            id="count"
                            type="number"
                            min={1}
                            max={100}
                            value={data.count}
                            onChange={(e) =>
                                setData('count', e.target.value)
                            }
                            className="w-24"
                        />
                        {errors.count && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.count}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="credits">Credits</Label>
                        <Input
                            id="credits"
                            type="number"
                            min={1}
                            value={data.credits}
                            onChange={(e) =>
                                setData('credits', e.target.value)
                            }
                            className="w-28"
                        />
                        {errors.credits && (
                            <p className="mt-1 text-xs text-destructive">
                                {errors.credits}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="expires_at">Expires At</Label>
                        <Input
                            id="expires_at"
                            type="date"
                            value={data.expires_at}
                            onChange={(e) =>
                                setData('expires_at', e.target.value)
                            }
                        />
                    </div>
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Minting...' : 'Mint Keys'}
                    </Button>
                </form>

                {/* Keys table */}
                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b bg-muted/50">
                                <th className="px-4 py-2 text-left font-medium">
                                    Code
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Credits
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Plan
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Redeemed By
                                </th>
                                <th className="px-4 py-2 text-left font-medium">
                                    Expires
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {licenseKeys.map((key) => (
                                <tr
                                    key={key.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="px-4 py-2 font-mono text-xs">
                                        {key.code}
                                    </td>
                                    <td className="px-4 py-2">
                                        {key.credits ?? '-'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {key.plan?.name ?? '-'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {key.redeemed_at ? (
                                            <span className="text-muted-foreground">
                                                Redeemed
                                            </span>
                                        ) : key.expires_at &&
                                          new Date(key.expires_at) <
                                              new Date() ? (
                                            <span className="text-destructive">
                                                Expired
                                            </span>
                                        ) : (
                                            <span className="text-green-600">
                                                Active
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-2">
                                        {key.redeemed_by_user?.email ?? '-'}
                                    </td>
                                    <td className="px-4 py-2">
                                        {key.expires_at
                                            ? new Date(
                                                  key.expires_at,
                                              ).toLocaleDateString()
                                            : 'Never'}
                                    </td>
                                </tr>
                            ))}
                            {licenseKeys.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        No licence keys yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
