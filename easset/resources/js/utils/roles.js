export const ADMIN_ROLE_ID = 2;

export function isAdmin(auth) {
    return auth?.user?.role_id === ADMIN_ROLE_ID;
}
