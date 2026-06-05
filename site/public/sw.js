// Baby Monitor Service Worker — handles background push notifications
const CACHE_NAME = 'babymonitor-v1';

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(clients.claim());
});

self.addEventListener('push', function(event) {
    let data = { title: 'Baby Monitor', body: 'Activity detected', type: 'sound' };
    try {
        data = Object.assign(data, event.data.json());
    } catch (e) {
        if (event.data) data.body = event.data.text();
    }

    const options = {
        body: data.body,
        icon: 'media/babymonitor.jpg',
        badge: 'media/babymonitor.jpg',
        tag: 'babymonitor-alert',
        renotify: true,
        requireInteraction: true,
        vibrate: [200, 100, 200, 100, 200],
        data: { url: self.registration.scope + 'main.php', type: data.type }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : self.registration.scope + 'main.php';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            for (const client of clientList) {
                if (client.url.includes('main.php') && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});
