import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    isMasterNavActive,
    masterNavForRole,
    masterNavHref,
    masterTabItemsForRole,
} from '@/Config/masterNav';
import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ title, description, actions, children }) {
    const { auth } = usePage().props;
    const role = auth.user.role;

    const primaryTabs = masterTabItemsForRole(role);
    const supplierQuickLinks = masterNavForRole(role).filter((item) => item.group === 'suppliers');

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="ui-eyebrow">Master Data</p>
                        <h2 className="ui-section-title mt-1">{title}</h2>
                        {description && (
                            <p className="mt-1 text-sm text-ink-muted">{description}</p>
                        )}
                    </div>
                    {actions}
                </div>
            }
        >
            <div className="ui-page space-y-5">
                <div className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        {primaryTabs.map((link) => {
                            const active = isMasterNavActive(link);
                            return (
                                <Link
                                    key={link.key}
                                    href={masterNavHref(link)}
                                    className={`ui-tab ${active ? 'ui-tab-active' : 'ui-tab-idle'}`}
                                >
                                    {link.label}
                                </Link>
                            );
                        })}
                    </div>

                    {route().current('admin.qad-suppliers.*') && supplierQuickLinks.length > 0 && (
                        <div className="flex flex-wrap items-center gap-2">
                            <span className="text-[0.625rem] font-semibold uppercase tracking-[0.14em] text-ink-faint">
                                Filter supplier
                            </span>
                            {supplierQuickLinks.map((link) => {
                                const active = isMasterNavActive(link);
                                return (
                                    <Link
                                        key={link.key}
                                        href={masterNavHref(link)}
                                        className={`ui-tab text-xs ${active ? 'ui-tab-active' : 'ui-tab-idle'}`}
                                    >
                                        {link.label}
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </div>
                <div className="animate-fade-up">{children}</div>
            </div>
        </AuthenticatedLayout>
    );
}
