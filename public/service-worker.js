const CACHE_NAME = 'credinor-v2';
const ASSETS_TO_CACHE = [
    './assets/css/app.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js'
];

const APP_ROUTES = [
    './consulta',
    './consulta/buscar',
    './offline'
];

self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache =>
            cache.addAll(ASSETS_TO_CACHE).catch(() => {})
        )
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // API: red primero, error JSON si sin conexión
    if (url.pathname.includes('/api/')) {
        event.respondWith(
            fetch(event.request).catch(() =>
                new Response(JSON.stringify({ ok: false, message: 'Estás sin conexión.' }), {
                    headers: { 'Content-Type': 'application/json' }
                })
            )
        );
        return;
    }

    // Assets estáticos: cache primero, luego red
    if (
        event.request.destination === 'style' ||
        event.request.destination === 'script' ||
        event.request.destination === 'font' ||
        event.request.destination === 'image'
    ) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                const network = fetch(event.request).then(res => {
                    caches.open(CACHE_NAME).then(c => c.put(event.request, res.clone()));
                    return res;
                });
                return cached || network;
            })
        );
        return;
    }

    // Navegación HTML: red primero; si falla usar cache; si no hay cache → /offline
    event.respondWith(
        fetch(event.request)
            .then(res => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then(c => c.put(event.request, clone));
                return res;
            })
            .catch(() =>
                caches.match(event.request).then(cached =>
                    cached || caches.match('./offline')
                )
            )
    );
});
