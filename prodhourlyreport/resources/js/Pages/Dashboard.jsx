import CrudTable from '@/Components/CrudTable';
import FilterBar, { FilterField, FilterSearch, FilterSelect } from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useCatalogCascade } from '@/lib/useCatalogCascade';
import { useServerFilters } from '@/lib/useServerFilters';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const SERIES = [
    { key: 'total_production', label: 'Production', color: '#0ca30c' },
    { key: 'total_repair', label: 'Repair', color: '#fab219' },
    { key: 'total_reject', label: 'Reject (NG)', color: '#d03b3b' },
];

const GRID = '#e1e0d9';
const AXIS = '#c3c2b7';
const MUTED = '#898781';

function CustomTooltip({ active, payload, label }) {
    if (!active || !payload?.length) return null;
    return (
        <div className="rounded-lg border border-gray-100 bg-white px-3 py-2 text-sm shadow-lg">
            <div className="mb-1 font-medium text-gray-900">{label}</div>
            {payload.map((entry) => (
                <div key={entry.dataKey} className="flex items-center gap-2 py-0.5">
                    <span className="inline-block h-0.5 w-3" style={{ backgroundColor: entry.color }} />
                    <span className="font-semibold text-gray-900">{entry.value}</span>
                    <span className="text-gray-500">{entry.name}</span>
                </div>
            ))}
        </div>
    );
}

function CustomLegend({ payload }) {
    return (
        <div className="flex flex-wrap items-center gap-4 text-sm">
            {payload.map((entry) => (
                <span key={entry.dataKey} className="flex items-center gap-1.5">
                    <span className="inline-block h-0.5 w-4" style={{ backgroundColor: entry.color }} />
                    <span className="text-gray-600">{entry.value}</span>
                </span>
            ))}
        </div>
    );
}

const STAT_TONE_DOT = {
    brand: 'bg-brand-500',
    warning: 'bg-amber-400',
    critical: 'bg-accent-500',
    neutral: 'bg-gray-400',
};

function StatTile({ label, value, tone }) {
    return (
        <div className="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">{label}</p>
            <div className="mt-2 flex items-center justify-between">
                <span className="text-2xl font-semibold text-gray-900">{value.toLocaleString()}</span>
                <span className={`h-2.5 w-2.5 rounded-full ${STAT_TONE_DOT[tone]}`} />
            </div>
        </div>
    );
}

