import EmptyState from '@/Components/EmptyState';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ users, filters, roles }) {
    const roleLabel = Object.fromEntries(roles.map((r) => [r.value, r.label]));

    const applyFilters = (next) => {
        router.get(route('admin.users.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AdminLayout
            title="Users"
            description="Kelola akun portal, role, dan company affiliation."
            actions={
                <Link
                    href={route('admin.users.create')}
                    className="pressable rounded-md bg-brand px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-deep"
                >
                    Tambah User
                </Link>
            }
        >
            <Head title="Admin Users" />

            <div className="mb-4 flex flex-wrap gap-2">
                <input
                    type="search"
                    placeholder="Cari nama / email..."
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
                    value={filters.role || ''}
                    onChange={(e) =>
                        applyFilters({ ...filters, role: e.target.value || undefined })
                    }
                >
                    <option value="">Semua role</option>
                    {roles.map((r) => (
                        <option key={r.value} value={r.value}>
                            {r.label}
                        </option>
                    ))}
                </select>
                {filters.company_id && (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-brand-muted px-3 py-1.5 text-xs font-medium text-brand-deep">
                        Difilter per supplier
                        <button
                            type="button"
                            className="text-brand-deep/70 hover:text-brand-deep"
                            onClick={() =>
                                applyFilters({ ...filters, company_id: undefined })
                            }
                        >
                            ×
                        </button>
                    </span>
                )}
            </div>

            {!users.data.length ? (
                <EmptyState title="Belum ada user" />
            ) : (
                <div className="overflow-hidden ui-panel">
                    <table className="min-w-full divide-y divide-line text-sm">
                        <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                            <tr>
                                <th className="px-4 py-3">Name</th>
                                <th className="px-4 py-3">Email</th>
                                <th className="px-4 py-3">Role</th>
                                <th className="px-4 py-3">Company</th>
                                <th className="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-line">
                            {users.data.map((user) => (
                                <tr key={user.id} className="hover:bg-canvas-soft">
                                    <td className="px-4 py-3 font-medium">{user.name}</td>
                                    <td className="px-4 py-3">{user.email}</td>
                                    <td className="px-4 py-3">
                                        {roleLabel[user.role] || user.role}
                                    </td>
                                    <td className="px-4 py-3">
                                        {user.company?.name || '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right space-x-3">
                                        <Link
                                            href={route('admin.users.edit', user.id)}
                                            className="font-medium text-brand hover:underline"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            className="font-medium text-rose-600 hover:underline"
                                            onClick={() => {
                                                if (confirm(`Hapus user ${user.name}?`)) {
                                                    router.delete(
                                                        route('admin.users.destroy', user.id),
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
