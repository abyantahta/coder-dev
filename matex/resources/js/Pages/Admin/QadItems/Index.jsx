import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';

const STATUS_STYLES = {
    ok: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    failed: 'border-rose-200 bg-rose-50 text-rose-800',
};

export default function Index({ items, filters, lastSyncedAt, totalItems, syncStatus }) {
    const syncInFlight = ['queued', 'running'].includes(syncStatus?.status);

    useEffect(() => {
        if (!syncInFlight) {
            return;
        }

        const timer = setTimeout(() => {
            router.reload({
                only: ['items', 'totalItems', 'lastSyncedAt', 'syncStatus'],
            });
        }, 5000);

        return () => clearTimeout(timer);
    }, [syncInFlight, syncStatus]);

    const applyFilters = (next) => {
        router.get(route('admin.qad-items.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSync = () => {
        router.post(
            route('admin.qad-items.sync'),
            {},
            { preserveScroll: true },
        );
    };

    return (
        <AdminLayout
            title="Item Master QAD"
            description="Cache item master hasil sync dari QAD (SDI_getItemMasterExt) — dipakai sebagai referensi kode item."
        >
            <Head title="Item Master QAD" />

            <div className="mb-4 ui-panel p-5">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 className="font-semibold text-ink">Sinkronisasi Item QAD</h2>
                        <p className="mt-0.5 text-sm text-ink-soft">
                            {totalItems} item tersimpan.{' '}
                            {lastSyncedAt
                                ? `Terakhir sync ${new Date(lastSyncedAt).toLocaleString('id-ID')}.`
                                : 'Belum pernah disinkronkan.'}
                        </p>
                    </div>
                    <button
                        type="button"
                        disabled={syncInFlight}
                        onClick={handleSync}
                        className="pressable rounded-md bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-deep disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {syncInFlight ? 'Sedang sync…' : 'Sync Sekarang'}
                    </button>
                </div>

                {syncStatus?.message && (
                    <div
                        className={`mt-4 rounded-lg border px-4 py-3 text-sm ${
                            STATUS_STYLES[syncStatus.status] ??
                            'border-sky-200 bg-sky-50 text-sky-900'
                        }`}
                    >
                        <span className="font-medium capitalize">{syncStatus.status}</span> —{' '}
                        {syncStatus.message}
                        {syncInFlight && (
                            <span className="ms-1 text-xs opacity-80">
                                (auto-refresh tiap 5 detik)
                            </span>
                        )}
                    </div>
                )}
            </div>

            <div className="mb-4 flex flex-wrap gap-2">
                <input
                    type="search"
                    placeholder="Cari kode / nama / part number..."
                    className="rounded-md border-line text-sm"
                    defaultValue={filters.q}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            applyFilters({ q: e.target.value || undefined });
                        }
                    }}
                />
            </div>

            {!items.data.length ? (
                <EmptyState
                    title={
                        filters.q
                            ? `Tidak ada item ditemukan untuk "${filters.q}".`
                            : 'Belum ada data item. Klik "Sync Sekarang" untuk menarik data dari QAD.'
                    }
                />
            ) : (
                <div className="overflow-hidden ui-panel">
                    <table className="min-w-full divide-y divide-line text-sm">
                        <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3">Kode</th>
                                <th className="px-4 py-3">Deskripsi</th>
                                <th className="px-4 py-3">Part Number</th>
                                <th className="px-4 py-3">Group</th>
                                <th className="px-4 py-3">Prod Line</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Aktif</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {items.data.map((item) => (
                                <tr key={item.id} className="hover:bg-canvas-soft">
                                    <td className="px-4 py-3 font-mono font-medium">
                                        {item.qad_code}
                                    </td>
                                    <td className="px-4 py-3">{item.description ?? '—'}</td>
                                    <td className="px-4 py-3 font-mono text-ink-soft">
                                        {item.part_number ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">{item.qad_group ?? '—'}</td>
                                    <td className="px-4 py-3">{item.prod_line ?? '—'}</td>
                                    <td className="px-4 py-3">{item.qad_status ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                item.is_active
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-canvas text-ink-soft'
                                            }`}
                                        >
                                            {item.is_active ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <Pagination paginator={items} />
                </div>
            )}
        </AdminLayout>
    );
}
