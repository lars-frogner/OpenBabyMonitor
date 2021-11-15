const MODE_CONTENT_LISTEN_ID = 'mode_content_listen';
const NOTIFICATIONS_MSG_ID = "listen_notifications_msg";
const LISTEN_NONE_RADIO_ID = "listen_none_radio";
const LISTEN_LIVE_RADIO_ID = "listen_live_radio";
const LISTEN_INACTIVE_ICON_ID = "listen_inactive_icon";
const LISTEN_ACTIVE_ICON_ID = "listen_active_icon";
const LISTEN_CONTROL_VISUALIZATION_MODE_BOX_ID = 'listen_visualization_mode_box';
const LISTEN_ANIMATION_CONTAINER_ID = "listen_animation_container";
const LISTEN_ANIMATION_CONTEXT_ID = "listen_animation_context";
const LISTEN_ANIMATION_INDICATOR_ID = "listen_animation_indicator";
const LISTEN_ANIMATION_LINE_ID = "listen_animation_line";
const LISTEN_ANIMATION_LABEL_CLASS = "listen-animation-label";
const LISTEN_ANIMATION_LABEL_BG_CLASS = "listen-animation-label-bg";
const LISTEN_ANIMATION_INDICATOR_CLASS = "listen-animation-indicator";

const MIN_DBFS = -50;
const MAX_DBFS = 0;

const NOTIFICATION_HEADERS = { sound: LANG['sound_alert'], bad: LANG['crying_alert'], good: LANG['babbling_alert'], bad_and_good: LANG['crying_and_babbling_alert'], bad_or_good: LANG['crying_or_babbling_alert'] };
const NOTIFICATION_TEXTS = { sound: LANG['significant_sound'], bad: LANG['child_crying'], good: LANG['child_babbling'], bad_and_good: LANG['child_crying_and_babbling'], bad_or_good: LANG['child_crying_or_babbling'] };

const NOTIFICATION_SOUND = new Audio('media/notification_sound.mp3');

const USES_SECURE_PROTOCOL = location.protocol === 'https:';
const SECURE_URL = 'https://' + location.host + location.pathname;

var _LISTEN_EVENT_SOURCE;
var _REDIRECT_MODAL_TRIGGER = {};
var _UNSUPPORTED_MODAL_TRIGGER = {};
var _PERMISSION_MODAL_TRIGGER = {};
var _NOTIFICATION_MODAL_TRIGGER = {};
var _BROWSER_NOTIFICATION;

$(function () {
    connectModalToObject(_REDIRECT_MODAL_TRIGGER, { checkboxLabel: LANG['dont_ask_again'], checkboxChecked: !SETTING_ASK_SECURE_REDIRECT, confirmOnclick: function () { redirectModalCallback(function () { window.location.replace(SECURE_URL); }); }, dismissOnclick: redirectModalCallback, header: LANG['not_supported_unencrypted'], confirm: LANG['switch_site'], dismiss: LANG['stay'] }, { text: LANG['want_to_switch_site'], showText: () => { return true; } });
    connectModalToObject(_UNSUPPORTED_MODAL_TRIGGER, { checkboxLabel: LANG['dont_show_again'], checkboxChecked: !SETTING_SHOW_UNSUPPORTED_MESSAGE, dismissOnclick: unsupportedModalCallback, header: LANG['not_supported_by_browser'], dismiss: LANG['ok'] });
    connectModalToObject(_PERMISSION_MODAL_TRIGGER, { checkboxLabel: LANG['dont_ask_again'], checkboxChecked: !SETTING_ASK_NOTIFICATION_PERMISSION, confirmOnclick: function () { askNotificationPermission(); hideModalWithoutDismissCallback(); }, dismissOnclick: permissionModalCallback, header: LANG['premission_required'], confirm: LANG['grant_permission'], dismiss: LANG['continue_without'] });
    connectModalToObject(_NOTIFICATION_MODAL_TRIGGER, { icon: 'exclamation-circle', noHeaderHiding: true, noBodyHiding: true, confirmOnclick: function () { hideModalWithoutDismissCallback(); changeModeTo('audiostream'); }, confirm: 'Begynn lydavspilling', dismiss: 'Lukk' });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            removeNotification();
        }
    });

    if (INITIAL_MODE == LISTEN_MODE) {
        initializeListenMode();
    }
});