export default function Dashboard({
    filters: initialFilters,
    lines,
    logs,
    chartData = [],
    selectedProductName = null,
    totals,
    categories = [],
}) {
    const { filters, setFilter, setSearch, setPage, apply } = useServerFilters('dashboard', initialFilters, {
        only: ['logs', 'filters', 'chartData', 'selectedProductName', 'totals'],
    });

    const { models, products } = useCatalogCascade({
        lineId: filters.line_id || '',
        modelId: filters.product_model_id || '',
        category: filters.category || '',
    });

    const lineOptions = useMemo(
        () => lines.map((line) => ({ value: String(line.id), label: line.name })),
        [lines],
    );
    const modelOptions = useMemo(
        () => models.map((model) => ({ value: String(model.id), label: model.name })),
        [models],
    );
    const productOptions = useMemo(
        () => products.map((product) => ({ value: String(product.id), label: product.name })),
        [products],
    );

    const logRows = logs?.data ?? logs ?? [];
    const exportUrl = route('dashboard.export', filters);

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <PageHeader
                title="Dashboard"
                description="Today's production journey across your lines."
                action={
                    <a
                        href={exportUrl}
                        className="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-500"
                    >
                        Generate Data (.xlsx)
                    </a>
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatTile label="Production" value={totals?.production ?? 0} tone="brand" />
                <StatTile label="Repair" value={totals?.repair ?? 0} tone="warning" />
                <StatTile label="Reject (NG)" value={totals?.reject ?? 0} tone="critical" />
                <StatTile label="Entries logged" value={totals?.entries ?? 0} tone="neutral" />
            </div>

            <FilterBar>
                <FilterField label="Date" className="min-w-[150px]">
                    <input
                        type="date"
                        className="w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        value={filters.date ?? ''}
                        onChange={(e) => setFilter('date', e.target.value)}
                    />
                </FilterField>
                <FilterSelect
                    label="Line"
                    value={filters.line_id ?? ''}
                    onChange={(value) => apply({ ...filters, line_id: value, product_model_id: '', product_id: '', page: '' })}
                    options={lineOptions}
                    emptyLabel="All lines"
                />
                <FilterSelect
                    label="Model"
                    value={filters.product_model_id ?? ''}
                    onChange={(value) => apply({ ...filters, product_model_id: value, product_id: '', page: '' })}
                    options={modelOptions}
                    emptyLabel="All models"
                    disabled={!filters.line_id}
                />
                <FilterSelect
                    label="Category"
                    value={filters.category ?? ''}
                    onChange={(value) => apply({ ...filters, category: value, product_id: '', page: '' })}
                    options={categories}
                    emptyLabel="All categories"
                />
                <FilterSelect
                    label="Product"
                    value={filters.product_id ?? ''}
                    onChange={(value) => setFilter('product_id', value)}
                    options={productOptions}
                    emptyLabel="All products"
                    disabled={!filters.product_model_id}
                />
                <FilterSearch
                    value={filters.search ?? ''}
                    onChange={setSearch}
                    placeholder="Product, leader, line…"
                />
            </FilterBar>

            <div className="mb-6 rounded-xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 className="text-sm font-semibold text-gray-700">
                        {selectedProductName
                            ? `${selectedProductName} — production journey (${filters.date})`
                            : 'Production journey'}
                    </h3>
                    {chartData.length > 0 && (
                        <CustomLegend
                            payload={SERIES.map((s) => ({ dataKey: s.key, value: s.label, color: s.color }))}
                        />
                    )}
                </div>

                {!filters.product_id ? (
                    <div className="flex h-64 items-center justify-center rounded-lg bg-gray-50/60 text-sm text-gray-400">
                        Select a line, model, and product above to see its production journey for the day.
                    </div>
                ) : chartData.length === 0 ? (
                    <div className="flex h-64 items-center justify-center rounded-lg bg-gray-50/60 text-sm text-gray-400">
                        No entries logged for this product on {filters.date}.
                    </div>
                ) : (
                    <ResponsiveContainer width="100%" height={360}>
                        <LineChart data={chartData} margin={{ top: 8, right: 16, left: 0, bottom: 0 }}>
                            <CartesianGrid stroke={GRID} vertical={false} />
                            <XAxis dataKey="timeLabel" tickLine={false} axisLine={{ stroke: AXIS }} tick={{ fill: MUTED, fontSize: 12 }} />
                            <YAxis allowDecimals={false} tickLine={false} axisLine={{ stroke: AXIS }} tick={{ fill: MUTED, fontSize: 12 }} />
                            <Tooltip cursor={{ stroke: AXIS, strokeWidth: 1 }} content={<CustomTooltip />} />
                            {SERIES.map((s) => (
                                <Line
                                    key={s.key}
                                    type="monotone"
                                    dataKey={s.key}
                                    name={s.label}
                                    stroke={s.color}
                                    strokeWidth={2}
                                    dot={{ r: 4, strokeWidth: 0, fill: s.color }}
                                    activeDot={{ r: 6 }}
                                />
                            ))}
                        </LineChart>
                    </ResponsiveContainer>
                )}
            </div>

            <div className="mb-3 text-sm font-semibold text-gray-700">Entries ({logs?.total ?? logRows.length})</div>

            <CrudTable
                headers={[
                    'Time', 'Line', 'Model', 'Product', 'Leader',
                    { label: 'Production', className: 'text-right' },
                    { label: 'Repair', className: 'text-right' },
                    { label: 'Reject', className: 'text-right' },
                ]}
                rows={logRows}
                emptyMessage="No entries for this filter."
                renderCells={(log) => (
                    <>
                        <td className="px-4 py-3">{new Date(log.logged_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                        <td className="px-4 py-3">{log.line?.name}</td>
                        <td className="px-4 py-3">{log.product_model?.name}</td>
                        <td className="px-4 py-3">{log.product?.name}</td>
                        <td className="px-4 py-3">{log.user?.name}</td>
                        <td className="px-4 py-3 text-right">{log.total_production}</td>
                        <td className="px-4 py-3 text-right">{log.total_repair}</td>
                        <td className="px-4 py-3 text-right">{log.total_reject}</td>
                    </>
                )}
            />

            <Pagination paginator={logs} onPageChange={setPage} />
        </AuthenticatedLayout>
    );
}
