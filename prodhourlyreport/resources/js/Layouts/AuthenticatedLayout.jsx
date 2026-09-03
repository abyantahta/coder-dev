import ApplicationLogo from '@/Components/ApplicationLogo';
import {
    IconClose,
    IconCube,
    IconDashboard,
    IconEntry,
    IconLines,
    IconLogout,
    IconMenu,
    IconTag,
    IconUsers,
} from '@/Components/Icons';
import SyncStatusBadge from '@/Components/SyncStatusBadge';
import { OfflineSyncProvider } from '@/lib/OfflineSyncContext';
import { canSubmitProduction, managesMasterData, roleLabel } from '@/utils/roles';
import { Dialog, DialogPanel, Transition, TransitionChild } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function resolveCapabilities(page) {
    const shared = page.props.auth?.can;
    const role = page.props.auth?.user?.role;

    // Prefer server-shared capabilities; fall back to role helpers for safety.
    return {
        showEntry: shared?.submitProduction ?? canSubmitProduction(role),
        showAdmin: shared?.manageMasterData ?? managesMasterData(role),
    };
}

const NAV_ICONS = {
    dashboard: IconDashboard,
    'entry.create': IconEntry,
    'lines.index': IconLines,
    'product-models.index': IconCube,
    'products.index': IconTag,
    'users.index': IconUsers,
};

function NavLinks({ items, onNavigate }) {
    return (
        <nav className="flex-1 space-y-1 px-3">
            {items.map((item) => {
                const Icon = NAV_ICONS[item.current];
                const active = route().current(item.current);
                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        onClick={onNavigate}
                        className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                            active ? 'bg-white/10 text-white' : 'text-brand-200/80 hover:bg-white/5 hover:text-white'
                        }`}
                    >
                        <Icon className="h-5 w-5 shrink-0" />
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}

function SidebarBody({ navItems, user, onNavigate }) {
    return (
        <>
            <Link href={route('dashboard')} className="flex items-center gap-2.5 px-5 py-6">
                <ApplicationLogo className="h-8 w-8 fill-current text-brand-300" />
                <span className="text-base font-semibold text-white">Prod Report</span>
            </Link>

            <NavLinks items={navItems} onNavigate={onNavigate} />

            <div className="border-t border-white/10 p-4">
                <div className="mb-3">
                    <SyncStatusBadge dark />
                </div>
                <Link href={route('profile.edit')} className="block rounded-lg px-2 py-1.5 transition hover:bg-white/5">
                    <p className="truncate text-sm font-medium text-white">{user.name}</p>
                    <p className="truncate text-xs text-brand-300">{roleLabel(user.role)}</p>
                </Link>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="mt-3 flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-brand-200 transition hover:bg-white/10 hover:text-white"
                >
                    <IconLogout className="h-4 w-4" />
                    Log Out
                </Link>
            </div>
        </>
    );
}

function FlashBanner() {
    const flash = usePage().props.flash ?? {};

    if (!flash.success && !flash.error) {
        return null;
    }

    return (
        <div className="mb-4 space-y-2">
            {flash.success && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {flash.success}
                </div>
            )}
            {flash.error && (
                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {flash.error}
                </div>
            )}
        </div>
    );
}

function LayoutChrome({ children }) {
    const page = usePage();
    const user = page.props.auth.user;
    const { showEntry, showAdmin } = resolveCapabilities(page);
    const [drawerOpen, setDrawerOpen] = useState(false);

    const navItems = useMemo(
        () =>
            [
                { href: route('dashboard'), label: 'Dashboard', current: 'dashboard', show: true },
                { href: route('entry.create'), label: 'Entry', current: 'entry.create', show: showEntry },
                { href: route('lines.index'), label: 'Lines', current: 'lines.index', show: showAdmin },
                { href: route('product-models.index'), label: 'Models', current: 'product-models.index', show: showAdmin },
                { href: route('products.index'), label: 'Products', current: 'products.index', show: showAdmin },
                { href: route('users.index'), label: 'Users', current: 'users.index', show: showAdmin },
            ].filter((item) => item.show),
        [showEntry, showAdmin],
    );

    return (
        <div className="min-h-screen bg-gray-50 lg:flex">
            <aside className="hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:w-64 lg:flex-col lg:bg-gradient-to-b lg:from-brand-950 lg:to-brand-900">
                <SidebarBody navItems={navItems} user={user} />
            </aside>

            <Transition show={drawerOpen}>
                <Dialog onClose={setDrawerOpen} className="relative z-40 lg:hidden">
                    <TransitionChild
                        enter="ease-out duration-200"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="ease-in duration-150"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="fixed inset-0 z-0 bg-gray-900/50" />
                    </TransitionChild>
                    <TransitionChild
                        enter="ease-out duration-200"
                        enterFrom="-translate-x-full"
                        enterTo="translate-x-0"
                        leave="ease-in duration-150"
                        leaveFrom="translate-x-0"
                        leaveTo="-translate-x-full"
                    >
                        <DialogPanel className="fixed inset-y-0 left-0 z-10 flex w-72 flex-col bg-gradient-to-b from-brand-950 to-brand-900">
                            <button
                                onClick={() => setDrawerOpen(false)}
                                aria-label="Close navigation menu"
                                className="absolute right-3 top-5 rounded-md p-1.5 text-brand-200 hover:bg-white/10"
                            >
                                <IconClose className="h-5 w-5" />
                            </button>
                            <SidebarBody navItems={navItems} user={user} onNavigate={() => setDrawerOpen(false)} />
                        </DialogPanel>
                    </TransitionChild>
                </Dialog>
            </Transition>

            <div className="flex min-h-screen flex-1 flex-col lg:pl-64">
                <header className="sticky top-0 z-20 flex items-center gap-3 border-b border-gray-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6 lg:hidden">
                    <button
                        onClick={() => setDrawerOpen(true)}
                        aria-label="Open navigation menu"
                        className="rounded-md p-2 text-gray-500 hover:bg-gray-100"
                    >
                        <IconMenu className="h-5 w-5" />
                    </button>
                    <Link href={route('dashboard')} className="flex items-center gap-2">
                        <ApplicationLogo className="h-6 w-6 fill-current text-brand-600" />
                        <span className="font-semibold text-gray-800">Prod Report</span>
                    </Link>

                    <div className="ms-auto flex items-center gap-3">
                        <SyncStatusBadge />
                        <Link
                            href={route('profile.edit')}
                            className="rounded-full bg-brand-50 px-3 py-1.5 text-sm font-medium text-brand-800"
                        >
                            {user.name.split(' ')[0]}
                        </Link>
                    </div>
                </header>

                <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <div className="mx-auto w-full max-w-7xl">
                        <FlashBanner />
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}

export default function AuthenticatedLayout({ children }) {
    return (
        <OfflineSyncProvider>
            <LayoutChrome>{children}</LayoutChrome>
        </OfflineSyncProvider>
    );
}
