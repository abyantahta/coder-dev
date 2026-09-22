import { useEffect, useMemo, useRef, useState } from 'react';

export default function SearchableSelect({
    options = [],
    value,
    onChange,
    placeholder = 'Cari & pilih...',
    getOptionLabel = (o) => o.label,
    getOptionValue = (o) => String(o.value),
    className = '',
    autoFocus = false,
    focusToken = 0,
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const rootRef = useRef(null);
    const inputRef = useRef(null);

    const selected = useMemo(
        () => options.find((o) => getOptionValue(o) === String(value ?? '')),
        [options, value, getOptionValue],
    );

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) {
            return options.slice(0, 50);
        }
        return options
            .filter((o) => getOptionLabel(o).toLowerCase().includes(q))
            .slice(0, 50);
    }, [options, query, getOptionLabel]);

    useEffect(() => {
        if (!open) {
            setQuery('');
        }
    }, [open]);

    useEffect(() => {
        if (!autoFocus) {
            return undefined;
        }
        setOpen(true);
        const timer = setTimeout(() => inputRef.current?.focus(), 0);
        return () => clearTimeout(timer);
    }, [autoFocus, focusToken]);

    useEffect(() => {
        const onDocClick = (e) => {
            if (rootRef.current && !rootRef.current.contains(e.target)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onDocClick);
        return () => document.removeEventListener('mousedown', onDocClick);
    }, []);

    return (
        <div ref={rootRef} className={`relative ${className}`}>
            <button
                type="button"
                className="mt-1 flex w-full items-center justify-between rounded-md border border-line bg-surface px-3 py-2 text-left text-sm shadow-sm transition duration-220 ease-matex hover:border-brand-line focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand"
                onClick={() => {
                    setOpen((v) => !v);
                    setTimeout(() => inputRef.current?.focus(), 0);
                }}
            >
                <span className={selected ? 'text-ink' : 'text-ink-faint'}>
                    {selected ? getOptionLabel(selected) : placeholder}
                </span>
                <svg className="h-4 w-4 text-ink-faint" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                    />
                </svg>
            </button>

            {open && (
                <div className="absolute z-30 mt-1 w-full overflow-hidden rounded-md border border-line bg-surface shadow-lg">
                    <div className="border-b border-line p-2">
                        <input
                            ref={inputRef}
                            type="text"
                            className="w-full rounded-md border-line text-sm"
                            placeholder="Ketik untuk mencari..."
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Escape') {
                                    setOpen(false);
                                }
                                if (e.key === 'Enter' && filtered[0]) {
                                    e.preventDefault();
                                    onChange(getOptionValue(filtered[0]));
                                    setOpen(false);
                                }
                            }}
                        />
                    </div>
                    <ul className="max-h-56 overflow-auto py-1">
                        {filtered.length === 0 ? (
                            <li className="px-3 py-2 text-sm text-ink-muted">Tidak ada hasil</li>
                        ) : (
                            filtered.map((option) => {
                                const optionValue = getOptionValue(option);
                                const active = optionValue === String(value ?? '');
                                return (
                                    <li key={optionValue}>
                                        <button
                                            type="button"
                                            className={`block w-full px-3 py-2 text-left text-sm transition duration-220 ease-matex hover:bg-brand-muted ${
                                                active
                                                    ? 'bg-brand-muted font-medium text-brand-deep'
                                                    : 'text-ink-soft'
                                            }`}
                                            onClick={() => {
                                                onChange(optionValue);
                                                setOpen(false);
                                            }}
                                        >
                                            {getOptionLabel(option)}
                                        </button>
                                    </li>
                                );
                            })
                        )}
                    </ul>
                    {value && (
                        <button
                            type="button"
                            className="w-full border-t border-line px-3 py-2 text-left text-xs text-rose-600 hover:bg-rose-50"
                            onClick={() => {
                                onChange('');
                                setOpen(false);
                            }}
                        >
                            Hapus pilihan
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
