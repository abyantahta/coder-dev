/**
 * Shared list-table shell: card chrome, header row, hover states, and an
 * empty state. The trailing column is opt-in — pass onEdit/onDelete for the
 * common admin Edit/Delete case, a custom renderTrailing for anything else
 * (e.g. a status badge), or omit both for a plain read-only table.
 *
 * `headers` entries can be a plain string (left-aligned) or
 * `{ label, className }` for a column that needs different alignment
 * (e.g. `{ label: 'Production', className: 'text-right' }`).
 */
export default function CrudTable({
    headers,
    rows,
    rowKey = (row) => row.id,
    rowClassName = () => 'hover:bg-brand-50/50',
    renderCells,
    onEdit,
    onDelete,
    renderTrailing,
    emptyMessage = 'Nothing here yet.',
}) {
    const trailing =
        renderTrailing ??
        (onEdit || onDelete
            ? (row) => (
                  <>
                      {onEdit && (
                          <button
                              className="me-2 rounded-md px-2.5 py-1.5 font-medium text-brand-700 transition hover:bg-brand-50 hover:text-brand-900"
                              onClick={() => onEdit(row)}
                          >
                              Edit
                          </button>
                      )}
                      {onDelete && (
                          <button
                              className="rounded-md px-2.5 py-1.5 font-medium text-accent-600 transition hover:bg-accent-50 hover:text-accent-700"
                              onClick={() => onDelete(row)}
                          >
                              Delete
                          </button>
                      )}
                  </>
              )
            : null);

    const columnCount = headers.length + (trailing ? 1 : 0);

    return (
        <div className="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-100 text-sm">
                    <thead className="bg-gray-50/80 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            {headers.map((header) => {
                                const label = typeof header === 'string' ? header : header.label;
                                const className = typeof header === 'string' ? '' : (header.className ?? '');
                                return (
                                    <th key={label} className={`px-4 py-3 ${className}`}>{label}</th>
                                );
                            })}
                            {trailing && <th className="px-4 py-3" />}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={columnCount} className="px-4 py-10 text-center text-gray-400">
                                    {emptyMessage}
                                </td>
                            </tr>
                        )}
                        {rows.map((row) => (
                            <tr key={rowKey(row)} className={`transition ${rowClassName(row)}`}>
                                {renderCells(row)}
                                {trailing && (
                                    <td className="whitespace-nowrap px-4 py-3 text-right">{trailing(row)}</td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
