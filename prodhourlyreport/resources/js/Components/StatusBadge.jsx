export default function StatusBadge({ active }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ${
                active ? 'bg-brand-100 text-brand-800' : 'bg-gray-100 text-gray-500'
            }`}
        >
            <span className={`h-1.5 w-1.5 rounded-full ${active ? 'bg-brand-500' : 'bg-gray-400'}`} />
            {active ? 'Active' : 'Inactive'}
        </span>
    );
}
