import { useOfflineSyncContext } from '@/lib/OfflineSyncContext';

export default function SyncStatusBadge({ dark = false }) {
    const { isOnline, pendingCount, syncing } = useOfflineSyncContext();

    if (isOnline && pendingCount === 0) {
        return (
            <span className={`flex items-center gap-1.5 text-xs ${dark ? 'text-brand-300' : 'text-gray-400'}`}>
                <span className="h-2 w-2 rounded-full bg-emerald-400" />
                Online
            </span>
        );
    }

    if (!isOnline) {
        return (
            <span
                className={`flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ${
                    dark ? 'bg-white/10 text-brand-100' : 'bg-gray-100 text-gray-600'
                }`}
            >
                <span className="h-2 w-2 rounded-full bg-gray-400" />
                Offline
                {pendingCount > 0 && <span className="opacity-70">&middot; {pendingCount} queued</span>}
            </span>
        );
    }

    return (
        <span
            className={`flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ${
                dark ? 'bg-accent-500/20 text-accent-100' : 'bg-accent-50 text-accent-700'
            }`}
        >
            <span className={`h-2 w-2 rounded-full bg-accent-500 ${syncing ? 'animate-pulse' : ''}`} />
            {syncing ? 'Syncing…' : `${pendingCount} pending sync`}
        </span>
    );
}
