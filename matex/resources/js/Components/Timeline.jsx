export default function Timeline({ logs = [] }) {
    if (!logs.length) {
        return <p className="text-sm text-ink-muted">Belum ada aktivitas tercatat.</p>;
    }

    return (
        <ol className="relative ms-3 space-y-4 border-s border-line">
            {logs.map((log, index) => (
                <li
                    key={log.id}
                    className="animate-fade-up ms-6"
                    style={{ animationDelay: `${Math.min(index, 6) * 40}ms` }}
                >
                    <span className="absolute -start-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-surface bg-brand shadow-sm" />
                    <div className="rounded-panel border border-line bg-surface p-3 transition duration-220 ease-matex hover:border-brand-line hover:shadow-panel">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="font-display text-sm font-semibold text-ink">
                                {log.action}
                            </p>
                            <time className="text-xs text-ink-muted">
                                {new Date(log.created_at).toLocaleString('id-ID')}
                            </time>
                        </div>
                        <p className="mt-1 text-xs text-ink-muted">
                            {log.from_status || '—'} → {log.to_status}
                            {log.user ? ` · ${log.user.name}` : ''}
                        </p>
                        {log.notes && (
                            <p className="mt-2 text-sm text-ink-soft">{log.notes}</p>
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}
