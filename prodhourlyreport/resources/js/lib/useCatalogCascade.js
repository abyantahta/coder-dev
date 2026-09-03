import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Lazy-load models for a line and products for a model via /catalog/* JSON APIs.
 */
export function useCatalogCascade({ lineId, modelId, category = '', productSearch = '' }) {
    const [models, setModels] = useState([]);
    const [products, setProducts] = useState([]);
    const [loadingModels, setLoadingModels] = useState(false);
    const [loadingProducts, setLoadingProducts] = useState(false);
    const searchTimer = useRef(null);

    useEffect(() => {
        if (!lineId) {
            setModels([]);
            return undefined;
        }

        const controller = new AbortController();
        setLoadingModels(true);

        fetch(route('catalog.models', { line_id: lineId }), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then((res) => res.json())
            .then((json) => setModels(json.data ?? []))
            .catch(() => {
                if (!controller.signal.aborted) setModels([]);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingModels(false);
            });

        return () => controller.abort();
    }, [lineId]);

    const loadProducts = useCallback((productModelId, opts = {}) => {
        if (!productModelId) {
            setProducts([]);
            return;
        }

        const params = new URLSearchParams({
            product_model_id: String(productModelId),
            limit: '50',
        });
        if (opts.category) params.set('category', opts.category);
        if (opts.search) params.set('search', opts.search);

        const controller = new AbortController();
        setLoadingProducts(true);

        fetch(`${route('catalog.products')}?${params.toString()}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then((res) => res.json())
            .then((json) => setProducts(json.data ?? []))
            .catch(() => {
                if (!controller.signal.aborted) setProducts([]);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoadingProducts(false);
            });

        return () => controller.abort();
    }, []);

    useEffect(() => {
        if (!modelId) {
            setProducts([]);
            return undefined;
        }

        if (searchTimer.current) clearTimeout(searchTimer.current);

        let abortFetch = () => {};

        searchTimer.current = setTimeout(() => {
            abortFetch = loadProducts(modelId, { category, search: productSearch }) ?? (() => {});
        }, productSearch ? 300 : 0);

        return () => {
            if (searchTimer.current) clearTimeout(searchTimer.current);
            abortFetch();
        };
    }, [modelId, category, productSearch, loadProducts]);

    return { models, products, loadingModels, loadingProducts, reloadProducts: loadProducts };
}
