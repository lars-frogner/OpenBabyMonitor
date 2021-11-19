const NOTIFICATION_SOUND = new Audio('media/notification_sound.mp3');

const USES_SECURE_PROTOCOL = location.protocol === 'https:';
const SECURE_URL = 'https://' + location.host + location.pathname;

var _REDIRECT_MODAL_TRIGGER = {};
var _UNSUPPORTED_MODAL_TRIGGER = {};
var _PERMISSION_MODAL_TRIGGER = {};

$(function () {
    if (SIGNED_IN) {
        performBrowserNotificationRequest();
    }
});

function setupBrowserNotificationRedirectModal() {
    connectModalToObject(_REDIRECT_MODAL_TRIGGER, { checkboxLabel: LANG['dont_ask_again'], checkboxChecked: false, confirmOnclick: function () { redirectModalCallback(function () { window.location.replace(SECURE_URL); }); }, dismissOnclick: redirectModalCallback, header: LANG['not_supported_unencrypted'], confirm: LANG['switch_site'], dismiss: LANG['stay'] }, { text: LANG['want_to_switch_site'], showText: () => { return true; } });
}

function setupBrowserNotificationUnsupportedModal() {
    connectModalToObject(_UNSUPPORTED_MODAL_TRIGGER, { checkboxLabel: LANG['dont_show_again'], checkboxChecked: false, dismissOnclick: unsupportedModalCallback, header: LANG['not_supported_by_browser'], dismiss: LANG['ok'] });
}

function setupBrowserNotificationPermissionModal() {
    connectModalToObject(_PERMISSION_MODAL_TRIGGER, { checkboxLabel: LANG['dont_ask_again'], checkboxChecked: false, confirmOnclick: function () { askBrowserNotificationPermission(); hideModalWithoutDismissCallback(); }, dismissOnclick: permissionModalCallback, header: LANG['premission_required'], confirm: LANG['grant_permission'], dismiss: LANG['continue_without'] });
}

function redirectModalCallback(onCompletion) {
    const askAgain = !$('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked');
    if (askAgain != SETTING_ASK_SECURE_REDIRECT) {
        SETTING_ASK_SECURE_REDIRECT = askAgain;
        updateSettings('system', { ask_secure_redirect: askAgain }).then(function () {
            if (onCompletion) {
                onCompletion();
            }
        });
    } else {
        if (onCompletion) {
            onCompletion();
        }
    }
}

function unsupportedModalCallback() {
    const showAgain = !$('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked');
    if (showAgain != SETTING_SHOW_UNSUPPORTED_MESSAGE) {
        SETTING_SHOW_UNSUPPORTED_MESSAGE = showAgain;
        updateSettings('system', { show_unsupported_message: showAgain });
    }
}

function permissionModalCallback() {
    const askAgain = !$('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked');
    if (askAgain != SETTING_ASK_NOTIFICATION_PERMISSION) {
        SETTING_ASK_NOTIFICATION_PERMISSION = askAgain;
        updateSettings('system', { ask_notification_permission: askAgain });
    }
}

function performBrowserNotificationRequest() {
    if (!USES_SECURE_PROTOCOL) {
        if (SETTING_ASK_SECURE_REDIRECT) {
            setupBrowserNotificationRedirectModal();
            _REDIRECT_MODAL_TRIGGER.triggerModal();
        }
    } else if (!browserNotificationsSupported()) {
        if (SETTING_SHOW_UNSUPPORTED_MESSAGE) {
            setupBrowserNotificationUnsupportedModal();
            _UNSUPPORTED_MODAL_TRIGGER.triggerModal();
        }
    } else if (browserNotificationsNotAllowed()) {
        if (SETTING_ASK_NOTIFICATION_PERMISSION) {
            setupBrowserNotificationPermissionModal();
            _PERMISSION_MODAL_TRIGGER.triggerModal();
        }
    }
}

function browserNotificationsSupported() {
    return 'Notification' in window;
}

function browserNotificationsAllowed() {
    return Notification.permission === 'granted';
}

function browserNotificationsNotAllowed() {
    return Notification.permission === 'default';
}

function browserNotificationsBlocked() {
    return Notification.permission === 'denied';
}

function askBrowserNotificationPermission(callback) {
    function handlePermission(permission) {
        if (callback) {
            callback(permission);
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

function playNotificationSound() {
    NOTIFICATION_SOUND.play();
}

function createBrowserNotification(header, body, tag) {
    return new Notification(header, { body: body, tag: tag, renotify: true, requireInteraction: true });
}
