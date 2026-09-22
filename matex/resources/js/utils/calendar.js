/**
 * @param {{ year: number, month: number }} dueMonth  month is 1–12
 * @param {number} day
 */
export function weekdayIndex(dueMonth, day) {
    return new Date(dueMonth.year, dueMonth.month - 1, day).getDay();
}

export function isWeekendDay(dueMonth, day) {
    const weekday = weekdayIndex(dueMonth, day);
    return weekday === 0 || weekday === 6;
}

export function weekdayLabel(dueMonth, day) {
    return new Date(dueMonth.year, dueMonth.month - 1, day).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

export function weekendShortLabel(dueMonth, day) {
    const weekday = weekdayIndex(dueMonth, day);
    if (weekday === 6) {
        return 'Sab';
    }
    if (weekday === 0) {
        return 'Min';
    }
    return null;
}

export function weekendHeaderClass(dueMonth, day) {
    return isWeekendDay(dueMonth, day) ? 'bg-rose-100 text-rose-800' : '';
}

export function weekendCellClass(dueMonth, day) {
    return isWeekendDay(dueMonth, day) ? 'bg-rose-50' : '';
}
