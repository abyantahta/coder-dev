// Minimal offline app-shell cache: network-first for everything cacheable,
// falling back to the last-known-good response when the network fails.
// Actual data mutations (production log submissions) are queued and synced
// by the page itself (see resources/js/lib/offlineQueue.js) — this worker
// only makes the shell (HTML/JS/CSS/icons) available offline.

const CACHE_VERSION = 'prodreport-v1';

const EXCLUDED_PATH_PREFIXES = ['/dashboard/export', '/build/hot'];

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key))),
        ).then(() => self.clients.claim()),
    );
});

function isCacheable(request, url) {
    if (request.method !== 'GET') return false;
    if (url.origin !== self.location.origin) return false;
    return !EXCLUDED_PATH_PREFIXES.some((prefix) => url.pathname.startsWith(prefix));
}

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (!isCacheable(event.request, url)) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE_VERSION).then((cache) => cache.put(event.request, copy));
                }
                return response;
            })
            .catch(async () => {
                const cached = await caches.match(event.request);
                if (cached) {
                    return cached;
                }
                if (event.request.mode === 'navigate') {
                    const shell = await caches.match('/dashboard');
                    if (shell) return shell;
                }
                throw new Error('offline and not cached');
            }),
    );
});
