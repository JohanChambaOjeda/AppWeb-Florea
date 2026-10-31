/*
 * Service worker de Florea.
 *
 * Hace solo dos cosas, a propósito:
 *
 *  1. Guarda el cascarón (íconos, manifiesto y la página "Sin conexión") para
 *     que la app abra aunque no haya señal.
 *  2. Guarda los assets compilados de /build/, que llevan hash en el nombre y
 *     por tanto nunca cambian de contenido.
 *
 * Lo que NUNCA guarda: las respuestas de Inertia ni ningún dato de la app.
 * Los pedidos, las ofertas y los precios tienen que verse al día siempre; una
 * publicación vencida mostrada como abierta haría perder una venta. Por eso
 * toda navegación va primero a la red y solo cae en la página de "Sin
 * conexión" si la red falla.
 *
 * Al cambiar SHELL hay que subir la versión de CACHE para que se renueve.
 */
const VERSION = 'florea-v1';
const SHELL = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(VERSION)
            .then((cache) => cache.addAll(SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => key !== VERSION)
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Solo GET y solo lo nuestro. Nada de POST, ni de dominios ajenos.
    if (
        request.method !== 'GET' ||
        new URL(request.url).origin !== self.location.origin
    ) {
        return;
    }

    // Las peticiones de Inertia traen datos frescos: que pasen de largo.
    if (request.headers.get('X-Inertia')) {
        return;
    }

    // Navegación: primero la red; si no hay, la página "Sin conexión".
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html')),
        );

        return;
    }

    // Assets con hash: del caché si están, y si no, a la red y se guardan.
    if (request.url.includes('/build/') || request.url.includes('/icons/')) {
        event.respondWith(
            caches.match(request).then(
                (hit) =>
                    hit ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();

                            caches
                                .open(VERSION)
                                .then((cache) => cache.put(request, copy));
                        }

                        return response;
                    }),
            ),
        );
    }

    // Todo lo demás (incluidas las fotos de las ofertas) va directo a la red.
});

/*
 * Avisos push.
 *
 * El payload lo arma WebPushNotification en el servidor: title, body, icon,
 * badge, vibrate, tag y data.url.
 */
self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload = {};

    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Florea', body: event.data.text() };
    }

    event.waitUntil(
        self.registration.showNotification(payload.title || 'Florea', {
            body: payload.body || '',
            icon: payload.icon || '/icons/icon-192.png',
            badge: payload.badge || '/icons/icon-192.png',
            vibrate: payload.vibrate || [200, 100, 200],
            // Con la misma etiqueta, un aviso nuevo reemplaza al anterior en
            // vez de apilarse: nadie quiere seis avisos del mismo pedido.
            tag: payload.tag || undefined,
            data: payload.data || {},
            lang: 'es',
        }),
    );
});

/*
 * Al tocar el aviso: si la app ya está abierta se reutiliza esa pestaña y se
 * navega; si no, se abre una. Así no se acumulan pestañas de Florea.
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/dashboard';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clients) => {
                for (const client of clients) {
                    if ('focus' in client) {
                        client.navigate(url);

                        return client.focus();
                    }
                }

                return self.clients.openWindow(url);
            }),
    );
});