function usesNeuralNetworkModel() {
    return SETTING_MODEL != 'sound_level_threshold';
}

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
    $('#' + NOTIFICATIONS_MSG_ID).html(LANG['listening_for_activity']);
}

function indicateNotificationsDeactivated() {
    $('#' + LISTEN_INACTIVE_ICON_ID).show();
    $('#' + LISTEN_ACTIVE_ICON_ID).hide();
    $('#' + NOTIFICATIONS_MSG_ID).html(LANG['listening_for_activity'] + '<br>(' + LANG['browser_notifications_deactivated'] + ')');
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
    _LISTEN_EVENT_SOURCE = new EventSource('listen.php');
    _LISTEN_EVENT_SOURCE.addEventListener('notification', handleNotificationEvent);
    _LISTEN_EVENT_SOURCE.onerror = handleErrorEvent;
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

function handleSoundLevelEvent(event) {
    const soundLevel = parseFloat(event.data);
    moveIndicatorTo(soundLevelToIndicatorCoordinates(soundLevel));
}

function handleErrorEvent(event) {
    switch (event.target.readyState) {
        case EventSource.CONNECTING:
            break
        case EventSource.CLOSED:
            _LISTEN_EVENT_SOURCE = null;
            break
        default:
            console.error("SSE stream failed:", error);
            _LISTEN_EVENT_SOURCE.close();
            _LISTEN_EVENT_SOURCE = null;
    }
}

function activateLiveResultsMode() {
    $('#' + LISTEN_ANIMATION_CONTAINER_ID).show();
    if (usesNeuralNetworkModel()) {
        _LISTEN_EVENT_SOURCE.addEventListener('probabilities', handleClassificationResultEvent);

        const container = $('#' + LISTEN_ANIMATION_CONTAINER_ID);
        const indicator = $('#' + LISTEN_ANIMATION_INDICATOR_ID);
        indicator.css({
            left: (container.width() / 2 - indicator.width() / 2).toFixed() + 'px',
            top: (container.height() - indicator.height() / 2).toFixed() + 'px'
        })
    } else {
        _LISTEN_EVENT_SOURCE.addEventListener('sound_level', handleSoundLevelEvent);
        $('#' + LISTEN_ANIMATION_CONTEXT_ID).css({ left: soundLevelToIndicatorCoordinates(SETTING_MIN_SOUND_LEVEL).left });
    }
}

function deactivateLiveResultsMode() {
    $('#' + LISTEN_ANIMATION_CONTAINER_ID).hide();
    if (usesNeuralNetworkModel()) {
        _LISTEN_EVENT_SOURCE.removeEventListener('probabilities', handleClassificationResultEvent);
    } else {
        _LISTEN_EVENT_SOURCE.removeEventListener('sound_level', handleSoundLevelEvent)
    }
}

function unsubscribeFromListenMessages() {
    if (_LISTEN_EVENT_SOURCE) {
        _LISTEN_EVENT_SOURCE.close();
        _LISTEN_EVENT_SOURCE = null;
    }
}

function styleClassificationAnimation() {
    $('#' + LISTEN_ANIMATION_LINE_ID).css('border-bottom', '1px solid ' + FOREGROUND_COLOR);
    $('.' + LISTEN_ANIMATION_LABEL_CLASS).get().forEach(label => {
        label.setAttribute('fill', FOREGROUND_COLOR);
    });
    $('.' + LISTEN_ANIMATION_LABEL_BG_CLASS).get().forEach(label => {
        label.setAttribute('fill', BACKGROUND_COLOR);
    });
    $('.' + LISTEN_ANIMATION_INDICATOR_CLASS).get().forEach(label => {
        label.style.backgroundColor = FOREGROUND_COLOR;
    });
}

function soundLevelToIndicatorCoordinates(soundLevel) {
    const relative_location = 100 * (soundLevel - MIN_DBFS) / (MAX_DBFS - MIN_DBFS);
    return { left: relative_location.toFixed() + '%' };
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
    var params = {
        targets: '#' + LISTEN_ANIMATION_INDICATOR_ID,
        easing: 'easeInOutQuart'
    };
    anime(Object.assign(params, coordinates));
}
