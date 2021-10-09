const MODE_CONTENT_LISTEN_ID = 'mode_content_listen';
const ENABLE_NOTIFICATIONS_BUTTON_ID = 'listen_enable_notifications_button';
const NOTIFICATIONS_MSG_ID = "listen_notifications_msg";
const REDIRECT_SECURE_LINK_ID = "listen_redirect_secure_link";
const LISTEN_INACTIVE_ICON_ID = "listen_inactive_icon";
const LISTEN_ACTIVE_ICON_ID = "listen_active_icon";

$(function () {
    $('#' + ENABLE_NOTIFICATIONS_BUTTON_ID).click(askNotificationPermission);
    if (INITIAL_MODE == LISTEN_MODE) {
        initializeListenMode();
    }
});

function initializeListenMode() {
    if (!USES_SECURE_PROTOCOL) {
        handleNotificationsUnsupportedInsecure();
    } else if (!('Notification' in window)) {
        handleNotificationsUnsupported();
    } else if (notificationsNotAllowed()) {
        handleNotificationsNotAllowed();
    } else {
        handleNotificationsAllowed();
    }
}

function notificationsNotAllowed() {
    return Notification.permission === 'denied' || Notification.permission === 'default';
}

function askNotificationPermission() {
    function handlePermission(permission) {
        if (permission === 'denied' || permission === 'default') {
            handleNotificationsNotAllowed();
        } else {
            handleNotificationsAllowed();
        }
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

function handleNotificationsUnsupportedInsecure() {
    $('#' + LISTEN_INACTIVE_ICON_ID).show();
    $('#' + NOTIFICATIONS_MSG_ID).html('Varsler støttes ikke på ukrypterte nettsider').show();
    $('#' + REDIRECT_SECURE_LINK_ID).show();
}

function handleNotificationsUnsupported() {
    $('#' + LISTEN_INACTIVE_ICON_ID).show();
    $('#' + NOTIFICATIONS_MSG_ID).html('Nettleseren støtter ikke varsler').show();
}

function handleNotificationsNotAllowed() {
    $('#' + LISTEN_INACTIVE_ICON_ID).show();
    $('#' + NOTIFICATIONS_MSG_ID).html('Tillatelse kreves for å bruke varsler').show();
    $('#' + ENABLE_NOTIFICATIONS_BUTTON_ID).show();
}

function handleNotificationsAllowed() {
    $('#' + LISTEN_INACTIVE_ICON_ID).hide();
    $('#' + NOTIFICATIONS_MSG_ID).hide();
    $('#' + ENABLE_NOTIFICATIONS_BUTTON_ID).hide();
    $('#' + LISTEN_ACTIVE_ICON_ID).show();
    $('#' + NOTIFICATIONS_MSG_ID).html('Enheten lytter etter aktivitet').show();
}
