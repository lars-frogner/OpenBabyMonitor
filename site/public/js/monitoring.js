const TEMPERATURE_LABEL_ID = 'temperature_label';
const STREAM_MONITORING_EVENTS = SETTING_MEASURE_TEMPERATURE || SETTING_WARN_UNDER_VOLTAGE || SETTING_WARN_OVERHEAT;
var _MONITORING_EVENT_SOURCE;
var _UNDER_VOLTAGE_MODAL_TRIGGER = {};
var _OVERHEAT_MODAL_TRIGGER = {};

$(function () {
    if (SETTING_WARN_UNDER_VOLTAGE) {
        connectModalToObject(_UNDER_VOLTAGE_MODAL_TRIGGER, { icon: 'lightning-charge', header: LANG['under_voltage_warning'], checkboxLabel: LANG['dont_show_again'], checkboxChecked: false, confirm: LANG['nav_shutdown'], confirmOnclick: function () { underVoltageModalCallback(function () { window.location.replace('shutdown.php'); }); }, dismiss: LANG['close'], dismissOnclick: underVoltageModalCallback }, { text: LANG['under_voltage_warning_text'], showText: () => { return true; } });
    }
    if (SETTING_WARN_OVERHEAT) {
        connectModalToObject(_OVERHEAT_MODAL_TRIGGER, { icon: 'thermometer-high', header: LANG['overheat_warning'], checkboxLabel: LANG['dont_show_again'], checkboxChecked: false, dismiss: LANG['close'], dismissOnclick: overheatModalCallback }, { text: LANG['overheat_warning_text'], showText: () => { return true; } });
    }

    subscribeToMonitoringMessages();
});

function subscribeToMonitoringMessages() {
    if (STREAM_MONITORING_EVENTS) {
        _MONITORING_EVENT_SOURCE = new EventSource('monitoring.php');
        _MONITORING_EVENT_SOURCE.onerror = handleMonitoringErrorEvent;
    }
    if (SETTING_MEASURE_TEMPERATURE) {
        _MONITORING_EVENT_SOURCE.addEventListener('temperature', handleTemperatureEvent);
    }
    if (SETTING_WARN_UNDER_VOLTAGE) {
        _MONITORING_EVENT_SOURCE.addEventListener('under_voltage', handleUnderVoltageEvent);
    }
    if (SETTING_WARN_OVERHEAT) {
        _MONITORING_EVENT_SOURCE.addEventListener('overheat', handleOverheatEvent);
    }
}

function unsubscribeFromMonitoringMessages() {
    if (_MONITORING_EVENT_SOURCE) {
        _MONITORING_EVENT_SOURCE.close();
        _MONITORING_EVENT_SOURCE = null;
    }
}

function underVoltageModalCallback(onCompletion) {
    const showAgain = !$('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked');
    if (!showAgain) {
        _MONITORING_EVENT_SOURCE.removeEventListener('under_voltage', handleUnderVoltageEvent);
    }
    if (showAgain != SETTING_WARN_UNDER_VOLTAGE) {
        SETTING_WARN_UNDER_VOLTAGE = showAgain;
        updateSettings('system', { warn_under_voltage: showAgain }).then(function () {
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

function overheatModalCallback() {
    const showAgain = !$('#' + MODAL_CONFIRM_CHECKBOX_ID).prop('checked');
    if (!showAgain) {
        _MONITORING_EVENT_SOURCE.removeEventListener('overheat', handleOverheatEvent);
    }
    if (showAgain != SETTING_WARN_OVERHEAT) {
        SETTING_WARN_OVERHEAT = showAgain;
        updateSettings('system', { warn_overheat: showAgain });
    }
}

function handleTemperatureEvent(event) {
    const temperature = event.data;
    $('#' + TEMPERATURE_LABEL_ID).html(temperature + '\xB0C');
}

function handleUnderVoltageEvent(event) {
    if (browserNotificationsAllowed()) {
        createBrowserNotification(LANG['under_voltage_warning'], '', 'under_voltage');
    } else {
        playNotificationSound();
    }
    _UNDER_VOLTAGE_MODAL_TRIGGER.triggerModal();
}

function handleOverheatEvent(event) {
    if (browserNotificationsAllowed()) {
        createBrowserNotification(LANG['overheat_warning'], '', 'overheat');
    } else {
        playNotificationSound();
    }
    _OVERHEAT_MODAL_TRIGGER.triggerModal();
}

function handleMonitoringErrorEvent(event) {
    switch (event.target.readyState) {
        case EventSource.CONNECTING:
            break
        case EventSource.CLOSED:
            _MONITORING_EVENT_SOURCE = null;
            break
        default:
            console.error("SSE stream failed:", error);
            _MONITORING_EVENT_SOURCE.close();
            _MONITORING_EVENT_SOURCE = null;
    }
}
