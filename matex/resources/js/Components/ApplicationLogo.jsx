export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
            aria-hidden="true"
        >
            <rect width="48" height="48" rx="12" className="fill-brand" />
            <path
                d="M12 34V14h4.6l7.4 14.2L31.4 14H36v20h-3.8V21.2L25.4 34h-2.8L15.8 21.2V34H12Z"
                className="fill-white"
            />
            <path d="M12 36.5h24" stroke="currentColor" strokeWidth="1.5" className="text-brand-bright/80" />
        </svg>
    );
}
