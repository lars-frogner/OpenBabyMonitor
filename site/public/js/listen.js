const MODE_CONTENT_LISTEN_ID = 'mode_content_listen';
const LISTEN_ICON_ID = 'listen_icon';
const LISTEN_NONE_RADIO_ID = "listen_none_radio";
const LISTEN_LIVE_RADIO_ID = "listen_live_radio";
const LISTEN_CONTROL_VISUALIZATION_MODE_BOX_ID = 'listen_visualization_mode_box';
const LISTEN_INFO_CARD_ID = 'listen_info_card';
const LISTEN_INFO_CARD_ITEM_CLASS = 'listen-info-card-item';
const LISTEN_ANIMATION_CONTAINER_ID = "listen_animation_container";
const LISTEN_ANIMATION_CONTEXT_ID = "listen_animation_context";
const LISTEN_ANIMATION_INDICATOR_ID = "listen_animation_indicator";
const LISTEN_ANIMATION_LINE_ID = "listen_animation_line";
const LISTEN_ANIMATION_LABEL_CLASS = "listen-animation-label";
const LISTEN_ANIMATION_LABEL_BG_CLASS = "listen-animation-label-bg";
const LISTEN_ANIMATION_INDICATOR_CLASS = "listen-animation-indicator";

const MIN_FOREGROUND_CONTRAST = 0;
const MAX_FOREGROUND_CONTRAST = 40;

const LISTEN_NOTIFICATION_HEADERS = { sound: LANG['sound_alert'], bad: LANG['crying_alert'], good: LANG['babbling_alert'], bad_and_good: LANG['crying_and_babbling_alert'], bad_or_good: LANG['crying_or_babbling_alert'] };
const LISTEN_NOTIFICATION_TEXTS = { sound: LANG['significant_sound'], bad: LANG['child_crying'], good: LANG['child_babbling'], bad_and_good: LANG['child_crying_and_babbling'], bad_or_good: LANG['child_crying_or_babbling'] };

var _LISTEN_EVENT_SOURCE;
var _LISTEN_NOTIFICATION_MODAL_TRIGGER = {};
var _LISTEN_BROWSER_NOTIFICATION;

var _LAST_EVENT_TIME;

$(function () {
    connectModalToObject(_LISTEN_NOTIFICATION_MODAL_TRIGGER, { icon: 'exclamation-circle', noHeaderHiding: true, noBodyHiding: true, confirmOnclick: function () { hideModalWithoutDismissCallback(); changeModeTo('audiostream'); }, confirm: 'Begynn lydavspilling', dismiss: 'Lukk' });

    if (INITIAL_MODE == LISTEN_MODE) {
        initializeListenMode();
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            removeListenBrowserNotification();
        }
    });
});

function usesNeuralNetworkModel() {
    return SETTING_MODEL != 'sound_level_threshold';
}

function initializeListenMode() {
    styleClassificationAnimation();
    subscribeToListenMessages();

    $('#' + LISTEN_NONE_RADIO_ID).prop('disabled', false);
    $('#' + LISTEN_LIVE_RADIO_ID).prop('disabled', false);

    $('#' + LISTEN_CONTROL_VISUALIZATION_MODE_BOX_ID).show()
}

function deactivateListenMode() {
    removeListenBrowserNotification();
    unsubscribeFromListenMessages();
}

function subscribeToListenMessages() {
    _LISTEN_EVENT_SOURCE = new EventSource('listen.php');
    _LISTEN_EVENT_SOURCE.addEventListener('notification', handleListenNotificationEvent);
    _LISTEN_EVENT_SOURCE.onerror = handleListenErrorEvent;
}

function handleListenNotificationEvent(event) {
    notificationType = event.data;
    if (browserNotificationsAllowed()) {
        createListenBrowserNotification(notificationType);
    } else {
        playNotificationSound();
    }
    if (SETTING_AUTOPLAY_ON_NOTIFY) {
        changeModeTo('audiostream');
    } else {
        displayNotificationModal(notificationType)
    }
}

function createListenBrowserNotification(notificationType) {
    removeListenBrowserNotification();
    _LISTEN_BROWSER_NOTIFICATION = createBrowserNotification(LISTEN_NOTIFICATION_HEADERS[notificationType], LISTEN_NOTIFICATION_TEXTS[notificationType], notificationType);
}

