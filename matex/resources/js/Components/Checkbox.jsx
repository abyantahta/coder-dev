export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-line text-brand shadow-sm transition duration-220 ease-matex focus:ring-brand/40 ' +
                className
            }
        />
    );
}
