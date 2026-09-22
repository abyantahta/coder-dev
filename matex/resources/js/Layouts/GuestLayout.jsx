import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-ink px-4 py-10">
            <div
                className="pointer-events-none absolute inset-0 opacity-40"
                style={{
                    backgroundImage:
                        'radial-gradient(circle at 20% 20%, rgba(20,158,114,0.35), transparent 42%), radial-gradient(circle at 80% 10%, rgba(196,92,38,0.22), transparent 36%), linear-gradient(160deg, #0F2137 0%, #12263A 45%, #0B6E4F 140%)',
                }}
            />
            <div
                className="pointer-events-none absolute inset-0 opacity-[0.07]"
                style={{
                    backgroundImage:
                        'linear-gradient(rgba(255,255,255,0.35) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.35) 1px, transparent 1px)',
                    backgroundSize: '48px 48px',
                }}
            />

            <div className="relative z-10 w-full max-w-md animate-fade-up">
                <Link href="/" className="mb-8 flex flex-col items-center gap-3 text-center">
                    <ApplicationLogo className="h-16 w-16 shadow-lift transition duration-320 ease-matex hover:scale-105" />
                    <div>
                        <span className="font-display text-3xl font-bold tracking-[0.18em] text-white">
                            MATEX
                        </span>
                        <p className="mt-2 text-xs font-medium uppercase tracking-[0.2em] text-brand-line">
                            Material Exchange Portal
                        </p>
                    </div>
                </Link>

                <div className="rounded-panel border border-white/10 bg-surface/95 p-6 shadow-lift backdrop-blur-sm sm:p-8">
                    {children}
                </div>
            </div>
        </div>
    );
}
