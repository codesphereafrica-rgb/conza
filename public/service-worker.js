const CACHE_NAME = 'conza-pwa-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch (error) {
        data = { body: event.data ? event.data.text() : '' };
    }

    const title = data.title || 'Conza Program';
    const options = {
        body: data.body || data.message || 'Vous avez une nouvelle notification.',
        icon: data.icon || '/icon-192.png',
        badge: data.badge || '/icon-192.png',
        data: { url: data.url || data.link || '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';

    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
        for (const client of clientList) {
            if ('focus' in client) {
                client.navigate(url);
                return client.focus();
            }
        }

        return clients.openWindow(url);
    }));
});
