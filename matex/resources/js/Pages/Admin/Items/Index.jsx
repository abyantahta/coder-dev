import EmptyState from '@/Components/EmptyState';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ items, filters }) {
    const applyFilters = (next) => {
        router.get(route('admin.items.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AdminLayout
            title="Raw Material"
            description="Master item raw material (base data proses lain) — harga /kg & subcont OHP."
            actions={
                <Link
                    href={route('admin.items.create')}
                    className="pressable rounded-md bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-deep"
                >
                    Tambah Raw Material
                </Link>
            }
        >
            <Head title="Master Raw Material" />

            <div className="mb-4 flex flex-wrap gap-2">
                <input
                    type="search"
                    placeholder="Cari item number / deskripsi..."
                    className="rounded-md border-line text-sm"
                    defaultValue={filters.search}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            applyFilters({ ...filters, search: e.target.value || undefined });
                        }
                    }}
                />
                <select
                    className="rounded-md border-line text-sm"
                    value={filters.active ?? ''}
                    onChange={(e) =>
                        applyFilters({
                            ...filters,
                            active: e.target.value === '' ? undefined : e.target.value,
                        })
                    }
                >
                    <option value="">Semua status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>

            {!items.data.length ? (
                <EmptyState title="Belum ada item" />
            ) : (
                <div className="overflow-hidden ui-panel">
                    <table className="min-w-full divide-y divide-line text-sm">
                        <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3">Item Number</th>
                                <th className="px-4 py-3">Description</th>
                                <th className="px-4 py-3">Harga /kg</th>
                                <th className="px-4 py-3">Subcont OHP</th>
                                <th className="px-4 py-3">UOM</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {items.data.map((item) => (
                                <tr key={item.id} className="hover:bg-canvas-soft">
                                    <td className="px-4 py-3 font-medium">
                                        {item.item_number}
                                    </td>
                                    <td className="px-4 py-3">{item.description}</td>
                                    <td className="px-4 py-3">
                                        {item.price != null
                                            ? Number(item.price).toLocaleString('id-ID', {
                                                  minimumFractionDigits: 0,
                                                  maximumFractionDigits: 2,
                                              })
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {item.subcont_ohp
                                            ? `${item.subcont_ohp.code} — ${item.subcont_ohp.name}`
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3">{item.uom}</td>
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
                                    <td className="px-4 py-3 text-right space-x-3">
                                        <Link
                                            href={route('admin.items.edit', item.id)}
                                            className="font-medium text-brand hover:underline"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            className="font-medium text-rose-600 hover:underline"
                                            onClick={() => {
                                                if (
                                                    confirm(
                                                        `Hapus/nonaktifkan item ${item.item_number}?`,
                                                    )
                                                ) {
                                                    router.delete(
                                                        route('admin.items.destroy', item.id),
                                                    );
                                                }
                                            }}
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AdminLayout>
    );
}
