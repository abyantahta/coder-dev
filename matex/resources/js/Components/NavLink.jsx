import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'group relative inline-flex items-center px-1 pt-1 text-sm font-medium leading-5 transition duration-220 ease-matex focus:outline-none ' +
                (active
                    ? 'font-semibold text-brand'
                    : 'text-ink-muted hover:text-ink') +
                (className ? ` ${className}` : '')
            }
        >
            <span>{children}</span>
            <span
                className={
                    'absolute inset-x-0 -bottom-px h-0.5 origin-left rounded-full bg-brand transition duration-320 ease-matex ' +
                    (active
                        ? 'scale-x-100 opacity-100'
                        : 'scale-x-0 opacity-0 group-hover:scale-x-100 group-hover:opacity-70')
                }
            />
        </Link>
    );
}
