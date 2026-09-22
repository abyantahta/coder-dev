import EmptyState from '@/Components/EmptyState';
import StatusBadge from '@/Components/StatusBadge';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

function ohpLabels(po) {
    return [
        ...new Set(
            (po.schedules || [])
                .map((s) => s.ohp_supplier?.name)
                .filter(Boolean),
        ),
    ].join(', ');
}

function FulfillmentBadge({ po }) {
    return (
        <StatusBadge
            type="fulfillment"
            value={po.is_closed ? 'closed' : 'open'}
            title={
                po.is_closed
                    ? 'Seluruh DN sudah dikirim dan sudah di-approve'
                    : 'Masih ada DN yang belum dikirim atau belum di-approve'
            }
        />
    );
}

export default function Index({ orders, filters }) {
    const { auth } = usePage().props;
    const canCreate = ['purchasing', 'admin'].includes(auth.user.role);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="ui-eyebrow">Workflow</p>
                        <h2 className="ui-section-title mt-1">Purchase Orders</h2>
                        <p className="mt-1 text-sm text-ink-muted">
                            Kelola pemesanan material dan jejak statusnya
                        </p>
                    </div>
                    {canCreate && (
                        <Link
                            href={route('purchase-orders.create')}
                            className="pressable rounded-md bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-deep"
                        >
                            Buat Draft PO
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Purchase Orders" />

            <div className="ui-page space-y-4">
                <div className="flex gap-2">
                    <select
                        className="rounded-md border-line bg-surface text-sm transition duration-220 ease-matex focus:border-brand focus:ring-brand/30"
                        value={filters.status || ''}
                        onChange={(e) =>
                            router.get(
                                route('purchase-orders.index'),
                                { status: e.target.value || undefined },
                                { preserveState: true },
                            )
                        }
                    >
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="awaiting_rm_confirm">Menunggu RM</option>
                        <option value="awaiting_purchasing_ok">Menunggu OK</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">Dalam Proses</option>
                        <option value="completed">Selesai</option>
                    </select>
                </div>

                {!orders.data.length ? (
                    <EmptyState
                        title="Belum ada Purchase Order"
                        description="Buat draft PO untuk memulai alur pemesanan material."
                    />
                ) : (
                    <div className="ui-panel animate-fade-up overflow-hidden">
                        <table className="min-w-full divide-y divide-line text-sm">
                            <thead className="ui-table-head">
                                <tr>
                                    <th className="px-4 py-3">PO Number</th>
                                    <th className="px-4 py-3">Supplier RM</th>
                                    <th className="px-4 py-3">OHP</th>
                                    <th className="px-4 py-3">Due Date</th>
                                    <th className="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {orders.data.map((po) => (
                                    <tr
                                        key={po.id}
                                        className="ui-row cursor-pointer"
                                        title="Klik baris untuk melihat detail PO"
                                        onClick={() =>
                                            router.visit(
                                                route('purchase-orders.show', po.id),
                                            )
                                        }
                                    >
                                        <td className="px-4 py-3 font-semibold text-brand">
                                            {po.po_number}
                                        </td>
                                        <td className="px-4 py-3 text-ink-soft">
                                            {po.supplier_rm?.name}
                                        </td>
                                        <td className="px-4 py-3 text-ink-soft">
                                            {ohpLabels(po) || '—'}
                                        </td>
                                        <td className="px-4 py-3 text-ink-soft">
                                            {new Date(po.due_date).toLocaleDateString('id-ID')}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap items-center gap-1.5">
                                                <StatusBadge type="po" value={po.status} />
                                                <FulfillmentBadge po={po} />
                                            </div>
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
