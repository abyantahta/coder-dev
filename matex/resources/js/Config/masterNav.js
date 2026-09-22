/**
 * Konfigurasi menu Master Data — tambah/ubah item di sini saja
 * agar nav utama & tab admin tetap sinkron.
 *
 * Diakses Purchasing (Mbak Dita) & Admin. Users kini juga bisa dikelola
 * Purchasing (kecuali akun Admin — lihat UserController).
 */
export const MASTER_DATA_ROLES = ['admin', 'purchasing'];

export const MASTER_NAV_ITEMS = [
    {
        key: 'overview',
        route: 'admin.dashboard',
        label: 'Overview',
        description: 'Ringkasan master data',
        match: 'admin.dashboard',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'products',
        route: 'admin.items.index',
        label: 'Raw Material',
        description: 'Item RM & harga /kg',
        match: 'admin.items.*',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'qad-items',
        route: 'admin.qad-items.index',
        label: 'Item Master QAD',
        description: 'Cache item master hasil sync dari QAD',
        match: 'admin.qad-items.*',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'suppliers',
        route: 'admin.qad-suppliers.index',
        label: 'Suppliers',
        description: 'Master supplier RM & OHP, tersinkron dari QAD',
        match: 'admin.qad-suppliers.*',
        params: {},
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'supplier-rm',
        route: 'admin.qad-suppliers.index',
        label: 'Supplier RM',
        description: 'Supplier raw material',
        match: 'admin.qad-suppliers.*',
        params: { category: 'raw_mat' },
        group: 'suppliers',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'supplier-ohp',
        route: 'admin.qad-suppliers.index',
        label: 'Supplier OHP',
        description: 'Supplier OH Part',
        match: 'admin.qad-suppliers.*',
        params: { category: 'ohp' },
        group: 'suppliers',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'discipline',
        route: 'admin.discipline.index',
        label: 'Kedisiplinan RM',
        description: 'Raport keterlambatan vs plan',
        match: 'admin.discipline.*',
        roles: ['admin', 'purchasing'],
    },
    {
        key: 'users',
        route: 'admin.users.index',
        label: 'Users',
        description: 'Akun & role portal',
        match: 'admin.users.*',
        roles: ['admin', 'purchasing'],
    },
];

export function masterNavForRole(role) {
    return MASTER_NAV_ITEMS.filter((item) => !item.roles || item.roles.includes(role));
}

/** Item utama di tab admin (tanpa filter turunan) */
export function masterTabItemsForRole(role) {
    return masterNavForRole(role).filter((item) => !item.group);
}

/** @deprecated gunakan masterTabItemsForRole(role) */
export const MASTER_TAB_ITEMS = MASTER_NAV_ITEMS.filter((item) => !item.group);

export function canAccessMasterData(role) {
    return MASTER_DATA_ROLES.includes(role);
}

export function masterNavHref(item) {
    const params = item.params || {};
    return route(item.route, params);
}

export function isMasterNavActive(item) {
    if (!route().current(item.match)) {
        return false;
    }

    if (typeof window === 'undefined') {
        return !item.params?.category;
    }

    const category = new URL(window.location.href).searchParams.get('category');

    if (item.params?.category) {
        return category === item.params.category;
    }

    if (item.key === 'suppliers') {
        return !category;
    }

    return true;
}

export function isMasterSectionActive() {
    return route().current('admin.*');
}
