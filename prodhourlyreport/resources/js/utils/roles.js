const MASTER_DATA_ROLES = ['gm', 'it_super_user'];
const ALL_LINES_ROLES = ['unit_head', 'gm', 'it_super_user'];
const SUPER_USER_ROLES = ['it_super_user'];
const SUBMIT_ROLES = ['leader', 'unit_head', 'it_super_user'];

export function isSuperUser(role) {
    return SUPER_USER_ROLES.includes(role);
}

export function managesMasterData(role) {
    return isSuperUser(role) || MASTER_DATA_ROLES.includes(role);
}

export function accessesAllLines(role) {
    return isSuperUser(role) || ALL_LINES_ROLES.includes(role);
}

export function canSubmitProduction(role) {
    return SUBMIT_ROLES.includes(role);
}

const ROLE_LABELS = {
    leader: 'Leader',
    group_head: 'Group Head',
    unit_head: 'Unit Head',
    gm: 'General Manager',
    it_super_user: 'IT Super User',
};

export function roleLabel(role) {
    return ROLE_LABELS[role] ?? role;
}
