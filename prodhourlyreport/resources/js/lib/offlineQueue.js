import { getCsrfToken } from './csrf';

const DB_NAME = 'prodreport-offline';
const DB_VERSION = 1;
const STORE_NAME = 'pending_entries';

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);
        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'client_uuid' });
            }
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function withStore(mode, callback) {
    return openDb().then(
        (db) =>
            new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, mode);
                const result = callback(tx.objectStore(STORE_NAME));
                tx.oncomplete = () => resolve(result?.result);
                tx.onerror = () => reject(tx.error);
            }),
    );
}

export function enqueueEntry(entry) {
    return withStore('readwrite', (store) => store.put(entry));
}

export function getPendingEntries() {
    return withStore('readonly', (store) => store.getAll());
}

export function removePendingEntry(clientUuid) {
    return withStore('readwrite', (store) => store.delete(clientUuid));
}

async function submitEntry(payload) {
    const response = await fetch('/entry', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const error = new Error(`Entry submission failed (${response.status})`);
        error.status = response.status;
        throw error;
    }

    return response.json();
}

/**
 * Attempts to sync every queued entry belonging to `currentUserId`. Network
 * failures leave an entry queued for the next attempt; a 422 (now-invalid
 * data, e.g. a product deactivated while offline) drops it instead of
 * retrying forever. Entries queued under a different user (a shared tablet
 * where someone else logged in before this queue synced) are left alone —
 * attributing them to whoever happens to be logged in at sync time would be
 * wrong, so they just wait for their own owner to be signed in again.
 */
export async function flushQueue(currentUserId) {
    const pending = await getPendingEntries();
    let synced = 0;

    for (const entry of pending) {
        if (entry.owner_user_id && currentUserId && entry.owner_user_id !== currentUserId) {
            continue;
        }

        const { owner_user_id, ...payload } = entry;
        try {
            await submitEntry(payload);
            await removePendingEntry(entry.client_uuid);
            synced += 1;
        } catch (error) {
            if (error.status === 422) {
                await removePendingEntry(entry.client_uuid);
            }
            // Network errors (no `status`) stay queued for the next attempt.
        }
    }

    return { synced, remaining: (await getPendingEntries()).length };
}