function removeListenBrowserNotification() {
    if (_LISTEN_BROWSER_NOTIFICATION) {
        _LISTEN_BROWSER_NOTIFICATION.close();
        _LISTEN_BROWSER_NOTIFICATION = null;
    }
}

function displayNotificationModal(notificationType) {
    setModalHeaderHTML(LISTEN_NOTIFICATION_HEADERS[notificationType]);
    setModalBodyHTML('<p>' + LISTEN_NOTIFICATION_TEXTS[notificationType] + '</p>');
    _LISTEN_NOTIFICATION_MODAL_TRIGGER.triggerModal()
}

function handleClassificationResultEvent(event) {
    const data = JSON.parse(event.data);
    const backgroundSoundLevel = parseFloat(data['bg']);
    const foregroundContrast = parseFloat(data['ct']);
    const recordTime = parseFloat(data['t']);
    updateInfoLabel(backgroundSoundLevel, foregroundContrast, recordTime);
    moveIndicatorTo(probabilitiesToIndicatorCoordinates(data));
}

function handleSoundLevelEvent(event) {
    const data = JSON.parse(event.data);
    const backgroundSoundLevel = parseFloat(data['bg']);
    const foregroundContrast = parseFloat(data['ct']);
    const recordTime = parseFloat(data['t']);
    updateInfoLabel(backgroundSoundLevel, foregroundContrast, recordTime);
    moveIndicatorTo(foregroundContrastToIndicatorCoordinates(foregroundContrast));
}

function updateInfoLabel(backgroundSoundLevel, foregroundContrast, recordTime) {
    const now = Date.now() / 1000;
    const delay = now - parseFloat(recordTime);
    const interval = (_LAST_EVENT_TIME) ? (now - _LAST_EVENT_TIME) : 0;
    _LAST_EVENT_TIME = now;
    $('#' + LISTEN_INFO_CARD_ID + '_contrast').html(foregroundContrast.toFixed(1) + ' dB');
    $('#' + LISTEN_INFO_CARD_ID + '_bg_sound_level').html(backgroundSoundLevel.toFixed(1) + ' dB');
    $('#' + LISTEN_INFO_CARD_ID + '_delay').html(delay.toFixed(1) + ' s');
    $('#' + LISTEN_INFO_CARD_ID + '_interval').html(interval.toFixed(1) + ' s');
}

function handleListenErrorEvent(event) {
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
    shrinkListenIcon();
    $('#' + LISTEN_INFO_CARD_ID + ' .' + LISTEN_INFO_CARD_ITEM_CLASS).html('');
    $('#' + LISTEN_INFO_CARD_ID).show();
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
        $('#' + LISTEN_ANIMATION_CONTEXT_ID).css({ left: foregroundContrastToIndicatorCoordinates(SETTING_MIN_SOUND_CONTRAST).left });
    }
}

function deactivateLiveResultsMode() {
    $('#' + LISTEN_INFO_CARD_ID).hide();
    $('#' + LISTEN_ANIMATION_CONTAINER_ID).hide();
    unshrinkListenIcon();
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

function shrinkListenIcon() {
    const icon = $('#' + LISTEN_ICON_ID);
    const visualization_control = $('#' + LISTEN_CONTROL_VISUALIZATION_MODE_BOX_ID);
    icon.css({ width: '5vh', height: '5vh' });
    icon.removeClass('mb-4');
    icon.addClass('mb-2');
    visualization_control.removeClass('mt-5');
    visualization_control.addClass('mt-4');
}

function unshrinkListenIcon() {
    const icon = $('#' + LISTEN_ICON_ID);
    const visualization_control = $('#' + LISTEN_CONTROL_VISUALIZATION_MODE_BOX_ID);
    icon.css({ width: '15vh', height: '15vh' });
    icon.removeClass('mb-2');
    icon.addClass('mb-4');
    visualization_control.removeClass('mt-4');
    visualization_control.addClass('mt-5');
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

function foregroundContrastToIndicatorCoordinates(foregroundContrast) {
    const relative_location = 100 * (foregroundContrast - MIN_FOREGROUND_CONTRAST) / (MAX_FOREGROUND_CONTRAST - MIN_FOREGROUND_CONTRAST);
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
