const NOTIFICATION_BUTTON_ID = 'notification_button';
const NOTIFICATION_MESSAGE_ID = 'notification_message';
const USES_SECURE_PROTOCOL = location.protocol === 'https:';
const SECURE_URL = 'https://' + location.host + location.pathname;

$(function () {
    updateNotificationIndicator();
})

function updateNotificationIndicator() {
    const message = $('#' + NOTIFICATION_MESSAGE_ID);
    const button = $('#' + NOTIFICATION_BUTTON_ID);
    if (!USES_SECURE_PROTOCOL) {
        message.html(LANG['unavailable_insecure']);
        button.html(LANG['switch_to_encrypted']);
        button.click(function (event) { event.preventDefault(); window.location.replace(SECURE_URL); });
        button.show();
    } else if (!('Notification' in window)) {
        message.html(LANG['unsupported_by_browser']);
        button.hide();
    } else if (Notification.permission === 'denied') {
        message.html(LANG['blocked']);
        button.hide();
    } else if (Notification.permission === 'granted') {
        message.html(LANG['allowed']);
        button.hide();
    } else if (Notification.permission === 'default') {
        message.html(LANG['not_allowed']);
        button.html(LANG['grant_permission']);
        button.click(function (event) { event.preventDefault(); askNotificationPermission(); });
        button.show();
    }
}

function askNotificationPermission() {
    function handlePermission(permission) {
        updateNotificationIndicator();
    }

    function checkNotificationPromise() {
        try {
            Notification.requestPermission().then();
        } catch (e) {
            return false;
        }
        return true;
    }

    if (checkNotificationPromise()) {
        Notification.requestPermission()
            .then((permission) => {
                handlePermission(permission);
            })
    } else {
        Notification.requestPermission(function (permission) {
            handlePermission(permission);
        });
    }
}
