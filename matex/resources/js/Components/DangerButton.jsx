export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `pressable inline-flex items-center justify-center rounded-md border border-transparent bg-rose-600 px-4 py-2.5 text-sm font-semibold tracking-wide text-white shadow-sm hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 active:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:translate-y-0 ${className}`
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
