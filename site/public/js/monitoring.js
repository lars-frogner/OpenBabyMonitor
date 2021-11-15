const TEMPERATURE_LABEL_ID = 'temperature_label';
var _MONITORING_EVENT_SOURCE;

$(function () {
    subscribeToMonitoringMessages();
});

function subscribeToMonitoringMessages() {
    _MONITORING_EVENT_SOURCE = new EventSource('monitoring.php');
    if (MEASURE_TEMPERATURE) {
        _MONITORING_EVENT_SOURCE.addEventListener('temperature', handleTemperatureEvent);
    }
    _MONITORING_EVENT_SOURCE.addEventListener('under_voltage', handleUnderVoltageEvent);
    _MONITORING_EVENT_SOURCE.addEventListener('frequency_capped', handleFrequencyCappedEvent);
    _MONITORING_EVENT_SOURCE.addEventListener('throttled', handleThrottledEvent);
    _MONITORING_EVENT_SOURCE.addEventListener('temp_lim_active', handleTempLimActiveEvent);
    _MONITORING_EVENT_SOURCE.onerror = handleErrorEvent;
}

function unsubscribeFromMonitoringMessages() {
    if (_MONITORING_EVENT_SOURCE) {
        _MONITORING_EVENT_SOURCE.close();
        _MONITORING_EVENT_SOURCE = null;
    }
}

function handleTemperatureEvent(event) {
    const temperature = event.data;
    $('#' + TEMPERATURE_LABEL_ID).html(temperature + '\xB0C');
}

function handleUnderVoltageEvent(event) {
    console.log(event);
}

function handleFrequencyCappedEvent(event) {
    console.log(event);
}

function handleThrottledEvent(event) {
    console.log(event);
}

function handleTempLimActiveEvent(event) {
    console.log(event);
}

function handleErrorEvent(event) {
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
