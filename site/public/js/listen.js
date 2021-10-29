const MODE_CONTENT_LISTEN_ID = 'mode_content_listen';
const NOTIFICATIONS_MSG_ID = "listen_notifications_msg";
const LISTEN_NONE_RADIO_ID = "listen_none_radio";
const LISTEN_LIVE_RADIO_ID = "listen_live_radio";
const LISTEN_INACTIVE_ICON_ID = "listen_inactive_icon";
const LISTEN_ACTIVE_ICON_ID = "listen_active_icon";
const LISTEN_CONTROL_VISUALIZATION_MODE_BOX_ID = 'listen_visualization_mode_box';
const LISTEN_ANIMATION_CONTAINER_ID = "listen_animation_container";
const LISTEN_ANIMATION_BACKGROUND_ID = "listen_animation_background";
const LISTEN_ANIMATION_INDICATOR_ID = "listen_animation_indicator";
const LISTEN_ANIMATION_LABEL_CLASS = "listen-animation-label";

const NOTIFICATION_HEADERS = { bad: 'Varsel om gråt', good: 'Varsel om babling', bad_and_good: 'Varsel om gråt og babling', bad_or_good: 'Varsel om gråt eller babling' };
const NOTIFICATION_TEXTS = { bad: 'Barnet gråter.', good: 'Barnet babler.', bad_and_good: 'Barnet gråter og babler om hverandre.', bad_or_good: 'Barnet lager lyder, men det er ikke tydelig om det er gråt eller babling.' };

const NOTIFICATION_SOUND = new Audio('media/notification_sound.mp3');

var _EVENT_SOURCE;
var _REDIRECT_MODAL_TRIGGER = {};
var _UNSUPPORTED_MODAL_TRIGGER = {};
var _PERMISSION_MODAL_TRIGGER = {};
var _NOTIFICATION_MODAL_TRIGGER = {};
var _BROWSER_NOTIFICATION;

$(function () {
    connectModalToObject(_REDIRECT_MODAL_TRIGGER, { checkboxLabel: 'Ikke spør igjen', checkboxChecked: !SETTING_ASK_SECURE_REDIRECT, confirmOnclick: function () { redirectModalCallback(function () { window.location.replace(SECURE_URL); }); }, dismissOnclick: redirectModalCallback, header: 'Nettleservarsler støttes ikke på ukrypterte nettsider', confirm: 'Fortsett til ny side', dismiss: 'Bli på denne siden' }, { text: 'Vil du gå til den krypterte (https) versjonen av siden?', showText: () => { return true; } });
    connectModalToObject(_UNSUPPORTED_MODAL_TRIGGER, { checkboxLabel: 'Ikke vis igjen', checkboxChecked: !SETTING_SHOW_UNSUPPORTED_MESSAGE, dismissOnclick: unsupportedModalCallback, header: 'Denne nettleseren støtter ikke varsler', dismiss: 'Ok' });
    connectModalToObject(_PERMISSION_MODAL_TRIGGER, { checkboxLabel: 'Ikke spør igjen', checkboxChecked: !SETTING_ASK_NOTIFICATION_PERMISSION, confirmOnclick: function () { askNotificationPermission(); hideModal(); }, dismissOnclick: permissionModalCallback, header: 'Tillatelse kreves for å bruke nettlerservarsler', confirm: 'Gi tillatelse', dismiss: 'Fortsett uten' });
    connectModalToObject(_NOTIFICATION_MODAL_TRIGGER, { noHeaderHiding: true, noBodyHiding: true, confirmOnclick: function () { hideModal(); changeModeTo('audiostream'); }, confirm: 'Begynn lydavspilling', dismiss: 'Lukk' });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            removeNotification();
        }
    });

    if (INITIAL_MODE == LISTEN_MODE) {
        initializeListenMode();
    }
});

