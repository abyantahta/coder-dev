import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const SUPPLIER_ROLE_CATEGORY = {
    supplier_rm: 'raw_mat',
    supplier_ohp: 'ohp',
};

export default function Form({ user, prefill, qadSuppliers, roles }) {
    const editing = Boolean(user);
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        password: '',
        password_confirmation: '',
        role: user?.role || prefill?.role || roles[0]?.value || 'purchasing',
        supplier_code: user?.company?.code || prefill?.supplier_code || '',
    });

    const supplierCategory = SUPPLIER_ROLE_CATEGORY[data.role];
    const supplierOptions = supplierCategory
        ? qadSuppliers.filter((s) => s.category === supplierCategory)
        : [];

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            put(route('admin.users.update', user.id));
        } else {
            post(route('admin.users.store'));
        }
    };

    return (
        <AdminLayout
            title={editing ? `Edit ${user.name}` : 'Tambah User'}
            description="Atur kredensial login, role, dan supplier (untuk role Supplier RM/OHP)."
        >
            <Head title={editing ? 'Edit User' : 'Tambah User'} />

            <form
                onSubmit={submit}
                className="max-w-2xl space-y-4 ui-panel p-5 sm:p-6"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput
                            className="mt-1 w-full"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput
                            type="email"
                            className="mt-1 w-full"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            value={editing ? 'Password baru (opsional)' : 'Password'}
                        />
                        <TextInput
                            type="password"
                            className="mt-1 w-full"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Konfirmasi Password" />
                        <TextInput
                            type="password"
                            className="mt-1 w-full"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                        />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Role" />
                        <select
                            className="mt-1 w-full rounded-md border-line"
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                        >
                            {roles.map((r) => (
                                <option key={r.value} value={r.value}>
                                    {r.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.role} className="mt-1" />
                    </div>
                    {supplierCategory && (
                        <div>
                            <InputLabel value="Supplier" />
                            <SearchableSelect
                                options={supplierOptions}
                                value={data.supplier_code}
                                placeholder="Ketik kode / nama supplier..."
                                getOptionValue={(s) => s.qad_code}
                                getOptionLabel={(s) =>
                                    `${s.qad_code} — ${s.name}${s.city ? ` (${s.city})` : ''}`
                                }
                                onChange={(val) => setData('supplier_code', val)}
                            />
                            <InputError message={errors.supplier_code} className="mt-1" />
                            {supplierOptions.length === 0 && (
                                <p className="mt-1 text-xs text-amber-700">
                                    Belum ada supplier bertanda kategori ini. Tandai dulu di
                                    halaman{' '}
                                    <Link
                                        href={route('admin.qad-suppliers.index')}
                                        className="underline"
                                    >
                                        Suppliers
                                    </Link>
                                    .
                                </p>
                            )}
                        </div>
                    )}
                </div>

                <div className="flex justify-end gap-3 pt-2">
                    <Link
                        href={route('admin.users.index')}
                        className="rounded-md border border-line px-4 py-2 text-sm"
                    >
                        Batal
                    </Link>
                    <PrimaryButton
                        disabled={processing}
                        className="bg-brand hover:bg-brand-deep"
                    >
                        {editing ? 'Simpan' : 'Tambah'}
                    </PrimaryButton>
                </div>
            </form>
        </AdminLayout>
    );
}
