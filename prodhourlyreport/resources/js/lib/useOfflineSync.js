import { useCallback, useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { flushQueue, getPendingEntries } from './offlineQueue';

const SYNC_RETRY_INTERVAL_MS = 30000;

/**
 * Tracks connectivity + the offline entry queue, and drains the queue
 * whenever the browser comes back online (plus a periodic retry, since
 * `navigator.onLine` can lie on flaky connections).
 */
export function useOfflineSync({ onSynced } = {}) {
    const userId = usePage().props.auth.user.id;
    const [isOnline, setIsOnline] = useState(() => (typeof navigator === 'undefined' ? true : navigator.onLine));
    const [pendingEntries, setPendingEntries] = useState([]);
    const [syncing, setSyncing] = useState(false);
    const syncingRef = useRef(false);

    const refreshPending = useCallback(() => {
        getPendingEntries()
            .then((entries) => setPendingEntries(entries.filter((e) => !e.owner_user_id || e.owner_user_id === userId)))
            .catch(() => {});
    }, [userId]);

    const sync = useCallback(async () => {
        if (!navigator.onLine || syncingRef.current) return;
        syncingRef.current = true;
        setSyncing(true);
        try {
            const result = await flushQueue(userId);
            refreshPending();
            if (result.synced > 0) {
                onSynced?.(result);
            }
        } finally {
            syncingRef.current = false;
            setSyncing(false);
        }
    }, [onSynced, refreshPending, userId]);

    useEffect(() => {
        refreshPending();
        if (navigator.onLine) sync();

        const handleOnline = () => {
            setIsOnline(true);
            sync();
        };
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
        const interval = setInterval(() => {
            if (navigator.onLine) sync();
        }, SYNC_RETRY_INTERVAL_MS);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
            clearInterval(interval);
        };
    }, [sync, refreshPending]);

    return { isOnline, pendingCount: pendingEntries.length, pendingEntries, syncing, refreshPending, sync };
}
