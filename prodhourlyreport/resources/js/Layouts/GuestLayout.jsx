import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen">
            <div className="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div className="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-brand-500/20 blur-3xl" />
                <div className="pointer-events-none absolute bottom-0 right-0 h-96 w-96 rounded-full bg-accent-500/10 blur-3xl" />

                <Link href="/" className="relative flex items-center gap-3">
                    <ApplicationLogo className="h-10 w-10 fill-current text-brand-300" />
                    <span className="text-xl font-semibold text-white">Hourly Production Report</span>
                </Link>

                <div className="relative">
                    <h2 className="max-w-md text-3xl font-semibold leading-tight text-white">
                        Real-time production visibility, right from the shop floor.
                    </h2>
                    <p className="mt-4 max-w-sm text-brand-200">
                        Log hourly production, reject, and repair counts — online or off — and watch the day&apos;s journey unfold on the dashboard.
                    </p>

                    <div className="mt-10 flex items-end gap-3" aria-hidden="true">
                        <div className="h-16 w-8 rounded-md bg-white/15" />
                        <div className="h-24 w-8 rounded-md bg-white/25" />
                        <div className="h-32 w-8 rounded-md bg-brand-300" />
                        <div className="h-20 w-8 rounded-md bg-white/15" />
                    </div>
                </div>

                <p className="relative text-xs text-brand-300/70">
                    &copy; {new Date().getFullYear()} Hourly Production Report
                </p>
            </div>

            <div className="flex flex-1 flex-col items-center justify-center bg-gray-50 px-4 py-10 sm:px-6">
                <div className="mb-8 flex items-center gap-2 lg:hidden">
                    <ApplicationLogo className="h-10 w-10 fill-current text-brand-600" />
                    <span className="text-lg font-semibold text-gray-800">Hourly Production Report</span>
                </div>

                <div className="w-full overflow-hidden rounded-2xl bg-white px-6 py-8 shadow-xl ring-1 ring-gray-900/5 sm:max-w-md sm:px-8">
                    {children}
                </div>
            </div>
        </div>
    );
}
