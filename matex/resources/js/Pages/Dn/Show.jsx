import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import StatusBadge from '@/Components/StatusBadge';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty } from '@/utils/qty';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ note, sjUrl, receivingEnabled }) {
    const { auth, errors } = usePage().props;
    const role = auth.user.role;
    const status = note.delivery_schedule?.status;
    const planDate = note.delivery_schedule?.scheduled_date;
    const currentDeliveryDate = (note.delivery_date || planDate || '').slice(0, 10);
    const [partialOpen, setPartialOpen] = useState(false);
    const [receiveQty, setReceiveQty] = useState(String(note.remaining_qty));
    const [receiveProcessing, setReceiveProcessing] = useState(false);

    const ohpForm = useForm({
        sj_document: null,
        notes: '',
    });

    const dateForm = useForm({
        delivery_date: currentDeliveryDate,
    });

    const canShip =
        role === 'supplier_rm' &&
        status === 'planned' &&
        Boolean(note.rm_sj_number || note.delivery_schedule?.rm_sj_number);
    const canOhp = role === 'supplier_ohp' && status === 'ship_confirmed' && !note.ohp_confirmation;
    const canReceive =
        ['ppic', 'admin'].includes(role) && status === 'ohp_ok' && !note.is_fully_received;
    const canEditDate = role === 'supplier_rm' && status === 'planned';

    const submitReceive = (qty) => {
        setReceiveProcessing(true);
        router.post(
            route('receivings.store', note.id),
            { received_qty: qty },
            { preserveScroll: true, onFinish: () => setReceiveProcessing(false) },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="ui-section-title">{note.dn_number}</h2>
                        <div className="mt-2">
                            <StatusBadge type="schedule" value={status} />
                        </div>
                    </div>
                    <a
                        href={route('delivery-notes.print', note.id)}
                        target="_blank"
                        rel="noreferrer"
                        className="pressable rounded-md bg-ink px-4 py-2.5 text-sm font-semibold text-white hover:bg-ink-soft"
                    >
                        Reprint DN
                    </a>
                </div>
            }
        >
            <Head title={`DN ${note.dn_number}`} />

            <div className="ui-page max-w-5xl space-y-4">
                    <section className="ui-panel animate-fade-up p-5">
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-ink-muted">PO Number</dt>
                                <dd className="font-medium">{note.purchase_order?.po_number}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Item</dt>
                                <dd className="font-medium">
                                    {note.purchase_order_item?.item?.item_number} —{' '}
                                    {note.purchase_order_item?.item?.description}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Qty</dt>
                                <dd className="font-medium">{formatQty(note.qty)} kg</dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Plan Kirim</dt>
                                <dd className="font-medium">
                                    {planDate
                                        ? new Date(planDate).toLocaleDateString('id-ID')
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Tanggal Pengiriman (DN)</dt>
                                <dd className="font-medium">
                                    {currentDeliveryDate
                                        ? new Date(currentDeliveryDate).toLocaleDateString('id-ID')
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Dari</dt>
                                <dd className="font-medium">
                                    {note.purchase_order?.supplier_rm?.name}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">Ke OHP</dt>
                                <dd className="font-medium">
                                    {note.delivery_schedule?.ohp_supplier?.name}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-muted">No. SJ Internal RM</dt>
                                <dd className="font-medium">
                                    {note.rm_sj_number ||
                                        note.delivery_schedule?.rm_sj_number ||
                                        '—'}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    {canEditDate && (
                        <section className="ui-panel p-5">
                            <h3 className="font-semibold text-ink">Ubah Tanggal Pengiriman</h3>
                            <p className="mt-1 text-sm text-ink-muted">
                                Default mengikuti plan. Ubah bila tanggal di DN perlu disesuaikan,
                                lalu reprint DN.
                            </p>
                            <div className="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                                <div className="sm:w-56">
                                    <InputLabel value="Tanggal DN" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 w-full"
                                        value={dateForm.data.delivery_date}
                                        onChange={(e) =>
                                            dateForm.setData('delivery_date', e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={dateForm.errors.delivery_date}
                                        className="mt-1"
                                    />
                                </div>
                                <PrimaryButton
                                    className="bg-brand"
                                    disabled={dateForm.processing}
                                    onClick={() =>
                                        dateForm.post(
                                            route('delivery-notes.update-delivery-date', note.id),
                                        )
                                    }
                                >
                                    Simpan Tanggal
                                </PrimaryButton>
                            </div>
                        </section>
                    )}

                    {canShip && (
                        <section className="rounded-xl border border-amber-200 bg-amber-50 p-5">
                            <h3 className="font-semibold text-amber-900">
                                Konfirmasi Pengiriman
                            </h3>
                            <p className="mt-1 text-sm text-amber-800">
                                Konfirmasi bahwa material akan/telah dikirim ke Supplier OHP sesuai
                                tanggal DN. Pastikan no. SJ internal sudah terisi, lalu reprint jika
                                perlu.
                            </p>
                            <p className="mt-2 text-sm font-medium text-amber-900">
                                SJ Internal:{' '}
                                {note.rm_sj_number ||
                                    note.delivery_schedule?.rm_sj_number ||
                                    '—'}
                            </p>
                            <PrimaryButton
                                className="mt-4 bg-amber-700 hover:bg-amber-800"
                                onClick={() =>
                                    router.post(
                                        route('delivery-notes.confirm-shipment', note.id),
                                    )
                                }
                            >
                                Konfirmasi Berangkat ke OHP
                            </PrimaryButton>
                        </section>
                    )}

                    {canOhp && (
                        <section className="rounded-xl border border-blue-200 bg-blue-50 p-5">
                            <h3 className="font-semibold text-blue-900">Konfirmasi Barang OK</h3>
                            <p className="mt-1 text-sm text-blue-800">
                                Upload foto/dokumen surat jalan berstempel untuk setiap DN.
                            </p>
                            <form
                                className="mt-4 space-y-3"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    ohpForm.post(route('delivery-notes.confirm-ohp', note.id), {
                                        forceFormData: true,
                                    });
                                }}
                            >
                                <div>
                                    <InputLabel value="Dokumen SJ berstempel" />
                                    <input
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        className="mt-1 block w-full text-sm"
                                        onChange={(e) =>
                                            ohpForm.setData(
                                                'sj_document',
                                                e.target.files?.[0] || null,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={ohpForm.errors.sj_document}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Catatan" />
                                    <textarea
                                        className="mt-1 w-full rounded-md border-line"
                                        rows={2}
                                        value={ohpForm.data.notes}
                                        onChange={(e) => ohpForm.setData('notes', e.target.value)}
                                    />
                                </div>
                                <PrimaryButton
                                    disabled={ohpForm.processing}
                                    className="bg-blue-700 hover:bg-blue-800"
                                >
                                    Submit Konfirmasi OHP
                                </PrimaryButton>
                            </form>
                        </section>
                    )}

                    {note.ohp_confirmation && (
                        <section className="ui-panel p-5">
                            <h3 className="font-semibold text-ink">Konfirmasi OHP</h3>
                            <p className="mt-2 text-sm text-ink-soft">
                                Oleh {note.ohp_confirmation.confirmer?.name} pada{' '}
                                {new Date(note.ohp_confirmation.confirmed_at).toLocaleString('id-ID')}
                            </p>
                            {sjUrl && (
                                <a
                                    href={sjUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="mt-2 inline-block text-sm font-medium text-brand hover:underline"
                                >
                                    Lihat dokumen SJ
                                </a>
                            )}
                        </section>
                    )}

                    {canReceive && !receivingEnabled && (
                        <section className="rounded-xl border border-rose-200 bg-rose-50 p-5">
                            <h3 className="font-semibold text-rose-900">
                                Receiving Sementara Dinonaktifkan
                            </h3>
                            <p className="mt-1 text-sm text-rose-800">
                                Fitur ini dimatikan lewat config — jangan proses receiving
                                barang ini dulu sampai diaktifkan kembali.
                            </p>
                        </section>
                    )}

                    {canReceive && receivingEnabled && (
                        <section className="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                            <h3 className="font-semibold text-emerald-900">Receiving PPIC</h3>
                            <p className="mt-1 text-sm text-emerald-800">
                                Proses receiving akan langsung di-push ke QAD.
                            </p>
                            {note.received_qty > 0 && (
                                <p className="mt-2 text-sm font-medium text-emerald-900">
                                    Sudah diterima {formatQty(note.received_qty)} kg dari{' '}
                                    {formatQty(note.qty)} kg — sisa {formatQty(note.remaining_qty)} kg
                                </p>
                            )}

                            {!partialOpen ? (
                                <div className="mt-4 flex items-center gap-3">
                                    <PrimaryButton
                                        className="bg-emerald-700 hover:bg-emerald-800"
                                        disabled={receiveProcessing}
                                        onClick={() => submitReceive(note.remaining_qty)}
                                    >
                                        {note.received_qty > 0
                                            ? `Receive Sisa (${formatQty(note.remaining_qty)} kg) + Push QAD`
                                            : `Receive Penuh (${formatQty(note.remaining_qty)} kg) + Push QAD`}
                                    </PrimaryButton>
                                    <button
                                        type="button"
                                        className="text-xs text-emerald-800 underline hover:text-emerald-900"
                                        onClick={() => setPartialOpen(true)}
                                    >
                                        Qty beda?
                                    </button>
                                </div>
                            ) : (
                                <div className="mt-4 flex flex-wrap items-end gap-3">
                                    <div>
                                        <InputLabel value="Qty Diterima (kg)" />
                                        <TextInput
                                            type="text"
                                            inputMode="numeric"
                                            className="mt-1 w-32"
                                            autoFocus
                                            value={receiveQty}
                                            onChange={(e) => setReceiveQty(e.target.value)}
                                        />
                                        <InputError message={errors?.received_qty} className="mt-1" />
                                    </div>
                                    <button
                                        type="button"
                                        className="rounded-md border border-line px-3 py-2 text-sm"
                                        onClick={() => setPartialOpen(false)}
                                    >
                                        Batal
                                    </button>
                                    <PrimaryButton
                                        className="bg-emerald-700 hover:bg-emerald-800"
                                        disabled={receiveProcessing}
                                        onClick={() => submitReceive(receiveQty)}
                                    >
                                        Receive & Push QAD
                                    </PrimaryButton>
                                </div>
                            )}
                        </section>
                    )}

                    {note.receivings?.length > 0 && (
                        <section className="ui-panel p-5">
                            <h3 className="font-semibold text-ink">
                                Receiving {note.is_fully_received ? '(Lunas)' : '(Parsial)'}
                            </h3>
                            <p className="mt-1 text-sm text-ink-muted">
                                Total diterima {formatQty(note.received_qty)} kg dari{' '}
                                {formatQty(note.qty)} kg
                                {!note.is_fully_received &&
                                    ` — sisa ${formatQty(note.remaining_qty)} kg`}
                            </p>
                            <div className="mt-3 divide-y divide-line">
                                {note.receivings.map((r) => (
                                    <div
                                        key={r.id}
                                        className="flex items-center justify-between py-2 text-sm"
                                    >
                                        <div>
                                            <span className="font-medium">
                                                {formatQty(r.received_qty)} kg
                                            </span>
                                            <span className="ms-2 text-ink-muted">
                                                oleh {r.receiver?.name} ·{' '}
                                                {new Date(r.received_at).toLocaleString('id-ID')}
                                            </span>
                                        </div>
                                        <StatusBadge type="qad" value={r.qad_status} />
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}
            </div>
        </AuthenticatedLayout>
    );
}
