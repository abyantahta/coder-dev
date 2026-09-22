import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ stats, tables }) {
    return (
        <AdminLayout
            title="Overview"
            description="Kelola master data base proses Matex: raw material, suppliers, dan kedisiplinan RM."
        >
            <Head title="Master Data" />

            <div className="space-y-6">
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {Object.entries(stats).map(([key, value], index) => (
                        <div
                            key={key}
                            className="ui-panel ui-panel-interactive animate-fade-up p-5"
                            style={{ animationDelay: `${index * 40}ms` }}
                        >
                            <p className="ui-eyebrow">{key.replaceAll('_', ' ')}</p>
                            <p className="mt-3 font-display text-3xl font-bold tabular-nums text-ink">
                                {value}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {tables.map((table, index) => (
                        <Link
                            key={table.key}
                            href={route(table.route)}
                            className="ui-panel ui-panel-interactive animate-fade-up p-5"
                            style={{ animationDelay: `${120 + index * 40}ms` }}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h3 className="font-display font-semibold text-ink">
                                        {table.label}
                                    </h3>
                                    <p className="mt-1 text-sm text-ink-muted">
                                        {table.description}
                                    </p>
                                </div>
                                <span className="rounded-md bg-brand-muted px-2.5 py-1 text-sm font-semibold text-brand-deep">
                                    {table.count}
                                </span>
                            </div>
                            <p className="mt-4 text-sm font-semibold text-brand">Kelola →</p>
                        </Link>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
