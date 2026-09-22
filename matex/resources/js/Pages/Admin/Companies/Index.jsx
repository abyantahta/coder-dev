import EmptyState from '@/Components/EmptyState';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

const TYPE_LABEL = {
    sdi: 'PT. SDI',
    raw_mat: 'Supplier RM',
    ohp: 'Supplier OHP',
};

export default function Index({ companies, filters, types }) {
    const applyFilters = (next) => {
        router.get(route('admin.companies.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AdminLayout
            title="Suppliers"
            description="Master supplier RM, supplier OHP, dan company terkait."
            actions={
                <Link
                    href={route('admin.companies.create')}
                    className="pressable rounded-md bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-deep"
                >
                    Tambah Supplier
                </Link>
            }
        >
            <Head title="Suppliers" />

            <div className="mb-4 flex flex-wrap gap-2">
                <input
                    type="search"
                    placeholder="Cari kode / nama..."
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
                    value={filters.type || ''}
                    onChange={(e) =>
                        applyFilters({ ...filters, type: e.target.value || undefined })
                    }
                >
                    <option value="">Semua tipe</option>
                    {types.map((t) => (
                        <option key={t.value} value={t.value}>
                            {t.label}
                        </option>
                    ))}
                </select>
            </div>

            {!companies.data.length ? (
                <EmptyState title="Belum ada company" description="Tambahkan company baru." />
            ) : (
                <div className="overflow-hidden ui-panel">
                    <table className="min-w-full divide-y divide-line text-sm">
                        <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3">Code</th>
                                <th className="px-4 py-3">Name</th>
                                <th className="px-4 py-3">Type</th>
                                <th className="px-4 py-3">Users</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {companies.data.map((company) => (
                                <tr key={company.id} className="hover:bg-canvas-soft">
                                    <td className="px-4 py-3 font-medium">{company.code}</td>
                                    <td className="px-4 py-3">
                                        <div>{company.name}</div>
                                        <div className="text-xs text-ink-faint">
                                            {company.address || '—'}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {TYPE_LABEL[company.type] || company.type}
                                    </td>
                                    <td className="px-4 py-3">{company.users_count}</td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                company.is_active
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-canvas text-ink-soft'
                                            }`}
                                        >
                                            {company.is_active ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-3">
                                        <Link
                                            href={route('admin.companies.edit', company.id)}
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
                                                        `Hapus company ${company.name}?`,
                                                    )
                                                ) {
                                                    router.delete(
                                                        route(
                                                            'admin.companies.destroy',
                                                            company.id,
                                                        ),
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
