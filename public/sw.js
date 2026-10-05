/*
 * Truk driver service worker.
 *
 * The driver portal must survive a cold start with no signal: navigations are
 * served network-first with the last cached copy as a fallback, and the built
 * assets are served cache-first. Writes are never intercepted here; the driver
 * portal queues them with an idempotency key and replays them when online.
 */
const CACHE = 'truk-driver-v1';
const APP_SHELL = ['/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));

                    return response;
                })
                .catch(() =>
                    caches.match(request).then((cached) => cached ?? caches.match('/')),
                ),
        );

        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (response.ok && (request.url.includes('/build/') || request.url.includes('/fonts'))) {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }

                return response;
            });
        }),
    );
});
