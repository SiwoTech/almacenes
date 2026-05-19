const CACHE_NAME = 'almacenes-pwa-v1';
const STATIC_FILES = [
    '/almacenes/pwa/index.html',
    '/almacenes/pwa/style.css',
    '/almacenes/pwa/app.js',
    '/almacenes/assets/css/almacenes.css',
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_FILES)));
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))))
    );
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    if (url.pathname.startsWith('/almacenes/api/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => response)
                .catch(() => caches.match(event.request))
        );
        return;
    }

    if (url.pathname.startsWith('/almacenes/')) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                    return response;
                });
            })
        );
    }
});

self.addEventListener('sync', event => {
    if (event.tag === 'cwo-sync-conteos') {
        event.waitUntil(
            self.clients.matchAll({ includeUncontrolled: true }).then(clients => {
                clients.forEach(client => client.postMessage({ type: 'sync-conteos' }));
            })
        );
    }
});
