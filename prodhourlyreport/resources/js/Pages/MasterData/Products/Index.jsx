import Checkbox from '@/Components/Checkbox';
import CrudModal from '@/Components/CrudModal';
import CrudTable from '@/Components/CrudTable';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal';
import FilterBar, { FilterSearch, FilterSelect } from '@/Components/FilterBar';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Select from '@/Components/Select';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useCrudResource } from '@/lib/useCrudResource';
import { useServerFilters } from '@/lib/useServerFilters';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

function emptyForm() {
    return { product_model_id: '', name: '', code: '', is_active: true };
}

function toFormData(product) {
    return {
        product_model_id: product.product_model_id ? String(product.product_model_id) : '',
        name: product.name,
        code: product.code ?? '',
        is_active: product.is_active,
    };
}

function syncBannerClass(status) {
    if (status === 'ok') return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    if (status === 'failed') return 'border-red-200 bg-red-50 text-red-800';
    return 'border-sky-200 bg-sky-50 text-sky-900';
}

export default function Index({ products, productModels, lines = [], categories = [], filters: initialFilters = {}, syncStatus }) {
    const [syncing, setSyncing] = useState(false);
    const prevFinished = useRef(syncStatus?.finished_at);
    const { filters, setFilter, setSearch, setPage } = useServerFilters('products.index', initialFilters, {
        only: ['products', 'filters', 'syncStatus'],
    });
    const {
        data,
        setData,
        errors,
        processing,
        editing,
        confirmingDelete,
        setConfirmingDelete,
        openCreate,
        openEdit,
        close,
        submit,
        destroy,
    } = useCrudResource({ routeName: 'products', emptyForm, toFormData });

    const syncInFlight = ['queued', 'running'].includes(syncStatus?.status);
    const rows = products?.data ?? products ?? [];
    const lineOptions = useMemo(
        () => lines.map((line) => ({ value: String(line.id), label: line.name })),
        [lines],
    );

    useEffect(() => {
        if (!syncInFlight) return undefined;
        const id = setInterval(() => {
            router.reload({ only: ['syncStatus'], preserveScroll: true });
        }, 5000);
        return () => clearInterval(id);
    }, [syncInFlight]);

    useEffect(() => {
        if (
            syncStatus?.status === 'ok'
            && syncStatus?.finished_at
            && syncStatus.finished_at !== prevFinished.current
        ) {
            prevFinished.current = syncStatus.finished_at;
            router.reload({ only: ['products', 'syncStatus'], preserveScroll: true });
        }
    }, [syncStatus?.status, syncStatus?.finished_at]);

    const syncFromQad = () => {
        if (syncing || syncInFlight) return;
        setSyncing(true);
        router.post(route('products.sync'), {}, {
            preserveScroll: true,
            onFinish: () => setSyncing(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Products" />

            <PageHeader
                title="Products"
                description="Item masters from QAD. Sync runs in the background so the gateway does not time out."
                action={
                    <div className="flex flex-wrap items-center gap-2">
                        <SecondaryButton onClick={syncFromQad} disabled={syncing || syncInFlight}>
                            {syncing || syncInFlight ? 'Syncing…' : 'Sync QAD'}
                        </SecondaryButton>
                        <PrimaryButton onClick={openCreate}>Add Product</PrimaryButton>
                    </div>
                }
            />

            {syncStatus?.message && (
                <div className={`mb-4 rounded-lg border px-4 py-3 text-sm ${syncBannerClass(syncStatus.status)}`}>
                    <span className="font-medium capitalize">{syncStatus.status}</span>
                    {' — '}
                    {syncStatus.message}
                    {syncInFlight && (
                        <span className="ms-1 text-xs opacity-80">(auto-refresh every 5s)</span>
                    )}
                </div>
            )}

            <FilterBar>
                <FilterSearch
                    value={filters.search ?? ''}
                    onChange={setSearch}
                    placeholder="Name, code, part no…"
                />
                <FilterSelect
                    label="Line"
                    value={filters.line_id ?? ''}
                    onChange={(value) => setFilter('line_id', value)}
                    options={lineOptions}
                    emptyLabel="All lines"
                />
                <FilterSelect
                    label="Category"
                    value={filters.category ?? ''}
                    onChange={(value) => setFilter('category', value)}
                    options={categories}
                    emptyLabel="All categories"
                />
            </FilterBar>

            <div className="mb-2 text-sm text-gray-500">{products?.total ?? rows.length} product(s)</div>

            <CrudTable
                headers={['Name', 'Code', 'Part no.', 'Model', 'Line', 'Category', 'Status', 'Source']}
                rows={rows}
                emptyMessage="No products match these filters."
                onEdit={openEdit}
                onDelete={setConfirmingDelete}
                renderCells={(product) => (
                    <>
                        <td className="px-4 py-3 font-medium text-gray-900">
                            <div>{product.name}</div>
                            {product.description && (
                                <div className="mt-0.5 text-xs font-normal text-gray-400">{product.description}</div>
                            )}
                        </td>
                        <td className="px-4 py-3 text-gray-500">{product.code}</td>
                        <td className="px-4 py-3 text-gray-500">{product.part_number ?? '—'}</td>
                        <td className="px-4 py-3 text-gray-500">
                            {product.product_model?.name ?? (
                                <span className="italic text-amber-600">Unassigned</span>
                            )}
                        </td>
                        <td className="px-4 py-3 text-gray-500">{product.product_model?.line?.name ?? '—'}</td>
                        <td className="px-4 py-3 text-gray-500">{product.category ?? '—'}</td>
                        <td className="px-4 py-3"><StatusBadge active={product.is_active} /></td>
                        <td className="px-4 py-3 capitalize text-gray-500">{product.source}</td>
                    </>
                )}
            />

            <Pagination paginator={products} onPageChange={setPage} />

            <CrudModal
                show={editing !== null}
                title={editing?.id ? 'Edit Product' : 'Add Product'}
                onClose={close}
                onSubmit={submit}
                processing={processing}
            >
                <div>
                    <InputLabel htmlFor="product_model_id" value="Model (connects to Line)" />
                    <Select
                        id="product_model_id"
                        className="mt-1 block w-full"
                        value={data.product_model_id}
                        onChange={(e) => setData('product_model_id', e.target.value)}
                    >
                        <option value="">Unassigned</option>
                        {productModels.map((model) => (
                            <option key={model.id} value={model.id}>
                                {model.line?.name} — {model.name}
                            </option>
                        ))}
                    </Select>
                    <InputError message={errors.product_model_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="name" value="Name" />
                    <TextInput id="name" className="mt-1 block w-full" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="code" value="Code (optional)" />
                    <TextInput id="code" className="mt-1 block w-full" value={data.code} onChange={(e) => setData('code', e.target.value)} />
                    <InputError message={errors.code} className="mt-2" />
                </div>

                <label className="flex items-center gap-2">
                    <Checkbox checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                    <span className="text-sm text-gray-600">Active</span>
                </label>
            </CrudModal>

            <DeleteConfirmModal
                show={confirmingDelete !== null}
                label={confirmingDelete?.name}
                description="This can't be undone. Products with recorded entries can't be deleted."
                onClose={() => setConfirmingDelete(null)}
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