function redirectModalCallback(onCompletion) {
    const askAgain = !$('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked');
    if (askAgain != SETTING_ASK_SECURE_REDIRECT) {
        SETTING_ASK_SECURE_REDIRECT = askAgain;
        updateSettings('listen', { ask_secure_redirect: askAgain }).then(function () {
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
        updateSettings('listen', { show_unsupported_message: showAgain });
    }
}

function permissionModalCallback() {
    const askAgain = !$('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked');
    if (askAgain != SETTING_ASK_NOTIFICATION_PERMISSION) {
        SETTING_ASK_NOTIFICATION_PERMISSION = askAgain;
        updateSettings('listen', { ask_notification_permission: askAgain });
    }
}

function initializeListenMode() {
    if (!USES_SECURE_PROTOCOL) {
        if (SETTING_ASK_SECURE_REDIRECT) {
            _REDIRECT_MODAL_TRIGGER.triggerModal();
        }
    } else if (!('Notification' in window)) {
        if (SETTING_SHOW_UNSUPPORTED_MESSAGE) {
            _UNSUPPORTED_MODAL_TRIGGER.triggerModal();
        }
    } else if (notificationsNotAllowed()) {
        if (SETTING_ASK_NOTIFICATION_PERMISSION) {
            _PERMISSION_MODAL_TRIGGER.triggerModal();
        }
    }

    styleClassificationAnimation();
    subscribeToListenMessages();

    $('#' + LISTEN_NONE_RADIO_ID).prop('disabled', false);
    $('#' + LISTEN_LIVE_RADIO_ID).prop('disabled', false);

    if (notificationsAllowed()) {
        indicateNotificationsActivated();
    } else {
        indicateNotificationsDeactivated();
    }
    $('#' + LISTEN_CONTROL_VISUALIZATION_MODE_BOX_ID).show()
}

function deactivateListenMode() {
    removeNotification();
    unsubscribeFromListenMessages();
}

function notificationsAllowed() {
    return Notification.permission === 'granted';
}

function notificationsNotAllowed() {
    return Notification.permission === 'default';
}

function notificationsBlocked() {
    return Notification.permission === 'denied';
}

function indicateNotificationsActivated() {
    $('#' + LISTEN_INACTIVE_ICON_ID).hide();
    $('#' + LISTEN_ACTIVE_ICON_ID).show();
    $('#' + NOTIFICATIONS_MSG_ID).html('Lytter etter aktivitet');
}

function indicateNotificationsDeactivated() {
    $('#' + LISTEN_INACTIVE_ICON_ID).show();
    $('#' + LISTEN_ACTIVE_ICON_ID).hide();
    $('#' + NOTIFICATIONS_MSG_ID).html('Lytter etter aktivitet<br>(Nettleservarsler er deaktivert)');
}

function askNotificationPermission() {
    function handlePermission(permission) {
        if (permission === 'granted') {
            indicateNotificationsActivated();
        } else {
            indicateNotificationsDeactivated();
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

function subscribeToListenMessages() {
    _EVENT_SOURCE = new EventSource('listen.php');
    _EVENT_SOURCE.addEventListener('notification', handleNotificationEvent);
    _EVENT_SOURCE.onerror = handleErrorEvent;
}

function handleNotificationEvent(event) {
    notificationType = event.data;
    if (notificationsAllowed()) {
        createNotification(notificationType);
    } else {
        playNotificationSound();
    }
    if (SETTING_AUTOPLAY_ON_NOTIFY) {
        changeModeTo('audiostream');
    } else {
        displayNotificationModal(notificationType)
    }
}

function playNotificationSound() {
    NOTIFICATION_SOUND.play();
}

function createNotification(notificationType) {
    removeNotification();
    _BROWSER_NOTIFICATION = new Notification(NOTIFICATION_HEADERS[notificationType], { body: NOTIFICATION_TEXTS[notificationType], tag: notificationType, renotify: true, requireInteraction: true });
}

function removeNotification() {
    if (_BROWSER_NOTIFICATION) {
        _BROWSER_NOTIFICATION.close();
        _BROWSER_NOTIFICATION = null;
    }
}

function displayNotificationModal(notificationType) {
    setModalHeaderHTML(NOTIFICATION_HEADERS[notificationType]);
    setModalBodyHTML('<p>' + NOTIFICATION_TEXTS[notificationType] + '</p>');
    _NOTIFICATION_MODAL_TRIGGER.triggerModal()
}

function handleClassificationResultEvent(event) {
    const probabilities = JSON.parse(event.data);
    moveIndicatorTo(probabilitiesToIndicatorCoordinates(probabilities));
}

function handleErrorEvent(error) {
    console.error("SSE stream failed:", error);
    _EVENT_SOURCE.close();
}

function activateLiveResultsMode() {
    _EVENT_SOURCE.addEventListener('probabilities', handleClassificationResultEvent);

    const container = $('#' + LISTEN_ANIMATION_CONTAINER_ID);
    const indicator = $('#' + LISTEN_ANIMATION_INDICATOR_ID);
    $('#' + LISTEN_ANIMATION_CONTAINER_ID).show();
    indicator.css({
        left: (container.width() / 2 - indicator.width() / 2).toFixed() + 'px',
        top: (container.height() - indicator.height() / 2).toFixed() + 'px'
    })
}

function deactivateLiveResultsMode() {
    $('#' + LISTEN_ANIMATION_CONTAINER_ID).hide();
    _EVENT_SOURCE.removeEventListener('probabilities', handleClassificationResultEvent);
}

function unsubscribeFromListenMessages() {
    if (_EVENT_SOURCE) {
        _EVENT_SOURCE.close();
    }
}

function styleClassificationAnimation() {
    $('.' + LISTEN_ANIMATION_LABEL_CLASS).get().forEach(label => {
        label.setAttribute('fill', BACKGROUND_COLOR);
    });
}

function probabilitiesToIndicatorCoordinates(probabilities) {
    const container = $('#' + LISTEN_ANIMATION_CONTAINER_ID);
    const indicator = $('#' + LISTEN_ANIMATION_INDICATOR_ID);
    const container_width = container.width();
    const container_height = container.height();
    const indicator_width = indicator.width();
    const indicator_height = indicator.height();
    const relative_center_pos_x = 0.5 * (probabilities['good'] - probabilities['bad'] + 1);
    const relative_center_pos_y = probabilities['ambient'];
    const center_pos_x = container_width * relative_center_pos_x;
    const center_pos_y = container_height * relative_center_pos_y;
    const left_pos_x = center_pos_x - 0.5 * indicator_width;
    const top_pos_y = center_pos_y - 0.5 * indicator_height;
    return { left: left_pos_x.toFixed() + 'px', top: top_pos_y.toFixed() + 'px' };
}

function moveIndicatorTo(coordinates) {
    anime({
        targets: '#' + LISTEN_ANIMATION_INDICATOR_ID,
        left: coordinates.left,
        top: coordinates.top,
        easing: 'easeInOutQuart'
    });
}
