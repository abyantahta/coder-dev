import EmptyState from '@/Components/EmptyState';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';

const STATUS_STYLES = {
    ok: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    failed: 'border-rose-200 bg-rose-50 text-rose-800',
};

const CATEGORY_LABELS = {
    raw_mat: 'Supplier RM',
    ohp: 'Supplier OHP',
};

export default function Index({
    suppliers,
    filters,
    lastSyncedAt,
    totalSuppliers,
    uncategorizedCount,
    syncStatus,
}) {
    const syncInFlight = ['queued', 'running'].includes(syncStatus?.status);

    useEffect(() => {
        if (!syncInFlight) {
            return;
        }

        const timer = setTimeout(() => {
            router.reload({
                only: ['suppliers', 'totalSuppliers', 'lastSyncedAt', 'syncStatus'],
            });
        }, 5000);

        return () => clearTimeout(timer);
    }, [syncInFlight, syncStatus]);

    const applyFilters = (next) => {
        router.get(route('admin.qad-suppliers.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSync = () => {
        router.post(
            route('admin.qad-suppliers.sync'),
            {},
            { preserveScroll: true },
        );
    };

    const handleCategoryChange = (supplier, category) => {
        router.patch(
            route('admin.qad-suppliers.update-category', supplier.id),
            { category: category || null },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <AdminLayout
            title="Suppliers"
            description="Master supplier RM & OHP, tersinkron dari QAD (SDI_getSupplierMaster). Tandai kategori RM/OHP dan kelola akun user di sini."
        >
            <Head title="Suppliers" />

            <div className="mb-4 ui-panel p-5">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 className="font-semibold text-ink">Sinkronisasi Supplier QAD</h2>
                        <p className="mt-0.5 text-sm text-ink-soft">
                            {totalSuppliers} supplier tersimpan.{' '}
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

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <input
                    type="search"
                    placeholder="Cari kode / nama / kota..."
                    className="rounded-md border-line text-sm"
                    defaultValue={filters.q}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            applyFilters({ ...filters, q: e.target.value || undefined });
                        }
                    }}
                />
                <select
                    className="rounded-md border-line text-sm"
                    value={filters.category || ''}
                    onChange={(e) =>
                        applyFilters({ ...filters, category: e.target.value || undefined })
                    }
                >
                    <option value="">Semua kategori</option>
                    <option value="uncategorized">Belum dikategorikan</option>
                    <option value="raw_mat">Supplier RM</option>
                    <option value="ohp">Supplier OHP</option>
                </select>
                {uncategorizedCount > 0 && (
                    <span className="text-xs text-amber-700">
                        {uncategorizedCount} supplier belum dikategorikan
                    </span>
                )}
            </div>

            {!suppliers.data.length ? (
                <EmptyState
                    title={
                        filters.q
                            ? `Tidak ada supplier ditemukan untuk "${filters.q}".`
                            : 'Belum ada data supplier. Klik "Sync Sekarang" untuk menarik data dari QAD.'
                    }
                />
            ) : (
                <div className="overflow-hidden ui-panel">
                    <table className="min-w-full divide-y divide-line text-sm">
                        <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3">Kode</th>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Kota</th>
                                <th className="px-4 py-3">Kategori</th>
                                <th className="px-4 py-3">Akun</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {suppliers.data.map((supplier) => (
                                <tr key={supplier.id} className="hover:bg-canvas-soft">
                                    <td className="px-4 py-3 font-mono font-medium">
                                        {supplier.qad_code}
                                    </td>
                                    <td className="px-4 py-3">{supplier.name ?? '—'}</td>
                                    <td className="px-4 py-3">{supplier.city ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <select
                                            className="rounded-md border-line py-1 text-xs"
                                            value={supplier.category ?? ''}
                                            onChange={(e) =>
                                                handleCategoryChange(supplier, e.target.value)
                                            }
                                        >
                                            <option value="">— belum ditandai —</option>
                                            <option value="raw_mat">
                                                {CATEGORY_LABELS.raw_mat}
                                            </option>
                                            <option value="ohp">{CATEGORY_LABELS.ohp}</option>
                                        </select>
                                    </td>
                                    <td className="px-4 py-3">
                                        {supplier.company_id ? (
                                            <Link
                                                href={route('admin.users.index', {
                                                    company_id: supplier.company_id,
                                                })}
                                                className="font-medium text-brand hover:underline"
                                            >
                                                {supplier.users_count}{' '}
                                                {supplier.users_count === 1 ? 'user' : 'users'}
                                            </Link>
                                        ) : (
                                            <span className="text-ink-faint">— belum ada akun —</span>
                                        )}
                                        {supplier.category && (
                                            <Link
                                                href={route('admin.users.create', {
                                                    supplier_code: supplier.qad_code,
                                                    role:
                                                        supplier.category === 'raw_mat'
                                                            ? 'supplier_rm'
                                                            : 'supplier_ohp',
                                                })}
                                                className="ms-3 text-xs font-medium text-ink-soft hover:text-brand hover:underline"
                                            >
                                                + Tambah user
                                            </Link>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <Pagination paginator={suppliers} />
                </div>
            )}
        </AdminLayout>
    );
}
