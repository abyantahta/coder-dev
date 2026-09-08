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
                `items-center rounded-md border border-transparent text-xs font-semibold  text-white transition duration-150 ease-in-out hover:brightness-110 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-greenTheme focus:ring-offset-2 active:bg-gray-900 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
