import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Truck, Package, AlertTriangle, ExternalLink } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface ZimPostDelivery {
    id: string | null;
    tracking_number: string | null;
    reference: string | null;
    status: string | null;
    amount_usd: number | null;
    distance_km: number | null;
    vehicle_type: string | null;
    created_at: string | null;
}

interface Props {
    agentCode: string;
    deliveries: ZimPostDelivery[];
    referenceCount: number;
    apiError: { message: string; code?: string | null } | null;
}

const statusStyles: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-800',
    assigned: 'bg-blue-100 text-blue-800',
    picked_up: 'bg-indigo-100 text-indigo-800',
    in_transit: 'bg-purple-100 text-purple-800',
    delivered: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800',
};

export default function ZimPostDeliveries({ agentCode, deliveries, referenceCount, apiError }: Props) {
    return (
        <>
            <Head title="ZimPost Deliveries" />
            <div className="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
                <div className="max-w-6xl mx-auto px-4 py-8">
                    <div className="mb-6 flex items-center justify-between">
                        <div>
                            <Link href={route('agent.dashboard')} className="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 mb-3">
                                <ArrowLeft className="h-4 w-4" />
                                Back to dashboard
                            </Link>
                            <h1 className="text-3xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-3">
                                <Truck className="h-7 w-7 text-emerald-600" />
                                ZimPost Deliveries
                            </h1>
                            <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Live courier status for deliveries linked to your clients ({referenceCount} client reference{referenceCount === 1 ? '' : 's'} tracked).
                            </p>
                        </div>
                        <div className="text-right">
                            <p className="text-xs text-gray-500">Agent code</p>
                            <p className="font-mono font-semibold text-gray-900 dark:text-gray-100">{agentCode}</p>
                        </div>
                    </div>

                    {apiError && (
                        <Card className="p-4 mb-6 border-amber-300 bg-amber-50 dark:bg-amber-900/20">
                            <div className="flex items-start gap-3">
                                <AlertTriangle className="h-5 w-5 text-amber-600 mt-0.5" />
                                <div>
                                    <p className="font-medium text-amber-900 dark:text-amber-100">Live updates unavailable</p>
                                    <p className="text-sm text-amber-700 dark:text-amber-300">{apiError.message}</p>
                                    {apiError.code && <p className="text-xs font-mono text-amber-600 mt-1">{apiError.code}</p>}
                                </div>
                            </div>
                        </Card>
                    )}

                    <Card className="overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900/40">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Tracking #</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Client Reference</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Status</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Amount</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Distance</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Vehicle</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Created</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600 dark:text-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                    {deliveries.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="px-4 py-12 text-center text-sm text-gray-500">
                                                <Package className="h-10 w-10 mx-auto mb-3 text-gray-300" />
                                                No ZimPost deliveries yet for your clients.
                                            </td>
                                        </tr>
                                    ) : (
                                        deliveries.map((d) => (
                                            <tr key={d.id ?? d.tracking_number} className="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                                <td className="px-4 py-3 text-sm font-mono">{d.tracking_number ?? '—'}</td>
                                                <td className="px-4 py-3 text-sm font-medium">{d.reference ?? '—'}</td>
                                                <td className="px-4 py-3 text-sm">
                                                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusStyles[d.status ?? ''] ?? 'bg-gray-100 text-gray-800'}`}>
                                                        {(d.status ?? '—').replace(/_/g, ' ')}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right">
                                                    {d.amount_usd !== null ? `$${Number(d.amount_usd).toFixed(2)}` : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right text-gray-600">
                                                    {d.distance_km !== null ? `${d.distance_km} km` : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-gray-600">{d.vehicle_type ?? '—'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-500">
                                                    {d.created_at ? new Date(d.created_at).toLocaleDateString() : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-right">
                                                    {d.reference && (
                                                        <Link
                                                            href={`/delivery/tracking?ref=${encodeURIComponent(d.reference)}`}
                                                            className="inline-flex items-center gap-1 text-emerald-700 hover:underline text-xs font-medium"
                                                        >
                                                            Track <ExternalLink className="h-3 w-3" />
                                                        </Link>
                                                    )}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}
