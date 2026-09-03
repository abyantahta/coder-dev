import Checkbox from '@/Components/Checkbox';
import CrudModal from '@/Components/CrudModal';
import CrudTable from '@/Components/CrudTable';
import DeleteConfirmModal from '@/Components/DeleteConfirmModal';
import FilterBar, { FilterSearch } from '@/Components/FilterBar';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useCrudResource } from '@/lib/useCrudResource';
import { useServerFilters } from '@/lib/useServerFilters';
import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function emptyForm() {
    return { name: '', code: '', is_active: true };
}

function toFormData(line) {
    return { name: line.name, code: line.code ?? '', is_active: line.is_active };
}

function syncBannerClass(status) {
    if (status === 'ok') return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    if (status === 'failed') return 'border-red-200 bg-red-50 text-red-800';
    return 'border-sky-200 bg-sky-50 text-sky-900';
}

export default function Index({ lines, filters: initialFilters = {}, syncStatus }) {
    const [syncing, setSyncing] = useState(false);
    const prevFinished = useRef(syncStatus?.finished_at);
    const { filters, setSearch } = useServerFilters('lines.index', initialFilters, {
        only: ['lines', 'filters', 'syncStatus'],
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
    } = useCrudResource({ routeName: 'lines', emptyForm, toFormData });

    const syncInFlight = ['queued', 'running'].includes(syncStatus?.status);

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
            router.reload({ only: ['lines', 'syncStatus'], preserveScroll: true });
        }
    }, [syncStatus?.status, syncStatus?.finished_at]);

    const syncFromQad = () => {
        if (syncing || syncInFlight) return;
        setSyncing(true);
        router.post(route('lines.sync'), {}, {
            preserveScroll: true,
            onFinish: () => setSyncing(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Lines" />

            <PageHeader
                title="Lines"
                description="Production lines synced from QAD. Models you create under a line group its products."
                action={
                    <div className="flex flex-wrap items-center gap-2">
                        <SecondaryButton onClick={syncFromQad} disabled={syncing || syncInFlight}>
                            {syncing || syncInFlight ? 'Syncing…' : 'Sync QAD'}
                        </SecondaryButton>
                        <PrimaryButton onClick={openCreate}>Add Line</PrimaryButton>
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
                    placeholder="Name or code…"
                />
            </FilterBar>

            <div className="mb-2 text-sm text-gray-500">{lines.length} line(s)</div>

            <CrudTable
                headers={['Name', 'Code', 'Models', 'Entries', 'Status', 'Source', 'Last sync']}
                rows={lines}
                emptyMessage="No lines match these filters."
                onEdit={openEdit}
                onDelete={setConfirmingDelete}
                renderCells={(line) => (
                    <>
                        <td className="px-4 py-3 font-medium text-gray-900">{line.name}</td>
                        <td className="px-4 py-3 text-gray-500">{line.code}</td>
                        <td className="px-4 py-3 text-gray-500">{line.product_models_count}</td>
                        <td className="px-4 py-3 text-gray-500">{line.production_logs_count}</td>
                        <td className="px-4 py-3"><StatusBadge active={line.is_active} /></td>
                        <td className="px-4 py-3 capitalize text-gray-500">{line.source}</td>
                        <td className="px-4 py-3 text-gray-500">
                            {line.last_synced_at
                                ? new Date(line.last_synced_at).toLocaleString()
                                : '—'}
                        </td>
                    </>
                )}
            />

            <CrudModal
                show={editing !== null}
                title={editing?.id ? 'Edit Line' : 'Add Line'}
                onClose={close}
                onSubmit={submit}
                processing={processing}
            >
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
                description="This can't be undone. Lines with recorded entries can't be deleted."
                onClose={() => setConfirmingDelete(null)}
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
