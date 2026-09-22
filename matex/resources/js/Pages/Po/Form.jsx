import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatQty, isQtyInput, roundQty, toNum } from '@/utils/qty';
import {
    isWeekendDay,
    weekendCellClass,
    weekendHeaderClass,
    weekendShortLabel,
    weekdayLabel,
} from '@/utils/calendar';
import { Head, Link, useForm } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

function emptyItem() {
    return {
        item_number: '',
        qty_ordered: '',
        ohp_supplier_code: '',
        schedules: [],
    };
}

function pad2(n) {
    return String(n).padStart(2, '0');
}

function scheduledTotal(item) {
    return roundQty((item.schedules || []).reduce((sum, s) => sum + toNum(s.qty), 0));
}

function remainingQty(item) {
    return roundQty(toNum(item.qty_ordered) - scheduledTotal(item));
}

function itemLabel(itemsCatalog, itemNumber) {
    const found = itemsCatalog.find((i) => i.qad_code === itemNumber);
    if (!found) {
        return 'Item belum dipilih';
    }
    return `${found.qad_code} — ${found.description}`;
}

function itemShortLabel(itemsCatalog, itemNumber) {
    const found = itemsCatalog.find((i) => i.qad_code === itemNumber);
    if (!found) {
        return '—';
    }
    return found.qad_code;
}

function defaultOhpForItem(itemDefaults, itemNumber) {
    return itemDefaults?.[itemNumber]?.subcont_ohp_code || '';
}

function parseDueMonth(dueDate) {
    if (!dueDate || !/^\d{4}-\d{2}-\d{2}$/.test(dueDate)) {
        return null;
    }
    const [year, month] = dueDate.split('-').map(Number);
    const days = new Date(year, month, 0).getDate();
    return { year, month, days };
}

function dateForDay(dueMonth, day) {
    return `${dueMonth.year}-${pad2(dueMonth.month)}-${pad2(day)}`;
}

/** Default due date untuk PO baru: tanggal terakhir bulan depan. */
function defaultDueDate() {
    const now = new Date();
    const lastDayNextMonth = new Date(now.getFullYear(), now.getMonth() + 2, 0);
    return `${lastDayNextMonth.getFullYear()}-${pad2(lastDayNextMonth.getMonth() + 1)}-${pad2(lastDayNextMonth.getDate())}`;
}

function monthTitle(dueMonth) {
    const label = new Date(dueMonth.year, dueMonth.month - 1, 1).toLocaleDateString('id-ID', {
        month: 'long',
        year: 'numeric',
    });
    return label;
}

function rowOhp(item, itemDefaults) {
    if (item.ohp_supplier_code) {
        return item.ohp_supplier_code;
    }
    const fromSchedule = (item.schedules || []).find((s) => s.ohp_supplier_code)?.ohp_supplier_code;
    if (fromSchedule) {
        return fromSchedule;
    }
    return defaultOhpForItem(itemDefaults, item.item_number);
}

function qtyOnDay(item, dueMonth, day) {
    const date = dateForDay(dueMonth, day);
    const found = (item.schedules || []).find((s) => s.scheduled_date === date);
    return found?.qty ?? '';
}

function mapOrderToForm(order) {
    if (!order) {
        return {
            supplier_code: '',
            due_date: defaultDueDate(),
            notes: '',
            items: [emptyItem()],
        };
    }

    const schedulesByItem = {};
    (order.schedules || []).forEach((s) => {
        if (!schedulesByItem[s.purchase_order_item_id]) {
            schedulesByItem[s.purchase_order_item_id] = [];
        }
        schedulesByItem[s.purchase_order_item_id].push({
            scheduled_date: s.scheduled_date?.slice(0, 10),
            qty: s.qty === null || s.qty === undefined ? '' : String(toNum(s.qty)),
            ohp_supplier_code: s.ohp_supplier?.code || '',
        });
    });

    return {
        po_number: order.po_number,
        supplier_code: order.supplier_rm?.code || '',
        due_date: order.due_date?.slice(0, 10),
        notes: order.notes || '',
        items: (order.items || []).map((item) => {
            const schedules = schedulesByItem[item.id] || [];
            return {
                item_number: item.item?.item_number,
                qty_ordered:
                    item.qty_ordered === null || item.qty_ordered === undefined
                        ? ''
                        : String(toNum(item.qty_ordered)),
                ohp_supplier_code: schedules[0]?.ohp_supplier_code || '',
                schedules,
            };
        }),
    };
}

