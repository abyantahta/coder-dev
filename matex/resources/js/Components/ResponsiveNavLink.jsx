import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2.5 pe-4 ps-3 text-base font-medium transition duration-220 ease-matex focus:outline-none ${
                active
                    ? 'border-brand bg-brand-muted text-brand-deep'
                    : 'border-transparent text-ink-muted hover:border-brand-line hover:bg-canvas-soft hover:text-ink'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
