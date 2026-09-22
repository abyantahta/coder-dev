import EmptyState from '@/Components/EmptyState';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function SrBadge({ score }) {
    if (score == null) {
        return <span className="text-ink-faint">—</span>;
    }

    let tone = 'bg-emerald-100 text-emerald-800';
    if (score < 70) tone = 'bg-rose-100 text-rose-800';
    else if (score < 85) tone = 'bg-amber-100 text-amber-900';

    return (
        <span className={`inline-flex min-w-[3rem] justify-center rounded-md px-2 py-1 text-sm font-bold tabular-nums ${tone}`}>
            {score}
        </span>
    );
}

function StatCard({ label, value, hint, tone = 'default' }) {
    const valueClass =
        tone === 'danger'
            ? 'text-rose-700'
            : tone === 'warn'
              ? 'text-amber-800'
              : 'text-ink';

    return (
        <div className="ui-panel p-5">
            <p className="ui-eyebrow">{label}</p>
            <p className={`mt-3 font-display text-3xl font-bold tabular-nums ${valueClass}`}>
                {value}
            </p>
            {hint && <p className="mt-1 text-xs text-ink-muted">{hint}</p>}
        </div>
    );
}

export default function Index({
    summary,
    suppliers,
    shipments,
    pending_overdue: pendingOverdue,
    filters,
    supplierOptions,
}) {
    const applyFilters = (next) => {
        router.get(route('admin.discipline.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    const lateShipments = shipments.filter((row) => row.is_late);

    return (
        <AdminLayout
            title="Kedisiplinan Supplier RM"
            description="Plan = tanggal jadwal yang dikonfirmasi Supplier RM. Aktual = tanggal kirim (ship confirm). Keterlambatan menurunkan skor SR."
        >
            <Head title="Kedisiplinan RM" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end gap-3">
                    <div>
                        <label className="ui-eyebrow">Filter supplier</label>
                        <select
                            className="mt-1 block rounded-md border-line text-sm"
                            value={filters.supplier_id ?? ''}
                            onChange={(e) =>
                                applyFilters({
                                    supplier_id: e.target.value || undefined,
                                })
                            }
                        >
                            <option value="">Semua Supplier RM</option>
                            {supplierOptions.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.code} — {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    {filters.supplier_id && (
                        <button
                            type="button"
                            className="text-sm font-medium text-brand hover:underline"
                            onClick={() => applyFilters({})}
                        >
                            Reset filter
                        </button>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Total pengiriman"
                        value={summary.total_shipments}
                        hint="Sudah ship confirm"
                    />
                    <StatCard
                        label="Terlambat"
                        value={summary.late_count}
                        hint={`${summary.on_time_count} on-time`}
                        tone={summary.late_count > 0 ? 'danger' : 'default'}
                    />
                    <StatCard
                        label="Total hari terlambat"
                        value={summary.total_days_late}
                        hint={`Rata-rata ${summary.avg_days_late} hari / keterlambatan`}
                        tone={summary.total_days_late > 0 ? 'warn' : 'default'}
                    />
                    <StatCard
                        label="Masih overdue"
                        value={summary.pending_overdue_count}
                        hint="Plan sudah lewat, belum dikirim"
                        tone={summary.pending_overdue_count > 0 ? 'danger' : 'default'}
                    />
                </div>

                <div>
                    <h3 className="font-display text-base font-semibold text-ink">
                        Ringkasan per Supplier RM
                    </h3>
                    <p className="mt-1 text-sm text-ink-muted">
                        Skor SR turun bila sering/lama terlambat dibanding plan yang sudah dikonfirmasi.
                    </p>

                    {!suppliers.length ? (
                        <div className="mt-4">
                            <EmptyState title="Belum ada supplier RM" />
                        </div>
                    ) : (
                        <div className="mt-4 overflow-hidden ui-panel">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                                    <tr>
                                        <th className="px-4 py-3">Supplier</th>
                                        <th className="px-4 py-3">Skor SR</th>
                                        <th className="px-4 py-3">Kirim</th>
                                        <th className="px-4 py-3">Terlambat</th>
                                        <th className="px-4 py-3">On-time %</th>
                                        <th className="px-4 py-3">Total hari</th>
                                        <th className="px-4 py-3">Rata-rata</th>
                                        <th className="px-4 py-3">Max</th>
                                        <th className="px-4 py-3">Overdue</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {suppliers.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="cursor-pointer hover:bg-canvas-soft"
                                            onClick={() =>
                                                applyFilters({ supplier_id: row.id })
                                            }
                                        >
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-ink">
                                                    {row.code}
                                                </div>
                                                <div className="text-xs text-ink-muted">
                                                    {row.name}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <SrBadge score={row.sr_score} />
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {row.total_shipments}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`font-semibold tabular-nums ${
                                                        row.late_count > 0
                                                            ? 'text-rose-700'
                                                            : 'text-ink'
                                                    }`}
                                                >
                                                    {row.late_count}×
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {row.on_time_rate != null
                                                    ? `${row.on_time_rate}%`
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {row.total_days_late}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {row.avg_days_late} hari
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {row.max_days_late} hari
                                            </td>
                                            <td className="px-4 py-3 tabular-nums">
                                                {row.pending_overdue_count > 0 ? (
                                                    <span className="font-semibold text-rose-700">
                                                        {row.pending_overdue_count}
                                                    </span>
                                                ) : (
                                                    0
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {pendingOverdue.length > 0 && (
                    <div>
                        <h3 className="font-display text-base font-semibold text-ink">
                            Belum dikirim (plan sudah lewat)
                        </h3>
                        <div className="mt-4 overflow-hidden ui-panel">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                                    <tr>
                                        <th className="px-4 py-3">Supplier</th>
                                        <th className="px-4 py-3">PO</th>
                                        <th className="px-4 py-3">Item</th>
                                        <th className="px-4 py-3">Plan</th>
                                        <th className="px-4 py-3">Terlambat</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {pendingOverdue.map((row) => (
                                        <tr key={row.id} className="hover:bg-canvas-soft">
                                            <td className="px-4 py-3">
                                                {row.supplier_code} — {row.supplier_name}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={route('purchase-orders.show', row.po_id)}
                                                    className="font-medium text-brand hover:underline"
                                                >
                                                    {row.po_number}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.item_number}</div>
                                                <div className="text-xs text-ink-muted">
                                                    {row.item_description}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">{formatDate(row.plan_date)}</td>
                                            <td className="px-4 py-3 font-semibold text-rose-700 tabular-nums">
                                                {row.days_late} hari
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                <div>
                    <h3 className="font-display text-base font-semibold text-ink">
                        Riwayat keterlambatan
                    </h3>
                    <p className="mt-1 text-sm text-ink-muted">
                        Pengiriman yang aktualnya lebih molor dari plan.
                    </p>

                    {!lateShipments.length ? (
                        <div className="mt-4">
                            <EmptyState title="Belum ada keterlambatan tercatat" />
                        </div>
                    ) : (
                        <div className="mt-4 overflow-hidden ui-panel">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                                    <tr>
                                        <th className="px-4 py-3">Supplier</th>
                                        <th className="px-4 py-3">PO / DN</th>
                                        <th className="px-4 py-3">Item</th>
                                        <th className="px-4 py-3">Plan</th>
                                        <th className="px-4 py-3">Aktual</th>
                                        <th className="px-4 py-3">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {lateShipments.map((row) => (
                                        <tr key={row.id} className="hover:bg-canvas-soft">
                                            <td className="px-4 py-3">
                                                {row.supplier_code} — {row.supplier_name}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={route('purchase-orders.show', row.po_id)}
                                                    className="font-medium text-brand hover:underline"
                                                >
                                                    {row.po_number}
                                                </Link>
                                                {row.dn_number && (
                                                    <div className="text-xs text-ink-muted">
                                                        {row.dn_number}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium">{row.item_number}</div>
                                                <div className="text-xs text-ink-muted">
                                                    {row.item_description}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">{formatDate(row.plan_date)}</td>
                                            <td className="px-4 py-3">{formatDate(row.actual_date)}</td>
                                            <td className="px-4 py-3 font-semibold text-rose-700 tabular-nums">
                                                +{row.days_late} hari
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
