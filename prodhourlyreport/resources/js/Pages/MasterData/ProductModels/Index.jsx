import Checkbox from '@/Components/Checkbox';
import CrudModal from '@/Components/CrudModal';
import CrudTable from '@/Components/CrudTable';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal';
import FilterBar, { FilterSearch, FilterSelect } from '@/Components/FilterBar';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import Select from '@/Components/Select';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useCrudResource } from '@/lib/useCrudResource';
import { useServerFilters } from '@/lib/useServerFilters';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';

function emptyForm() {
    return { line_id: '', name: '', code: '', is_active: true };
}

function toFormData(model) {
    return { line_id: String(model.line_id), name: model.name, code: model.code ?? '', is_active: model.is_active };
}

export default function Index({ productModels, lines, filters: initialFilters = {} }) {
    const { filters, setFilter, setSearch } = useServerFilters('product-models.index', initialFilters, {
        only: ['productModels', 'filters'],
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
    } = useCrudResource({ routeName: 'product-models', emptyForm, toFormData });

    const lineOptions = useMemo(
        () => lines.map((line) => ({ value: String(line.id), label: line.name })),
        [lines],
    );

    return (
        <AuthenticatedLayout>
            <Head title="Models" />

            <PageHeader
                title="Models"
                description="Local grouping between QAD Lines and QAD Products (Line → Model → Product). Create a model under a line, then assign products to it."
                action={<PrimaryButton onClick={openCreate}>Add Model</PrimaryButton>}
            />

            <FilterBar>
                <FilterSearch
                    value={filters.search ?? ''}
                    onChange={setSearch}
                    placeholder="Name or code…"
                />
                <FilterSelect
                    label="Line"
                    value={filters.line_id ?? ''}
                    onChange={(value) => setFilter('line_id', value)}
                    options={lineOptions}
                    emptyLabel="All lines"
                />
            </FilterBar>

            <div className="mb-2 text-sm text-gray-500">{productModels.length} model(s)</div>

            <CrudTable
                headers={['Name', 'Line', 'Code', 'Products', 'Status']}
                rows={productModels}
                emptyMessage="No models match these filters."
                onEdit={openEdit}
                onDelete={setConfirmingDelete}
                renderCells={(model) => (
                    <>
                        <td className="px-4 py-3 font-medium text-gray-900">{model.name}</td>
                        <td className="px-4 py-3 text-gray-500">{model.line?.name}</td>
                        <td className="px-4 py-3 text-gray-500">{model.code}</td>
                        <td className="px-4 py-3 text-gray-500">{model.products_count}</td>
                        <td className="px-4 py-3"><StatusBadge active={model.is_active} /></td>
                    </>
                )}
            />

            <CrudModal
                show={editing !== null}
                title={editing?.id ? 'Edit Model' : 'Add Model'}
                onClose={close}
                onSubmit={submit}
                processing={processing}
            >
                <div>
                    <InputLabel htmlFor="line_id" value="Line" />
                    <Select
                        id="line_id"
                        className="mt-1 block w-full"
                        value={data.line_id}
                        onChange={(e) => setData('line_id', e.target.value)}
                    >
                        <option value="">Select line</option>
                        {lines.map((line) => (
                            <option key={line.id} value={line.id}>{line.name}</option>
                        ))}
                    </Select>
                    <InputError message={errors.line_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="name" value="Name" />
                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="code" value="Code (optional)" />
                    <TextInput
                        id="code"
                        className="mt-1 block w-full"
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value)}
                    />
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
                description="This can't be undone. Models with recorded entries can't be deleted."
                onClose={() => setConfirmingDelete(null)}
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
