import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Keep list filters in the URL query string (server-driven).
 * Search is debounced; selects apply immediately.
 */
export function useServerFilters(routeName, initialFilters = {}, { only } = {}) {
    const [filters, setFilters] = useState(() => normalize(initialFilters));
    const debounceRef = useRef(null);
    const filtersRef = useRef(filters);
    filtersRef.current = filters;

    useEffect(() => {
        setFilters(normalize(initialFilters));
    }, [
        initialFilters.search,
        initialFilters.line_id,
        initialFilters.category,
        initialFilters.role,
        initialFilters.date,
        initialFilters.product_model_id,
        initialFilters.product_id,
        initialFilters.page,
    ]);

    const apply = useCallback(
        (next, { debounce = false } = {}) => {
            const cleaned = clean(typeof next === 'function' ? next(filtersRef.current) : next);
            setFilters(cleaned);
            filtersRef.current = cleaned;

            const visit = () => {
                router.get(route(routeName), cleaned, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    ...(only ? { only } : {}),
                });
            };

            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
                debounceRef.current = null;
            }

            if (debounce) {
                debounceRef.current = setTimeout(visit, 350);
            } else {
                visit();
            }
        },
        [routeName, only],
    );

    useEffect(() => () => {
        if (debounceRef.current) clearTimeout(debounceRef.current);
    }, []);

    const setFilter = useCallback(
        (key, value, options = {}) => {
            apply((current) => {
                const next = { ...current, [key]: value };
                // Changing filters other than page resets to page 1.
                if (key !== 'page') {
                    delete next.page;
                }
                return next;
            }, options);
        },
        [apply],
    );

    const setSearch = useCallback(
        (value) => setFilter('search', value, { debounce: true }),
        [setFilter],
    );

    const setPage = useCallback(
        (page) => setFilter('page', String(page)),
        [setFilter],
    );

    const reset = useCallback(() => apply({}), [apply]);

    return { filters, setFilter, setSearch, setPage, apply, reset };
}

function normalize(filters) {
    const next = {};
    Object.entries(filters ?? {}).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        next[key] = String(value);
    });
    return next;
}

function clean(filters) {
    const next = {};
    Object.entries(filters ?? {}).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') return;
        next[key] = String(value);
    });
    return next;
}
