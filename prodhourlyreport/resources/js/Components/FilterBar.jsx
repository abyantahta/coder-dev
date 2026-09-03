import TextInput from '@/Components/TextInput';

/**
 * Shared filter strip for list pages (master data, users, dashboard).
 * Pass `filters` as [{ key, label, type: 'search'|'select', options?, placeholder?, className? }].
 */
export default function FilterBar({ children, className = '' }) {
    return (
        <div
            className={
                'mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm ' +
                className
            }
        >
            {children}
        </div>
    );
}

export function FilterField({ label, children, className = 'min-w-[160px]' }) {
    return (
        <div className={className}>
            {label && <label className="block text-xs font-medium text-gray-500">{label}</label>}
            <div className={label ? 'mt-1' : ''}>{children}</div>
        </div>
    );
}

export function FilterSearch({ value, onChange, placeholder = 'Search…', className = 'min-w-[200px] flex-1' }) {
    return (
        <FilterField label="Search" className={className}>
            <TextInput
                type="search"
                className="block w-full"
                value={value}
                placeholder={placeholder}
                onChange={(e) => onChange(e.target.value)}
            />
        </FilterField>
    );
}

export function FilterSelect({ label, value, onChange, options, emptyLabel = 'All', className = 'min-w-[150px]', disabled = false }) {
    return (
        <FilterField label={label} className={className}>
            <select
                className="w-full rounded-lg border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100"
                value={value ?? ''}
                disabled={disabled}
                onChange={(e) => onChange(e.target.value || '')}
            >
                <option value="">{emptyLabel}</option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </FilterField>
    );
}
