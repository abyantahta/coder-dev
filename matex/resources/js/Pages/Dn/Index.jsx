import EmptyState from '@/Components/EmptyState';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty } from '@/utils/qty';
import { Head, router } from '@inertiajs/react';

export default function Index({ notes, filters, scheduleStatuses }) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="ui-section-title">Delivery Notes</h2>
                    <p className="text-sm text-ink-muted">
                        Traceability pengiriman material per DN
                    </p>
                </div>
            }
        >
            <Head title="Delivery Notes" />

            <div className="ui-page space-y-4">
                    <select
                        className="rounded-md border-line text-sm"
                        value={filters.status || ''}
                        onChange={(e) =>
                            router.get(
                                route('delivery-notes.index'),
                                { status: e.target.value || undefined },
                                { preserveState: true },
                            )
                        }
                    >
                        <option value="">Semua status jadwal</option>
                        {scheduleStatuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>

                    {!notes.data.length ? (
                        <EmptyState
                            title="Belum ada Delivery Note"
                            description="DN digenerate setelah Purchasing mengonfirmasi OK."
                        />
                    ) : (
                        <div className="overflow-hidden ui-panel">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                                    <tr>
                                        <th className="px-4 py-3">DN Number</th>
                                        <th className="px-4 py-3">PO</th>
                                        <th className="px-4 py-3">Item</th>
                                        <th className="px-4 py-3">Qty</th>
                                        <th className="px-4 py-3">Jadwal</th>
                                        <th className="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {notes.data.map((note) => (
                                        <tr
                                            key={note.id}
                                            className="ui-row cursor-pointer"
                                            title="Klik baris untuk melihat detail DN"
                                            onClick={() =>
                                                router.visit(
                                                    route('delivery-notes.show', note.id),
                                                )
                                            }
                                        >
                                            <td className="px-4 py-3 font-semibold text-brand">
                                                {note.dn_number}
                                            </td>
                                            <td className="px-4 py-3">
                                                {note.purchase_order?.po_number}
                                            </td>
                                            <td className="px-4 py-3">
                                                {note.purchase_order_item?.item?.item_number}
                                            </td>
                                            <td className="px-4 py-3">{formatQty(note.qty)} kg</td>
                                            <td className="px-4 py-3">
                                                {new Date(
                                                    note.delivery_schedule?.scheduled_date,
                                                ).toLocaleDateString('id-ID')}
                                            </td>
                                            <td className="px-4 py-3">
                                                <StatusBadge
                                                    type="schedule"
                                                    value={note.delivery_schedule?.status}
                                                />
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
