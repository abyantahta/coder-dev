import { formatQty } from '@/utils/qty';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

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

export default function Show({ forecast, fileUrl, canManage, isNextMonth }) {
    const total = (forecast.items || []).reduce((sum, item) => sum + Number(item.qty || 0), 0);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="ui-eyebrow">Forecast</p>
                        <h2 className="ui-section-title mt-1">
                            {periodLabel(forecast.period_month)}
                        </h2>
                        <p className="mt-1 text-sm text-ink-muted">
                            {forecast.supplier_rm?.name}
                            {isNextMonth ? ' · bulan depan' : ''}
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {fileUrl && (
                            <a
                                href={fileUrl}
                                className="pressable rounded-md border border-line px-4 py-2.5 text-sm font-semibold text-ink-soft hover:border-brand-line"
                            >
                                Unduh File
                            </a>
                        )}
                        {canManage && (
                            <>
                                <Link
                                    href={route('forecasts.edit', forecast.id)}
                                    className="pressable rounded-md border border-line px-4 py-2.5 text-sm font-semibold text-ink-soft"
                                >
                                    Perbarui
                                </Link>
                                <button
                                    type="button"
                                    className="pressable rounded-md border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700"
                                    onClick={() => {
                                        if (confirm('Hapus forecast ini?')) {
                                            router.delete(route('forecasts.destroy', forecast.id));
                                        }
                                    }}
                                >
                                    Hapus
                                </button>
                            </>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Forecast ${periodLabel(forecast.period_month)}`} />

            <div className="ui-page space-y-4">
                <section className="ui-panel p-5">
                    <dl className="grid gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <dt className="text-ink-muted">Supplier RM</dt>
                            <dd className="font-medium">{forecast.supplier_rm?.name}</dd>
                        </div>
                        <div>
                            <dt className="text-ink-muted">Periode</dt>
                            <dd className="font-medium">{periodLabel(forecast.period_month)}</dd>
                        </div>
                        <div>
                            <dt className="text-ink-muted">Diunggah oleh</dt>
                            <dd className="font-medium">{forecast.uploader?.name || '—'}</dd>
                        </div>
                    </dl>
                    {forecast.notes && (
                        <p className="mt-4 rounded-lg bg-canvas-soft px-3 py-2 text-sm text-ink-soft">
                            {forecast.notes}
                        </p>
                    )}
                </section>

                <section className="ui-panel overflow-hidden">
                    <div className="flex items-center justify-between border-b border-line px-5 py-4">
                        <h3 className="font-display font-semibold text-ink">Item Forecast</h3>
                        <p className="text-sm text-ink-muted">
                            Total {formatQty(total)} kg
                        </p>
                    </div>
                    {(forecast.items || []).length === 0 ? (
                        <p className="px-5 py-8 text-center text-sm text-ink-muted">
                            Tidak ada rincian item.
                            {fileUrl ? ' Silakan unduh file forecast.' : ''}
                        </p>
                    ) : (
                        <table className="min-w-full divide-y divide-line text-sm">
                            <thead className="ui-table-head">
                                <tr>
                                    <th className="px-5 py-3">Item Number</th>
                                    <th className="px-5 py-3">Description</th>
                                    <th className="px-5 py-3 text-right">Qty (kg)</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {forecast.items.map((line) => (
                                    <tr key={line.id} className="ui-row">
                                        <td className="px-5 py-3 font-semibold text-ink">
                                            {line.item_number}
                                        </td>
                                        <td className="px-5 py-3 text-ink-soft">
                                            {line.description || '—'}
                                        </td>
                                        <td className="px-5 py-3 text-right tabular-nums">
                                            {formatQty(line.qty)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
