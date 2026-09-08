export default function ZebraCell({ index, className = "", children, ...props }) {
    const zebra = index % 2 !== 0 ? "bg-green-50" : "bg-white";
    return (
        <td className={`${zebra} ${className}`} {...props}>
            {children}
        </td>
    );
}
