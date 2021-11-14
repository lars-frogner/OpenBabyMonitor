var _EVENT_SOURCE;

$(function () {
    subscribeToMonitoringMessages()
});

function subscribeToMonitoringMessages() {
    _EVENT_SOURCE = new EventSource('monitoring.php');
    _EVENT_SOURCE.addEventListener('temperature', handleTemperatureEvent);
    _EVENT_SOURCE.addEventListener('under_voltage', handleUnderVoltageEvent);
    _EVENT_SOURCE.addEventListener('frequency_capped', handleFrequencyCappedEvent);
    _EVENT_SOURCE.addEventListener('throttled', handleThrottledEvent);
    _EVENT_SOURCE.addEventListener('temp_lim_active', handleTempLimActiveEvent);
    _EVENT_SOURCE.onerror = handleErrorEvent;
}

function unsubscribeFromMonitoringMessages() {
    if (_EVENT_SOURCE) {
        _EVENT_SOURCE.close();
    }
}

function handleTemperatureEvent(event) {
    const temperature = event.data;
    console.log(temperature);
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

function handleErrorEvent(error) {
    console.error("SSE stream failed:", error);
    _EVENT_SOURCE.close();
}
