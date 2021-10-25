const NOTIFICATION_BUTTON_ID = 'notification_button';
const NOTIFICATION_MESSAGE_ID = 'notification_message';

$(function () {
    updateNotificationIndicator();
})

function updateNotificationIndicator() {
    const message = $('#' + NOTIFICATION_MESSAGE_ID);
    const button = $('#' + NOTIFICATION_BUTTON_ID);
    if (!USES_SECURE_PROTOCOL) {
        message.html('Utilgjengelig på ukryptert side');
        button.html('Gå til kryptert side');
        button.click(function (event) { event.preventDefault(); window.location.replace(SECURE_URL); });
        button.show();
    } else if (!('Notification' in window)) {
        message.html('Ikke støttet av nettleseren');
        button.hide();
    } else if (Notification.permission === 'denied') {
        message.html('Blokkert');
        button.hide();
    } else if (Notification.permission === 'granted') {
        message.html('Tillatt');
        button.hide();
    } else if (Notification.permission === 'default') {
        message.html('Ikke tillatt');
        button.html('Gi tillatelse');
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
