import EmptyState from '@/Components/EmptyState';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty } from '@/utils/qty';
import { Head } from '@inertiajs/react';

export default function Index({ receivings, summary }) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="ui-section-title">
                        Billing / Data Receiving
                    </h2>
                    <p className="text-sm text-ink-muted">
                        Basis penagihan Supplier RM berdasarkan receiving PPIC
                    </p>
                </div>
            }
        >
            <Head title="Billing" />

            <div className="ui-page space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="ui-panel p-5">
                            <p className="text-xs uppercase text-ink-muted">Total DN Received</p>
                            <p className="mt-2 text-3xl font-semibold">{summary.total_dn}</p>
                        </div>
                        <div className="ui-panel p-5">
                            <p className="text-xs uppercase text-ink-muted">Total Qty (kg)</p>
                            <p className="mt-2 text-3xl font-semibold">
                                {formatQty(summary.total_qty || 0)}
                            </p>
                        </div>
                    </div>

                    {!receivings.data.length ? (
                        <EmptyState
                            title="Belum ada data receiving"
                            description="Data muncul setelah PPIC melakukan receiving."
                        />
                    ) : (
                        <div className="overflow-hidden ui-panel">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                                    <tr>
                                        <th className="px-4 py-3">Tanggal Receive</th>
                                        <th className="px-4 py-3">DN</th>
                                        <th className="px-4 py-3">PO</th>
                                        <th className="px-4 py-3">Item</th>
                                        <th className="px-4 py-3">Qty</th>
                                        <th className="px-4 py-3">QAD</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {receivings.data.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-3">
                                                {new Date(r.received_at).toLocaleString('id-ID')}
                                            </td>
                                            <td className="px-4 py-3 font-medium">
                                                {r.delivery_note?.dn_number}
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.delivery_note?.purchase_order?.po_number}
                                            </td>
                                            <td className="px-4 py-3">
                                                {
                                                    r.delivery_note?.purchase_order_item?.item
                                                        ?.item_number
                                                }
                                            </td>
                                            <td className="px-4 py-3">{formatQty(r.received_qty)} kg</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge type="qad" value={r.qad_status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
            </div>
        </AuthenticatedLayout>
    );
}
