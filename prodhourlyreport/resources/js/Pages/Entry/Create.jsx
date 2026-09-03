import CrudTable from '@/Components/CrudTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PageHeader from '@/Components/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useOfflineSyncContext } from '@/lib/OfflineSyncContext';
import { enqueueEntry } from '@/lib/offlineQueue';
import { useCatalogCascade } from '@/lib/useCatalogCascade';
import { randomUuid } from '@/lib/uuid';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

function selectSingleOption(options, value, setter) {
    if (!value && options.length === 1) {
        setter(String(options[0].id));
    }
}

export default function Create({ lines, todayLogs }) {
    return (
        <AuthenticatedLayout>
            <CreateInner lines={lines} todayLogs={todayLogs} />
        </AuthenticatedLayout>
    );
}

function CreateInner({ lines, todayLogs }) {
    const currentUserId = usePage().props.auth.user.id;
    const { isOnline, pendingEntries, sync, refreshPending } = useOfflineSyncContext();
    const { data, setData, errors, setError, clearErrors, reset } = useForm({
        line_id: '',
        product_model_id: '',
        product_id: '',
        total_production: '',
        total_reject: '',
        total_repair: '',
        notes: '',
    });
    const [justQueued, setJustQueued] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [category, setCategory] = useState('');
    const [productSearch, setProductSearch] = useState('');

    const { models, products, loadingModels, loadingProducts } = useCatalogCascade({
        lineId: data.line_id,
        modelId: data.product_model_id,
        category,
        productSearch,
    });

    useEffect(() => selectSingleOption(lines, data.line_id, (v) => setData('line_id', v)), [lines]);
    useEffect(() => selectSingleOption(models, data.product_model_id, (v) => setData('product_model_id', v)), [models]);
    useEffect(() => selectSingleOption(products, data.product_id, (v) => setData('product_id', v)), [products]);

    const submit = async (e) => {
        e.preventDefault();
        clearErrors();
        setSubmitError(null);

        const totals = {
            total_production: Number(data.total_production),
            total_reject: Number(data.total_reject),
            total_repair: Number(data.total_repair),
        };

        for (const [field, value] of Object.entries(totals)) {
            if (Number.isNaN(value) || value < 0) {
                setError(field, 'Enter a number of 0 or more.');
                return;
            }
        }

        const line = lines.find((l) => String(l.id) === String(data.line_id));
        const model = models.find((m) => String(m.id) === String(data.product_model_id));
        const product = products.find((p) => String(p.id) === String(data.product_id));

        try {
            await enqueueEntry({
                client_uuid: randomUuid(),
                owner_user_id: currentUserId,
                logged_at: new Date().toISOString(),
                line_id: data.line_id,
                product_model_id: data.product_model_id,
                product_id: data.product_id,
                line_name: line?.name ?? '—',
                model_name: model?.name ?? '—',
                product_name: product?.name ?? '—',
                notes: data.notes || null,
                ...totals,
            });
        } catch (error) {
            console.error('Failed to save entry on this device', error);
            setSubmitError("Couldn't save this entry on your device. Please try again — if it keeps happening, check that your browser allows local storage for this site.");
            return;
        }

        reset('total_production', 'total_reject', 'total_repair', 'notes');
        setJustQueued(true);
        setTimeout(() => setJustQueued(false), 3000);

        refreshPending();
        sync();
    };

    const rows = useMemo(() => {
        const pendingRows = pendingEntries.map((entry) => ({
            id: entry.client_uuid,
            pending: true,
            logged_at: entry.logged_at,
            total_production: entry.total_production,
            total_reject: entry.total_reject,
            total_repair: entry.total_repair,
            line: entry.line_name ?? '—',
            model: entry.model_name ?? '—',
            product: entry.product_name ?? '—',
        }));

        const confirmedRows = todayLogs.map((log) => ({
            id: log.id,
            pending: false,
            logged_at: log.logged_at,
            total_production: log.total_production,
            total_reject: log.total_reject,
            total_repair: log.total_repair,
            line: log.line?.name,
            model: log.product_model?.name,
            product: log.product?.name,
        }));

        return [...pendingRows, ...confirmedRows].sort((a, b) => new Date(b.logged_at) - new Date(a.logged_at));
    }, [pendingEntries, todayLogs]);

    if (lines.length === 0) {
        return (
            <>
                <Head title="Production Entry" />
                <PageHeader title="Production Entry" />
                <div className="mx-auto max-w-2xl rounded-xl border border-gray-100 bg-white p-6 text-gray-600 shadow-sm">
                    No line has been assigned to you yet. Contact your Unit Head to get a line assigned.
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Production Entry" />

            <PageHeader title="Production Entry" description="Log this hour's production, reject, and repair counts." />

            <div className="mx-auto max-w-2xl">
                {!isOnline && (
                    <div className="mb-4 flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-3 text-sm font-medium text-gray-600">
                        <span className="h-2 w-2 shrink-0 rounded-full bg-gray-400" />
                        You&apos;re offline. Entries are saved on this device and will sync automatically once you&apos;re back online.
                    </div>
                )}

                <form onSubmit={submit} className="space-y-6 rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    {justQueued && (
                        <div className="rounded-lg bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800">
                            {isOnline ? 'Entry recorded.' : 'Saved offline — will sync automatically.'}
                        </div>
                    )}

                    {submitError && (
                        <div className="rounded-lg bg-accent-50 px-4 py-3 text-sm font-medium text-accent-700">
                            {submitError}
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <InputLabel htmlFor="line_id" value="Line" />
                            <select
                                id="line_id"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-base shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                value={data.line_id}
                                onChange={(e) => {
                                    setData('line_id', e.target.value);
                                    setData('product_model_id', '');
                                    setData('product_id', '');
                                    setProductSearch('');
                                }}
                            >
                                <option value="">Select line</option>
                                {lines.map((line) => (
                                    <option key={line.id} value={line.id}>{line.name}</option>
                                ))}
                            </select>
                            <InputError message={errors.line_id} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="product_model_id" value="Model" />
                            <select
                                id="product_model_id"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-base shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100"
                                value={data.product_model_id}
                                disabled={!data.line_id || loadingModels}
                                onChange={(e) => {
                                    setData('product_model_id', e.target.value);
                                    setData('product_id', '');
                                    setProductSearch('');
                                }}
                            >
                                <option value="">{loadingModels ? 'Loading models…' : 'Select model'}</option>
                                {models.map((model) => (
                                    <option key={model.id} value={model.id}>{model.name}</option>
                                ))}
                            </select>
                            <InputError message={errors.product_model_id} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="category" value="Category" />
                            <select
                                id="category"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-base shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                value={category}
                                onChange={(e) => {
                                    setCategory(e.target.value);
                                    setData('product_id', '');
                                }}
                            >
                                <option value="">All categories</option>
                                <option value="FG">FG</option>
                                <option value="SA">SA</option>
                                <option value="RM">RM</option>
                            </select>
                        </div>

                        <div>
                            <InputLabel htmlFor="product_search" value="Search product" />
                            <input
                                id="product_search"
                                type="search"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-base shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100"
                                value={productSearch}
                                disabled={!data.product_model_id}
                                placeholder="Name, code, part no…"
                                onChange={(e) => {
                                    setProductSearch(e.target.value);
                                    setData('product_id', '');
                                }}
                            />
                        </div>

                        <div className="sm:col-span-2 lg:col-span-2">
                            <InputLabel htmlFor="product_id" value="Product" />
                            <select
                                id="product_id"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-base shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100"
                                value={data.product_id}
                                disabled={!data.product_model_id || loadingProducts}
                                onChange={(e) => setData('product_id', e.target.value)}
                            >
                                <option value="">
                                    {loadingProducts ? 'Loading products…' : 'Select product'}
                                </option>
                                {products.map((product) => (
                                    <option key={product.id} value={product.id}>
                                        {product.name}
                                        {product.category ? ` (${product.category})` : ''}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.product_id} className="mt-1" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel htmlFor="total_production" value="Total Production" />
                            <input
                                id="total_production"
                                type="number"
                                inputMode="numeric"
                                min="0"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-lg shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                value={data.total_production}
                                onChange={(e) => setData('total_production', e.target.value)}
                            />
                            <InputError message={errors.total_production} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="total_reject" value="Total Reject (NG)" />
                            <input
                                id="total_reject"
                                type="number"
                                inputMode="numeric"
                                min="0"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-lg shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                value={data.total_reject}
                                onChange={(e) => setData('total_reject', e.target.value)}
                            />
                            <InputError message={errors.total_reject} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="total_repair" value="Total Repair" />
                            <input
                                id="total_repair"
                                type="number"
                                inputMode="numeric"
                                min="0"
                                className="mt-1 block w-full rounded-lg border-gray-300 py-3 text-lg shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                value={data.total_repair}
                                onChange={(e) => setData('total_repair', e.target.value)}
                            />
                            <InputError message={errors.total_repair} className="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="notes" value="Notes (optional)" />
                        <textarea
                            id="notes"
                            rows={2}
                            className="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} className="mt-1" />
                    </div>

                    <button
                        type="submit"
                        disabled={!data.product_id}
                        className="w-full rounded-lg bg-brand-600 px-4 py-4 text-lg font-semibold text-white shadow-sm transition hover:bg-brand-500 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Record Entry
                    </button>

                    {!data.product_id && (
                        <p className="text-center text-sm text-gray-500">
                            {!data.line_id
                                ? 'Select a line above to continue.'
                                : !data.product_model_id
                                    ? 'Select a model above to continue.'
                                    : 'Select a product above to continue.'}
                        </p>
                    )}
                </form>

                <div className="mb-3 mt-8 text-sm font-semibold text-gray-700">Today&apos;s Entries</div>

                <CrudTable
                    headers={[
                        'Time', 'Line', 'Model', 'Product',
                        { label: 'Production', className: 'text-right' },
                        { label: 'Reject', className: 'text-right' },
                        { label: 'Repair', className: 'text-right' },
                    ]}
                    rows={rows}
                    emptyMessage="No entries yet today."
                    rowClassName={(row) => (row.pending ? 'bg-amber-50/60 hover:bg-amber-50' : 'hover:bg-brand-50/50')}
                    renderTrailing={(row) =>
                        row.pending && (
                            <span className="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                Pending sync
                            </span>
                        )
                    }
                    renderCells={(row) => (
                        <>
                            <td className="px-4 py-3">{new Date(row.logged_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                            <td className="px-4 py-3">{row.line}</td>
                            <td className="px-4 py-3">{row.model}</td>
                            <td className="px-4 py-3">{row.product}</td>
                            <td className="px-4 py-3 text-right">{row.total_production}</td>
                            <td className="px-4 py-3 text-right">{row.total_reject}</td>
                            <td className="px-4 py-3 text-right">{row.total_repair}</td>
                        </>
                    )}
                />
            </div>
        </>
    );
}
