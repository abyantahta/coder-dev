export function toNum(value) {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    const n = Number(String(value).replace(',', '.'));
    return Number.isFinite(n) ? Math.round(n) : 0;
}

export function roundQty(value) {
    return toNum(value);
}

export function formatQty(value) {
    return String(toNum(value));
}

export function isQtyInput(raw) {
    return raw === '' || /^\d+$/.test(String(raw));
}
