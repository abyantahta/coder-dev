const base = {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    strokeWidth: 1.75,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
};

export function IconDashboard(props) {
    return (
        <svg {...base} {...props}>
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
        </svg>
    );
}

export function IconEntry(props) {
    return (
        <svg {...base} {...props}>
            <path d="M4 20h16" />
            <path d="M12 4v12" />
            <path d="M7 9l5-5 5 5" />
        </svg>
    );
}

export function IconLines(props) {
    return (
        <svg {...base} {...props}>
            <path d="M4 6h16" />
            <path d="M4 12h10" />
            <path d="M4 18h13" />
        </svg>
    );
}

export function IconCube(props) {
    return (
        <svg {...base} {...props}>
            <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" />
            <path d="M12 12l8-4.5" />
            <path d="M12 12v9" />
            <path d="M12 12L4 7.5" />
        </svg>
    );
}

export function IconTag(props) {
    return (
        <svg {...base} {...props}>
            <path d="M20.5 12.5l-8 8-9-9v-8h8l9 9z" />
            <circle cx="7.5" cy="7.5" r="1.1" fill="currentColor" stroke="none" />
        </svg>
    );
}

export function IconUsers(props) {
    return (
        <svg {...base} {...props}>
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 20c0-3.5 2.5-6 5.5-6s5.5 2.5 5.5 6" />
            <circle cx="17" cy="8.5" r="2.4" />
            <path d="M15.8 14.2c2.4.3 4.2 2.4 4.2 5.8" />
        </svg>
    );
}

export function IconLogout(props) {
    return (
        <svg {...base} {...props}>
            <path d="M9 4H5a1 1 0 00-1 1v14a1 1 0 001 1h4" />
            <path d="M16 16l4-4-4-4" />
            <path d="M20 12H9" />
        </svg>
    );
}

export function IconMenu(props) {
    return (
        <svg {...base} {...props}>
            <path d="M4 7h16" />
            <path d="M4 12h16" />
            <path d="M4 17h16" />
        </svg>
    );
}

export function IconClose(props) {
    return (
        <svg {...base} {...props}>
            <path d="M6 6l12 12" />
            <path d="M18 6L6 18" />
        </svg>
    );
}
