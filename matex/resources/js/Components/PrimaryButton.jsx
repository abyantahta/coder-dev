export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `pressable inline-flex items-center justify-center rounded-md border border-transparent bg-brand px-4 py-2.5 text-sm font-semibold tracking-wide text-white shadow-sm hover:bg-brand-deep focus:outline-none focus:ring-2 focus:ring-brand/40 focus:ring-offset-2 active:bg-brand-deep disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 ${className}`
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
