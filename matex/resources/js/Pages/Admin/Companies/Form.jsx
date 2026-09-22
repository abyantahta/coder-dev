import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ company, types }) {
    const editing = Boolean(company);
    const { data, setData, post, put, processing, errors } = useForm({
        code: company?.code || '',
        name: company?.name || '',
        type: company?.type || 'raw_mat',
        address: company?.address || '',
        is_active: company?.is_active ?? true,
    });

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            put(route('admin.companies.update', company.id));
        } else {
            post(route('admin.companies.store'));
        }
    };

    return (
        <AdminLayout
            title={editing ? `Edit ${company.name}` : 'Tambah Supplier'}
            description="Data supplier/company untuk scoping RM, OHP, dan SDI."
        >
            <Head title={editing ? 'Edit Supplier' : 'Tambah Supplier'} />

            <form
                onSubmit={submit}
                className="max-w-2xl space-y-4 ui-panel p-5 sm:p-6"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Code" />
                        <TextInput
                            className="mt-1 w-full"
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                        />
                        <InputError message={errors.code} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Type" />
                        <select
                            className="mt-1 w-full rounded-md border-line"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                        >
                            {types.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.type} className="mt-1" />
                    </div>
                </div>

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
                    <InputLabel value="Address" />
                    <textarea
                        className="mt-1 w-full rounded-md border-line"
                        rows={2}
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                    />
                    <InputError message={errors.address} className="mt-1" />
                </div>

                <label className="flex items-center gap-2 text-sm text-ink-soft">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                    />
                    Aktif
                </label>

                <div className="flex justify-end gap-3 pt-2">
                    <Link
                        href={route('admin.companies.index')}
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
