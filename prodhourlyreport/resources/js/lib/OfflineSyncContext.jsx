import { createContext, useCallback, useContext } from 'react';
import { router } from '@inertiajs/react';
import { useOfflineSync } from './useOfflineSync';

const OfflineSyncContext = createContext(null);

/**
 * Runs the offline queue's sync engine exactly once per authenticated
 * session. Both sidebar badges and the entry form previously each called
 * useOfflineSync() independently — since the desktop sidebar and mobile
 * header are both always mounted (visibility is CSS-only), that meant 2-3
 * concurrent 30s pollers/online-listeners, and concurrent flushQueue() calls
 * that could each submit the same queued entry before either removed it
 * locally. One provider, one poller, one flush per reconnect.
 */
export function OfflineSyncProvider({ children }) {
    const onSynced = useCallback(() => router.reload({ only: ['todayLogs', 'logs'] }), []);
    const sync = useOfflineSync({ onSynced });

    return <OfflineSyncContext.Provider value={sync}>{children}</OfflineSyncContext.Provider>;
}

export function useOfflineSyncContext() {
    const context = useContext(OfflineSyncContext);
    if (!context) {
        throw new Error('useOfflineSyncContext must be used within an OfflineSyncProvider');
    }
    return context;
}
