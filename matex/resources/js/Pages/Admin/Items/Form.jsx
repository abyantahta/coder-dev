import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ item, suppliersOhp }) {
    const editing = Boolean(item);
    const { data, setData, post, put, processing, errors } = useForm({
        item_number: item?.item_number || '',
        description: item?.description || '',
        uom: item?.uom || 'kg',
        price: item?.price ?? '',
        subcont_ohp_id: item?.subcont_ohp_id ? String(item.subcont_ohp_id) : '',
        is_active: item?.is_active ?? true,
    });

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            put(route('admin.items.update', item.id));
        } else {
            post(route('admin.items.store'));
        }
    };

    return (
        <AdminLayout
            title={editing ? `Edit ${item.item_number}` : 'Tambah Raw Material'}
            description="Master item RM (base data) — harga /kg & subcont OHP default."
        >
            <Head title={editing ? 'Edit Raw Material' : 'Tambah Raw Material'} />

            <form
                onSubmit={submit}
                className="max-w-2xl space-y-4 ui-panel p-5 sm:p-6"
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Item Number" />
                        <TextInput
                            className="mt-1 w-full"
                            value={data.item_number}
                            onChange={(e) => setData('item_number', e.target.value)}
                        />
                        <InputError message={errors.item_number} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="UOM" />
                        <TextInput
                            className="mt-1 w-full"
                            value={data.uom}
                            onChange={(e) => setData('uom', e.target.value)}
                        />
                        <InputError message={errors.uom} className="mt-1" />
                    </div>
                </div>

                <div>
                    <InputLabel value="Description" />
                    <TextInput
                        className="mt-1 w-full"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                    />
                    <InputError message={errors.description} className="mt-1" />
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Harga /kg" />
                        <TextInput
                            type="number"
                            step="0.01"
                            min="0"
                            className="mt-1 w-full"
                            value={data.price}
                            onChange={(e) => setData('price', e.target.value)}
                        />
                        <InputError message={errors.price} className="mt-1" />
                        <p className="mt-1 text-xs text-ink-muted">
                            Harga per kilogram (UOM default: kg).
                        </p>
                    </div>
                    <div>
                        <InputLabel value="Subcont OHP (tujuan kirim)" />
                        <select
                            className="mt-1 w-full rounded-md border-line"
                            value={data.subcont_ohp_id}
                            onChange={(e) => setData('subcont_ohp_id', e.target.value)}
                        >
                            <option value="">Pilih supplier OHP</option>
                            {suppliersOhp.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.code} — {s.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.subcont_ohp_id} className="mt-1" />
                    </div>
                </div>
                <p className="text-xs text-ink-muted">
                    Data ini dipakai sebagai base pada PO dan proses lain. Subcont OHP
                    menjadi default tujuan jadwal kirim.
                </p>

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
                        href={route('admin.items.index')}
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