function cleanItemsForSubmit(items) {
    return items.map(({ ohp_supplier_code, ...item }) => ({
        ...item,
        qty_ordered: toNum(item.qty_ordered),
        schedules: (item.schedules || [])
            .filter((s) => s.scheduled_date && toNum(s.qty) > 0 && s.ohp_supplier_code)
            .map((s) => ({
                ...s,
                qty: toNum(s.qty),
            })),
    }));
}

export default function Form({ order, qadSuppliers, qadSuppliersOhp, qadItems, itemDefaults }) {
    const editing = Boolean(order);
    const { data, setData, post, put, processing, errors, transform } = useForm(
        mapOrderToForm(order),
    );
    const [focusItemIndex, setFocusItemIndex] = useState(null);
    const [focusToken, setFocusToken] = useState(0);
    const itemSectionRef = useRef(null);

    transform((form) => ({
        ...form,
        items: cleanItemsForSubmit(form.items),
    }));

    const dueMonth = parseDueMonth(data.due_date);
    const dayColumns = dueMonth
        ? Array.from({ length: dueMonth.days }, (_, i) => i + 1)
        : [];

    const scheduleItems = data.items
        .map((item, index) => ({ item, index }))
        .filter(({ item }) => item.item_number && toNum(item.qty_ordered) > 0);

    const mismatchItems = scheduleItems.filter(({ item }) => remainingQty(item) !== 0);
    const hasMismatch = mismatchItems.length > 0;

    const addItem = useCallback(() => {
        const last = data.items[data.items.length - 1];
        const lastIsEmpty =
            last && !String(last.item_number || '').trim() && !String(last.qty_ordered || '').trim();

        if (lastIsEmpty) {
            setFocusItemIndex(data.items.length - 1);
            setFocusToken((token) => token + 1);
            return;
        }

        setData('items', [...data.items, emptyItem()]);
        setFocusItemIndex(data.items.length);
        setFocusToken((token) => token + 1);
    }, [data.items, setData]);

    useEffect(() => {
        const onKeyDown = (event) => {
            if (event.code !== 'CapsLock' && event.key !== 'CapsLock') {
                return;
            }
            if (!itemSectionRef.current?.contains(event.target)) {
                return;
            }
            event.preventDefault();
            addItem();
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [addItem]);

    const submit = (e) => {
        e.preventDefault();
        if (editing) {
            put(route('purchase-orders.update', order.id));
        } else {
            post(route('purchase-orders.store'));
        }
    };

    const updateItem = (index, key, value) => {
        const next = [...data.items];
        next[index] = { ...next[index], [key]: value };

        if (key === 'item_number') {
            const defaultOhp = defaultOhpForItem(itemDefaults, value);
            next[index].ohp_supplier_code = defaultOhp;
            next[index].schedules = (next[index].schedules || []).map((s) => ({
                ...s,
                ohp_supplier_code: defaultOhp,
            }));
        }

        setData('items', next);
    };

    const setRowOhp = (itemIndex, ohpSupplierCode) => {
        const next = [...data.items];
        const item = next[itemIndex];
        const schedules = (item.schedules || []).map((s) => ({
            ...s,
            ohp_supplier_code: ohpSupplierCode,
        }));
        next[itemIndex] = { ...item, ohp_supplier_code: ohpSupplierCode, schedules };
        setData('items', next);
    };

    const setDayQty = (itemIndex, day, qty) => {
        if (!dueMonth) {
            return;
        }

        const next = [...data.items];
        const item = next[itemIndex];
        const date = dateForDay(dueMonth, day);
        const ohp = rowOhp(item, itemDefaults);
        let schedules = [...(item.schedules || [])];
        const existingIndex = schedules.findIndex((s) => s.scheduled_date === date);

        if (!qty || toNum(qty) <= 0) {
            schedules = schedules.filter((s) => s.scheduled_date !== date);
        } else if (existingIndex >= 0) {
            schedules[existingIndex] = {
                ...schedules[existingIndex],
                qty,
                ohp_supplier_code: schedules[existingIndex].ohp_supplier_code || ohp,
            };
        } else {
            schedules.push({
                scheduled_date: date,
                qty,
                ohp_supplier_code: ohp,
            });
        }

        next[itemIndex] = { ...item, schedules };
        setData('items', next);
    };

    const remappingSchedulesToDueMonth = (itemsList, newDueDate) => {
        const month = parseDueMonth(newDueDate);
        if (!month) {
            return itemsList;
        }

        return itemsList.map((item) => {
            const byDay = {};
            (item.schedules || []).forEach((s) => {
                if (!s.scheduled_date || toNum(s.qty) <= 0) {
                    return;
                }
                const day = Number(s.scheduled_date.slice(8, 10));
                if (!Number.isFinite(day) || day < 1 || day > month.days) {
                    return;
                }
                byDay[day] = {
                    scheduled_date: dateForDay(month, day),
                    qty: s.qty,
                    ohp_supplier_code:
                        s.ohp_supplier_code || defaultOhpForItem(itemDefaults, item.item_number),
                };
            });
            return {
                ...item,
                schedules: Object.values(byDay),
            };
        });
    };

    const onDueDateChange = (value) => {
        setData({
            ...data,
            due_date: value,
            items: remappingSchedulesToDueMonth(data.items, value),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="ui-section-title">
                    {editing ? `Edit Draft ${order.po_number}` : 'Buat Draft PO'}
                </h2>
            }
        >
            <Head title={editing ? 'Edit PO' : 'Buat PO'} />

            <div className="ui-page">
                <form onSubmit={submit} className="w-full space-y-4">
                    <div className="ui-panel animate-fade-up p-5 sm:p-6">
                        <h3 className="mb-4 font-display font-semibold text-ink">Header PO</h3>
                        <div className="grid gap-4 md:grid-cols-2">
                            {editing && (
                                <div>
                                    <InputLabel value="No. PO" />
                                    <TextInput
                                        className="mt-1 w-full"
                                        value={data.po_number}
                                        onChange={(e) => setData('po_number', e.target.value)}
                                    />
                                    <InputError message={errors.po_number} className="mt-1" />
                                </div>
                            )}
                            <div>
                                <InputLabel value="Due Date" />
                                <TextInput
                                    type="date"
                                    className="mt-1 w-full"
                                    value={data.due_date}
                                    onChange={(e) => onDueDateChange(e.target.value)}
                                />
                                <InputError message={errors.due_date} className="mt-1" />
                                {dueMonth && (
                                    <p className="mt-1 text-xs text-ink-muted">
                                        Kolom jadwal memakai bulan{' '}
                                        <span className="font-medium text-ink-soft">
                                            {monthTitle(dueMonth)}
                                        </span>
                                    </p>
                                )}
                            </div>
                            <div>
                                <InputLabel value="Supplier Raw Material" />
                                <SearchableSelect
                                    options={qadSuppliers}
                                    value={data.supplier_code}
                                    placeholder="Ketik kode / nama supplier..."
                                    getOptionValue={(s) => s.qad_code}
                                    getOptionLabel={(s) =>
                                        `${s.qad_code} — ${s.name}${s.city ? ` (${s.city})` : ''}`
                                    }
                                    onChange={(val) => setData('supplier_code', val)}
                                />
                                <InputError message={errors.supplier_code} className="mt-1" />
                            </div>
                            <div className="md:col-span-2">
                                <InputLabel value="Catatan" />
                                <textarea
                                    className="mt-1 w-full rounded-md border-line"
                                    rows={2}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                            </div>
                        </div>
                        <p className="mt-3 text-xs text-ink-muted">
                            Tujuan OHP diisi per baris part di jadwal pengiriman, sehingga satu PO
                            bisa mengirim ke beberapa supplier OHP.
                        </p>
                    </div>

                    {/* Section 1: Item + Qty */}
                    <div ref={itemSectionRef} className="ui-panel p-5 sm:p-6">
                        <div className="mb-1 flex items-center gap-2">
                            <span className="inline-flex h-6 w-6 items-center justify-center rounded-md bg-brand text-xs font-bold text-white shadow-sm">
                                1
                            </span>
                            <h3 className="font-display font-semibold text-ink">Item Order & Qty</h3>
                        </div>
                        <p className="mb-5 text-sm text-ink-muted">
                            Isi item dan qty order terlebih dahulu. Subcont (OHP) default item akan
                            dipakai sebagai tujuan jadwal.
                        </p>

                        <div className="space-y-4">
                            {data.items.map((item, itemIndex) => (
                                <div
                                    key={itemIndex}
                                    className="grid gap-3 rounded-lg border border-line bg-canvas-soft p-4 md:grid-cols-[1fr_180px_auto]"
                                >
                                    <div>
                                        <InputLabel value="Item Number" />
                                        <SearchableSelect
                                            options={qadItems}
                                            value={item.item_number}
                                            placeholder="Ketik item number / deskripsi..."
                                            getOptionValue={(i) => i.qad_code}
                                            getOptionLabel={(i) =>
                                                `${i.qad_code} — ${i.description}`
                                            }
                                            autoFocus={focusItemIndex === itemIndex}
                                            focusToken={focusToken}
                                            onChange={(val) =>
                                                updateItem(itemIndex, 'item_number', val)
                                            }
                                        />
                                        <InputError
                                            message={errors[`items.${itemIndex}.item_number`]}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel value="Qty Order (kg)" />
                                        <TextInput
                                            type="text"
                                            inputMode="numeric"
                                            className="mt-1 w-full"
                                            value={item.qty_ordered}
                                            onChange={(e) => {
                                                if (isQtyInput(e.target.value)) {
                                                    updateItem(
                                                        itemIndex,
                                                        'qty_ordered',
                                                        e.target.value,
                                                    );
                                                }
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    addItem();
                                                }
                                            }}
                                        />
                                        <InputError
                                            message={errors[`items.${itemIndex}.qty_ordered`]}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div className="flex items-end">
                                        {data.items.length > 1 && (
                                            <button
                                                type="button"
                                                className="pb-2 text-sm text-rose-600"
                                                onClick={() =>
                                                    setData(
                                                        'items',
                                                        data.items.filter(
                                                            (_, i) => i !== itemIndex,
                                                        ),
                                                    )
                                                }
                                            >
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <button
                            type="button"
                            className="pressable text-sm font-semibold text-brand hover:text-brand-deep"
                            onClick={addItem}
                        >
                            + Tambah item
                        </button>
                        <p className="mt-1.5 text-xs text-ink-muted">
                            Pintasan: tekan{' '}
                            <kbd className="rounded border border-line bg-surface px-1.5 py-0.5 font-semibold text-ink-soft">
                                Caps Lock
                            </kbd>{' '}
                            atau{' '}
                            <kbd className="rounded border border-line bg-surface px-1.5 py-0.5 font-semibold text-ink-soft">
                                Enter
                            </kbd>{' '}
                            di kolom qty untuk menambah baris item.
                        </p>
                        <InputError message={errors.items} className="mt-1" />
                    </div>

                    {/* Section 2: Delivery Schedule Matrix */}
                    <div className="ui-panel p-5 sm:p-6">
                        <div className="mb-1 flex flex-wrap items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <span className="inline-flex h-6 w-6 items-center justify-center rounded-md bg-brand text-xs font-bold text-white shadow-sm">
                                    2
                                </span>
                                <h3 className="font-display font-semibold text-ink">Jadwal Pengiriman</h3>
                            </div>
                            {dueMonth && (
                                <span className="rounded-md bg-brand-muted px-3 py-1 text-sm font-medium text-brand-deep">
                                    {monthTitle(dueMonth)} · tgl 1–{dueMonth.days}
                                </span>
                            )}
                        </div>
                        <p className="mb-5 text-sm text-ink-muted">
                            Isi qty (kg, bilangan bulat) di cell tanggal yang diinginkan. Baris = part order, kolom =
                            tanggal dalam bulan due date. Kondisi ideal: sisa qty = 0.
                        </p>

                        {hasMismatch && scheduleItems.length > 0 && dueMonth && (
                            <div className="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                <p className="font-medium">
                                    Qty jadwal belum cocok dengan qty order.
                                </p>
                                <ul className="mt-1 list-disc space-y-0.5 pl-5">
                                    {mismatchItems.map(({ item, index }) => {
                                        const left = remainingQty(item);
                                        return (
                                            <li key={index}>
                                                {itemLabel(qadItems, item.item_number)} — sisa{' '}
                                                <span className="font-semibold">
                                                    {formatQty(left)} kg
                                                </span>
                                                {left > 0
                                                    ? ' belum dialokasikan'
                                                    : ' kelebihan alokasi'}
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        )}

                        {!dueMonth ? (
                            <div className="rounded-lg border border-dashed border-line bg-canvas-soft px-4 py-8 text-center text-sm text-ink-muted">
                                Pilih due date di header PO terlebih dahulu. Bulan due date menentukan
                                kolom tanggal (1–31).
                            </div>
                        ) : scheduleItems.length === 0 ? (
                            <div className="rounded-lg border border-dashed border-line bg-canvas-soft px-4 py-8 text-center text-sm text-ink-muted">
                                Isi item dan qty di section 1 terlebih dahulu untuk mulai mengisi
                                jadwal pengiriman.
                            </div>
                        ) : (
                            <div className="-mx-2 overflow-x-auto rounded-lg border border-line">
                                <table className="min-w-full border-collapse text-sm">
                                    <thead>
                                        <tr className="bg-canvas text-ink-soft">
                                            <th className="sticky left-0 z-20 w-[200px] min-w-[200px] max-w-[200px] border-b border-r border-line bg-canvas px-3 py-2 text-left font-semibold shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">
                                                Part
                                            </th>
                                            <th className="sticky left-[200px] z-20 w-[190px] min-w-[190px] max-w-[190px] border-b border-r border-line bg-canvas px-2 py-2 text-left font-semibold shadow-[2px_0_4px_-2px_rgba(0,0,0,0.08)]">
                                                Tujuan OHP
                                            </th>
                                            <th className="min-w-[88px] border-b border-r border-line px-2 py-2 text-right font-semibold">
                                                Order
                                            </th>
                                            <th className="min-w-[88px] border-b border-r border-line px-2 py-2 text-right font-semibold">
                                                Terjadwal
                                            </th>
                                            <th className="min-w-[72px] border-b border-r border-line px-2 py-2 text-right font-semibold">
                                                Sisa
                                            </th>
                                            {dayColumns.map((day) => {
                                                const sabMin = weekendShortLabel(dueMonth, day);

                                                return (
                                                <th
                                                    key={day}
                                                    title={weekdayLabel(dueMonth, day)}
                                                    className={`min-w-[52px] border-b border-line px-1 py-2 text-center font-semibold tabular-nums ${weekendHeaderClass(dueMonth, day)}`}
                                                >
                                                    {day}
                                                    {sabMin && (
                                                        <div className="text-[9px] font-semibold leading-none tracking-wide">
                                                            {sabMin}
                                                        </div>
                                                    )}
                                                </th>
                                                );
                                            })}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {scheduleItems.map(({ item, index: itemIndex }, rowIdx) => {
                                            const ordered = toNum(item.qty_ordered);
                                            const scheduled = scheduledTotal(item);
                                            const remaining = remainingQty(item);
                                            const matched = remaining === 0;
                                            const ohpValue = rowOhp(item, itemDefaults);
                                            const zebra = rowIdx % 2 === 0 ? 'bg-surface' : 'bg-canvas-soft/80';

                                            return (
                                                <tr key={itemIndex} className={zebra}>
                                                    <td
                                                        className={`sticky left-0 z-10 w-[200px] min-w-[200px] max-w-[200px] border-b border-r border-line px-3 py-2 align-middle shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)] ${zebra}`}
                                                    >
                                                        <div
                                                            className="font-medium text-ink"
                                                            title={itemLabel(qadItems, item.item_number)}
                                                        >
                                                            {itemShortLabel(qadItems, item.item_number)}
                                                        </div>
                                                        <div className="mt-0.5 max-w-[176px] truncate text-xs text-ink-muted">
                                                            {qadItems.find(
                                                                (i) => i.qad_code === item.item_number,
                                                            )?.description || ''}
                                                        </div>
                                                        <InputError
                                                            message={
                                                                errors[`items.${itemIndex}.schedules`]
                                                            }
                                                            className="mt-1"
                                                        />
                                                    </td>
                                                    <td
                                                        className={`sticky left-[200px] z-10 w-[190px] min-w-[190px] max-w-[190px] border-b border-r border-line px-2 py-1.5 align-middle shadow-[2px_0_4px_-2px_rgba(0,0,0,0.06)] ${zebra}`}
                                                    >
                                                        <select
                                                            className="w-full rounded-md border-line py-1.5 text-xs"
                                                            value={ohpValue}
                                                            onChange={(e) =>
                                                                setRowOhp(itemIndex, e.target.value)
                                                            }
                                                        >
                                                            <option value="">Pilih OHP</option>
                                                            {qadSuppliersOhp.map((s) => (
                                                                <option
                                                                    key={s.qad_code}
                                                                    value={s.qad_code}
                                                                >
                                                                    {s.qad_code} — {s.name}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </td>
                                                    <td className="border-b border-r border-line px-2 py-2 text-right tabular-nums text-ink-soft">
                                                        {formatQty(ordered)}
                                                    </td>
                                                    <td className="border-b border-r border-line px-2 py-2 text-right tabular-nums text-ink-soft">
                                                        {formatQty(scheduled)}
                                                    </td>
                                                    <td
                                                        className={`border-b border-r border-line px-2 py-2 text-right font-semibold tabular-nums ${
                                                            matched
                                                                ? 'text-emerald-700'
                                                                : remaining > 0
                                                                  ? 'text-amber-700'
                                                                  : 'text-rose-700'
                                                        }`}
                                                    >
                                                        {formatQty(remaining)}
                                                    </td>
                                                    {dayColumns.map((day) => {
                                                        const cellQty = qtyOnDay(
                                                            item,
                                                            dueMonth,
                                                            day,
                                                        );
                                                        const filled = toNum(cellQty) > 0;
                                                        const weekend = isWeekendDay(dueMonth, day);

                                                        return (
                                                            <td
                                                                key={day}
                                                                className={`border-b border-line p-0.5 align-middle ${weekendCellClass(dueMonth, day)}`}
                                                                title={weekdayLabel(dueMonth, day)}
                                                            >
                                                                <input
                                                                    type="text"
                                                                    inputMode="numeric"
                                                                    autoComplete="off"
                                                                    aria-label={`Qty ${itemShortLabel(qadItems, item.item_number)} tanggal ${day}`}
                                                                    className={`no-spin w-full rounded border px-1 py-1.5 text-center text-xs tabular-nums focus:border-brand focus:outline-none focus:ring-1 focus:ring-brand ${
                                                                        filled
                                                                            ? weekend
                                                                                ? 'border-rose-300 bg-rose-100 font-medium text-rose-900'
                                                                                : 'border-brand-line bg-brand-muted font-medium text-brand-deep'
                                                                            : weekend
                                                                              ? 'border-transparent bg-rose-50 text-ink hover:border-rose-200 hover:bg-rose-100'
                                                                              : 'border-transparent bg-transparent text-ink hover:border-line hover:bg-surface'
                                                                    }`}
                                                                    value={
                                                                        cellQty === null ||
                                                                        cellQty === undefined
                                                                            ? ''
                                                                            : String(cellQty)
                                                                    }
                                                                    onChange={(e) => {
                                                                        if (isQtyInput(e.target.value)) {
                                                                            setDayQty(
                                                                                itemIndex,
                                                                                day,
                                                                                e.target.value,
                                                                            );
                                                                        }
                                                                    }}
                                                                    onFocus={(e) => e.target.select()}
                                                                />
                                                            </td>
                                                        );
                                                    })}
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {dueMonth && scheduleItems.length > 0 && (
                            <p className="mt-3 text-xs text-ink-muted">
                                Tip: klik cell lalu ketik qty. Cell terisi akan berwarna hijau muda.
                                Kolom Sabtu & Minggu berwarna merah sesuai bulan due date. Geser
                                horizontal untuk melihat tanggal lain. Kolom Part & OHP tetap
                                terlihat saat scroll.
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href={route('purchase-orders.index')}
                            className="rounded-md border border-line px-4 py-2 text-sm"
                        >
                            Batal
                        </Link>
                        <PrimaryButton
                            disabled={processing}
                            className=""
                        >
                            {editing ? 'Simpan Perubahan' : 'Simpan Draft'}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
