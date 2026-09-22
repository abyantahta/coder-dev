import EmptyState from '@/Components/EmptyState';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty } from '@/utils/qty';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ ready, history, receivingEnabled }) {
    const { errors } = usePage().props;
    const [partialOpenId, setPartialOpenId] = useState(null);
    const [qtyByNote, setQtyByNote] = useState({});
    const [processingId, setProcessingId] = useState(null);

    const submitReceive = (note, qty) => {
        setProcessingId(note.id);
        router.post(
            route('receivings.store', note.id),
            { received_qty: qty },
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="ui-section-title">Receiving PPIC</h2>
                    <p className="text-sm text-ink-muted">
                        Receiving berdasarkan konfirmasi OHP, push ke QAD.
                    </p>
                </div>
            }
        >
            <Head title="Receiving" />

            <div className="ui-page space-y-4">
                    {!receivingEnabled && (
                        <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                            <strong>Receiving sementara dinonaktifkan.</strong> Fitur ini
                            dimatikan lewat config — jangan proses receiving dulu sampai
                            diaktifkan kembali.
                        </div>
                    )}
                    <section>
                        <h3 className="mb-3 font-semibold text-ink">Siap Receive</h3>
                        {!ready.length ? (
                            <EmptyState
                                title="Tidak ada antrian receiving"
                                description="DN akan muncul di sini setelah OHP submit konfirmasi + SJ."
                            />
                        ) : (
                            <div className="space-y-3">
                                {ready.map((note) => {
                                    const isPartialAlready = note.received_qty > 0;
                                    const partialOpen = partialOpenId === note.id;
                                    const partialQty =
                                        qtyByNote[note.id] ?? String(note.remaining_qty);

                                    return (
                                        <div
                                            key={note.id}
                                            className="flex flex-wrap items-end justify-between gap-3 rounded-xl border border-line bg-surface p-4 shadow-sm"
                                        >
                                            <div>
                                                <p className="font-semibold text-ink">
                                                    {note.dn_number}
                                                </p>
                                                <p className="text-sm text-ink-muted">
                                                    {note.purchase_order?.po_number} ·{' '}
                                                    {note.purchase_order_item?.item?.item_number} ·{' '}
                                                    order {formatQty(note.qty)} kg
                                                </p>
                                                <p className="text-xs text-ink-faint">
                                                    RM: {note.purchase_order?.supplier_rm?.name} → OHP:{' '}
                                                    {note.delivery_schedule?.ohp_supplier?.name}
                                                </p>
                                                {isPartialAlready && (
                                                    <p className="mt-1 text-xs font-medium text-amber-700">
                                                        Sudah diterima {formatQty(note.received_qty)} kg
                                                        — sisa {formatQty(note.remaining_qty)} kg
                                                    </p>
                                                )}
                                            </div>

                                            {!receivingEnabled ? (
                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={route('delivery-notes.show', note.id)}
                                                        className="rounded-md border border-line px-3 py-2 text-sm"
                                                    >
                                                        Detail
                                                    </Link>
                                                    <span className="rounded-md bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700">
                                                        Receiving dinonaktifkan
                                                    </span>
                                                </div>
                                            ) : !partialOpen ? (
                                                <div className="flex items-center gap-2">
                                                    <Link
                                                        href={route('delivery-notes.show', note.id)}
                                                        className="rounded-md border border-line px-3 py-2 text-sm"
                                                    >
                                                        Detail
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        className="text-xs text-ink-muted underline hover:text-ink"
                                                        onClick={() => setPartialOpenId(note.id)}
                                                    >
                                                        Qty beda?
                                                    </button>
                                                    <PrimaryButton
                                                        className="bg-emerald-700 hover:bg-emerald-800"
                                                        disabled={processingId === note.id}
                                                        onClick={() =>
                                                            submitReceive(note, note.remaining_qty)
                                                        }
                                                    >
                                                        {isPartialAlready
                                                            ? `Receive Sisa (${formatQty(note.remaining_qty)} kg)`
                                                            : `Receive Penuh (${formatQty(note.remaining_qty)} kg)`}
                                                    </PrimaryButton>
                                                </div>
                                            ) : (
                                                <div className="flex items-end gap-2">
                                                    <div>
                                                        <TextInput
                                                            type="text"
                                                            inputMode="numeric"
                                                            className="w-28"
                                                            autoFocus
                                                            value={partialQty}
                                                            onChange={(e) =>
                                                                setQtyByNote((prev) => ({
                                                                    ...prev,
                                                                    [note.id]: e.target.value,
                                                                }))
                                                            }
                                                        />
                                                        <InputError
                                                            message={errors?.received_qty}
                                                            className="mt-1"
                                                        />
                                                    </div>
                                                    <button
                                                        type="button"
                                                        className="rounded-md border border-line px-3 py-2 text-sm"
                                                        onClick={() => setPartialOpenId(null)}
                                                    >
                                                        Batal
                                                    </button>
                                                    <PrimaryButton
                                                        className="bg-emerald-700 hover:bg-emerald-800"
                                                        disabled={processingId === note.id}
                                                        onClick={() =>
                                                            submitReceive(note, partialQty)
                                                        }
                                                    >
                                                        Receive
                                                    </PrimaryButton>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </section>

                    <section className="ui-panel">
                        <div className="border-b border-line px-5 py-4">
                            <h3 className="font-semibold text-ink">History Receiving</h3>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-line text-sm">
                                <thead className="bg-canvas-soft text-left text-xs uppercase text-ink-muted">
                                    <tr>
                                        <th className="px-4 py-3">DN</th>
                                        <th className="px-4 py-3">PO</th>
                                        <th className="px-4 py-3">Item</th>
                                        <th className="px-4 py-3">Qty Diterima</th>
                                        <th className="px-4 py-3">QAD</th>
                                        <th className="px-4 py-3">Oleh</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {history.data.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-4 py-3">
                                                {r.delivery_note?.dn_number}
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.delivery_note?.purchase_order?.po_number}
                                            </td>
                                            <td className="px-4 py-3">
                                                {
                                                    r.delivery_note?.purchase_order_item?.item
                                                        ?.item_number
                                                }
                                            </td>
                                            <td className="px-4 py-3">{formatQty(r.received_qty)}</td>
                                            <td className="px-4 py-3">
                                                <StatusBadge type="qad" value={r.qad_status} />
                                            </td>
                                            <td className="px-4 py-3">{r.receiver?.name}</td>
                                        </tr>
                                    ))}
                                    {!history.data.length && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="px-4 py-8 text-center text-ink-muted"
                                            >
                                                Belum ada history receiving.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>
            </div>
        </AuthenticatedLayout>
    );
}
