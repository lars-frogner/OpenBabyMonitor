// Web Push subscription management

var _PUSH_SUBSCRIPTION = null;
var _SW_REGISTRATION   = null;

function pushNotificationsAvailable() {
    return 'serviceWorker' in navigator
        && 'PushManager' in window
        && location.protocol === 'https:';
}

function pushRequiresInternetHint() {
    return typeof ACCESS_POINT_ACTIVE !== 'undefined' && ACCESS_POINT_ACTIVE;
}

function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return Promise.reject('SW not supported');
    return navigator.serviceWorker.register('sw.js')
        .then(function(reg) {
            _SW_REGISTRATION = reg;
            return reg;
        });
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

function subscribeToPush(vapidPublicKey) {
    if (!_SW_REGISTRATION) return Promise.reject('No SW registration');
    return _SW_REGISTRATION.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
    }).then(function(subscription) {
        _PUSH_SUBSCRIPTION = subscription;
        return savePushSubscription(subscription);
    });
}

function savePushSubscription(subscription) {
    const key  = subscription.getKey ? subscription.getKey('p256dh') : null;
    const auth = subscription.getKey ? subscription.getKey('auth')   : null;
    const body = JSON.stringify({
        endpoint: subscription.endpoint,
        p256dh:   key  ? btoa(String.fromCharCode(...new Uint8Array(key)))  : '',
        auth:     auth ? btoa(String.fromCharCode(...new Uint8Array(auth))) : ''
    });
    return fetch('push_subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: body
    });
}

function unsubscribeFromPush() {
    if (!_SW_REGISTRATION) return Promise.resolve();
    return _SW_REGISTRATION.pushManager.getSubscription()
        .then(function(subscription) {
            if (!subscription) return;
            return fetch('push_subscribe.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ endpoint: subscription.endpoint })
            }).then(function() {
                return subscription.unsubscribe();
            });
        })
        .then(function() { _PUSH_SUBSCRIPTION = null; });
}

function isPushSubscribed() {
    if (!_SW_REGISTRATION) return Promise.resolve(false);
    return _SW_REGISTRATION.pushManager.getSubscription()
        .then(function(sub) { return !!sub; });
}

// Initialize push support on page load — register SW silently
$(function() {
    if (!pushNotificationsAvailable()) return;
    registerServiceWorker().catch(function() {});
});
