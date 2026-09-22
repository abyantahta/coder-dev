import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty } from '@/utils/qty';
import { Head, Link, router, usePage } from '@inertiajs/react';

function periodLabel(value) {
    if (!value) {
        return '—';
    }
    const raw = String(value).slice(0, 7);
    const [year, month] = raw.split('-').map(Number);
    if (!year || !month) {
        return raw;
    }
    return new Date(year, month - 1, 1).toLocaleDateString('id-ID', {
        month: 'long',
        year: 'numeric',
    });
}

function totalQty(forecast) {
    return (forecast.items || []).reduce((sum, item) => sum + Number(item.qty || 0), 0);
}

export default function Index({
    forecasts,
    nextMonthForecast,
    nextMonth,
    canUpload,
    filters,
    suppliersRm,
}) {
    const { auth } = usePage().props;
    const isSupplier = auth.user.role === 'supplier_rm';

    const applyFilters = (next) => {
        router.get(route('forecasts.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="ui-eyebrow">Planning</p>
                        <h2 className="ui-section-title mt-1">Forecast</h2>
                        <p className="mt-1 text-sm text-ink-muted">
                            {isSupplier
                                ? `Forecast bulan depan (${periodLabel(nextMonth)}) dari Purchasing`
                                : 'Upload forecast per supplier RM, diperbarui setiap bulan'}
                        </p>
                    </div>
                    {canUpload && (
                        <Link
                            href={route('forecasts.create')}
                            className="pressable rounded-md bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-deep"
                        >
                            Upload Forecast
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Forecast" />

            <div className="ui-page space-y-5">
                {isSupplier && (
                    <section className="ui-panel animate-fade-up border-brand-line p-5">
                        <p className="ui-eyebrow">Bulan depan</p>
                        <h3 className="mt-1 font-display text-lg font-semibold text-ink">
                            {periodLabel(nextMonth)}
                        </h3>
                        {nextMonthForecast ? (
                            <div className="mt-4">
                                <p className="text-sm text-ink-muted">
                                    {nextMonthForecast.items?.length || 0} item · total{' '}
                                    {formatQty(totalQty(nextMonthForecast))} kg
                                    {nextMonthForecast.original_filename
                                        ? ` · file ${nextMonthForecast.original_filename}`
                                        : ''}
                                </p>
                                <Link
                                    href={route('forecasts.show', nextMonthForecast.id)}
                                    className="mt-3 inline-flex ui-link text-sm"
                                >
                                    Lihat forecast bulan depan
                                </Link>
                            </div>
                        ) : (
                            <p className="mt-3 text-sm text-ink-muted">
                                Forecast bulan depan belum diunggah Purchasing.
                            </p>
                        )}
                    </section>
                )}

                {!isSupplier && (
                    <div className="flex flex-wrap gap-2">
                        <select
                            className="rounded-md border-line bg-surface text-sm"
                            value={filters.supplier_rm_id || ''}
                            onChange={(e) =>
                                applyFilters({
                                    ...filters,
                                    supplier_rm_id: e.target.value || undefined,
                                })
                            }
                        >
                            <option value="">Semua supplier RM</option>
                            {suppliersRm.map((supplier) => (
                                <option key={supplier.id} value={supplier.id}>
                                    {supplier.code} — {supplier.name}
                                </option>
                            ))}
                        </select>
                        <input
                            type="month"
                            className="rounded-md border-line bg-surface text-sm"
                            value={filters.period || ''}
                            onChange={(e) =>
                                applyFilters({
                                    ...filters,
                                    period: e.target.value || undefined,
                                })
                            }
                        />
                    </div>
                )}

                {!forecasts.data.length ? (
                    <EmptyState
                        title="Belum ada forecast"
                        description={
                            canUpload
                                ? 'Upload forecast per supplier RM untuk bulan berjalan atau bulan depan.'
                                : 'Forecast akan muncul di sini setelah Purchasing mengunggah data.'
                        }
                    />
                ) : (
                    <div className="ui-panel animate-fade-up overflow-hidden">
                        <table className="min-w-full divide-y divide-line text-sm">
                            <thead className="ui-table-head">
                                <tr>
                                    <th className="px-4 py-3">Periode</th>
                                    {!isSupplier && <th className="px-4 py-3">Supplier RM</th>}
                                    <th className="px-4 py-3">Item</th>
                                    <th className="px-4 py-3">Total Qty</th>
                                    <th className="px-4 py-3">File</th>
                                    <th className="px-4 py-3">Diunggah</th>
                                    <th className="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {forecasts.data.map((forecast) => {
                                    const isNext =
                                        String(forecast.period_month).slice(0, 7) === nextMonth;
                                    return (
                                        <tr
                                            key={forecast.id}
                                            className="ui-row cursor-pointer"
                                            onClick={() =>
                                                router.visit(route('forecasts.show', forecast.id))
                                            }
                                        >
                                            <td className="px-4 py-3 font-semibold text-ink">
                                                {periodLabel(forecast.period_month)}
                                                {isNext && (
                                                    <span className="ml-2 rounded-full bg-brand-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-brand-deep">
                                                        Bulan depan
                                                    </span>
                                                )}
                                            </td>
                                            {!isSupplier && (
                                                <td className="px-4 py-3 text-ink-soft">
                                                    {forecast.supplier_rm?.name}
                                                </td>
                                            )}
                                            <td className="px-4 py-3 tabular-nums text-ink-soft">
                                                {forecast.items_count ?? forecast.items?.length ?? 0}
                                            </td>
                                            <td className="px-4 py-3 tabular-nums text-ink-soft">
                                                {formatQty(totalQty(forecast))} kg
                                            </td>
                                            <td className="px-4 py-3 text-ink-soft">
                                                {forecast.original_filename || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-ink-soft">
                                                {forecast.uploader?.name || '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <span className="ui-link">Lihat</span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                        <Pagination paginator={forecasts} />
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
