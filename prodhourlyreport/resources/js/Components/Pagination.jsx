import SecondaryButton from '@/Components/SecondaryButton';

/**
 * Simple pagination for Laravel LengthAwarePaginator props.
 * Expects `{ data, current_page, last_page, total, from, to, links }` or Inertia-style paginator.
 */
export default function Pagination({ paginator, onPageChange }) {
    if (!paginator || paginator.last_page <= 1) {
        return null;
    }

    const current = paginator.current_page;
    const last = paginator.last_page;

    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm text-gray-500">
                Showing {paginator.from ?? 0}–{paginator.to ?? 0} of {paginator.total}
            </p>
            <div className="flex items-center gap-2">
                <SecondaryButton
                    type="button"
                    disabled={current <= 1}
                    onClick={() => onPageChange(current - 1)}
                >
                    Previous
                </SecondaryButton>
                <span className="text-sm text-gray-600">
                    Page {current} / {last}
                </span>
                <SecondaryButton
                    type="button"
                    disabled={current >= last}
                    onClick={() => onPageChange(current + 1)}
                >
                    Next
                </SecondaryButton>
            </div>
        </div>
    );
}
