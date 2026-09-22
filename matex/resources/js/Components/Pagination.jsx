import { Link } from '@inertiajs/react';

export default function Pagination({ paginator }) {
    const links = Array.isArray(paginator?.links)
        ? paginator.links
        : paginator?.meta?.links || [];

    if (!links.length || links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center justify-end gap-1 border-t border-line px-4 py-3">
            {links.map((link, index) => {
                const label = String(link.label)
                    .replace('&laquo;', '«')
                    .replace('&raquo;', '»');

                if (!link.url) {
                    return (
                        <span
                            key={`${label}-${index}`}
                            className="rounded-md px-2.5 py-1 text-sm text-ink-faint"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={`${label}-${index}`}
                        href={link.url}
                        preserveState
                        preserveScroll
                        className={`rounded-md px-2.5 py-1 text-sm transition duration-220 ease-matex ${
                            link.active
                                ? 'bg-brand font-semibold text-white'
                                : 'text-ink-soft hover:bg-canvas-soft'
                        }`}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}
