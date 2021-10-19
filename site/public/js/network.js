const MEASURE_BANDWIDTH_BUTTON_ID = 'measure_bandwidth_button';
const MEASURE_BANDWIDTH_BUSY_SPINNER_ID = 'measure_bandwidth_busy_spinner';
const LATENCY_CONTAINER_ID = 'latency_container';
const DOWNLOAD_SPEED_CONTAINER_ID = 'download_speed_container';
const LATENCY_ICON_ID = 'latency_icon';
const DOWNLOAD_SPEED_ICON_ID = 'download_speed_icon';
const LATENCY_TEXT_ID = 'latency_text';
const DOWNLOAD_SPEED_TEXT_ID = 'download_speed_text';
const CONNECTION_PROGRESS_BAR_ID = 'connection_progress_bar';
const CONNECTION_PROGRESS_BAR_CONTAINER_ID = 'connection_progress_bar_container';
const CONNECTION_RESULTS_MESSAGE_ID = 'connection_results_message';
const CONNECTION_RESULTS_LISTEN_ID = 'connection_results_listen';
const CONNECTION_RESULTS_AUDIO_ID = 'connection_results_audio';
const CONNECTION_RESULTS_VIDEO_ID = 'connection_results_video';
const CONNECTION_RESULTS_LISTEN_TEXT_ID = 'connection_results_listen_text';
const CONNECTION_RESULTS_AUDIO_TEXT_ID = 'connection_results_audio_text';
const CONNECTION_RESULTS_VIDEO_TEXT_ID = 'connection_results_video_text';

const TARGET_FILE_TRANSIT_TIME = 500;
const MIN_TEST_FILE_SIZE = 5;
const MAX_TEST_FILE_SIZE = 10000;
const INITIAL_TEST_FILE_SIZE = 100;
const N_INITIAL_MEASUREMENTS = 4;
const N_MEASUREMENTS = 16;
const INITIAL_LIMIT_STRENGTH = 2.0;
const MAX_RELATIVE_INCREASE = 1.2;
const MAX_RELATIVE_DECREASE = 1 / MAX_RELATIVE_INCREASE;
const STEP_LENGTH_FRACTION = 0.7;
const DISCARD_LIMIT = 0.2;

$(function () {
    const button = $('#' + MEASURE_BANDWIDTH_BUTTON_ID);
    button.click(measureAndDisplayBandwidth);
    button.prop('disabled', false);
});

function measureAndDisplayBandwidth() {
    const button = $('#' + MEASURE_BANDWIDTH_BUTTON_ID);
    const spinner = $('#' + MEASURE_BANDWIDTH_BUSY_SPINNER_ID);
    const latencyContainer = $('#' + LATENCY_CONTAINER_ID);
    const downloadContainer = $('#' + DOWNLOAD_SPEED_CONTAINER_ID);
    const latencyText = $('#' + LATENCY_TEXT_ID);
    const downloadText = $('#' + DOWNLOAD_SPEED_TEXT_ID);
    const progressBar = $('#' + CONNECTION_PROGRESS_BAR_ID);
    const progressBarContainer = $('#' + CONNECTION_PROGRESS_BAR_CONTAINER_ID);
    const resultsMessage = $('#' + CONNECTION_RESULTS_MESSAGE_ID);
    const resultsListen = $('#' + CONNECTION_RESULTS_LISTEN_ID);
    const resultsAudio = $('#' + CONNECTION_RESULTS_AUDIO_ID);
    const resultsVideo = $('#' + CONNECTION_RESULTS_VIDEO_ID);
    const resultsListenText = $('#' + CONNECTION_RESULTS_LISTEN_TEXT_ID);
    const resultsAudioText = $('#' + CONNECTION_RESULTS_AUDIO_TEXT_ID);
    const resultsVideoText = $('#' + CONNECTION_RESULTS_VIDEO_TEXT_ID);

    progressBar.prop('style', 'width: 0%');

    resultsMessage.hide();
    resultsListen.hide();
    resultsAudio.hide();
    resultsVideo.hide();
    resultsListenText.html('');
    resultsAudioText.html('');
    resultsVideoText.html('');
    button.hide();
    spinner.show();
    latencyText.html('');
    downloadText.html('');
    latencyContainer.show();
    downloadContainer.show();
    progressBarContainer.show();

    measureMeanRoundTripTimeAndDownloadSpeed(
        idx => {
            const progress = 100 * (idx + 1) / (N_INITIAL_MEASUREMENTS + N_MEASUREMENTS);
            progressBar.prop('style', 'width: ' + progress.toFixed() + '%');
        },
        function (roundTripTime, downloadSpeed) {
            latencyText.html(formatLatency(roundTripTime));
            downloadText.html(formatSpeed(downloadSpeed));
        }).then(result => {
            spinner.hide();
            progressBarContainer.hide();
            button.show();
            const [roundTripTime, downloadSpeed] = result;
            showConnectionResults(downloadSpeed);
        });
}

function showConnectionResults(downloadSpeed) {
    const resultsMessage = $('#' + CONNECTION_RESULTS_MESSAGE_ID);
    const resultsListen = $('#' + CONNECTION_RESULTS_LISTEN_ID);
    const resultsAudio = $('#' + CONNECTION_RESULTS_AUDIO_ID);
    const resultsVideo = $('#' + CONNECTION_RESULTS_VIDEO_ID);
    const resultsListenText = $('#' + CONNECTION_RESULTS_LISTEN_TEXT_ID);
    const resultsAudioText = $('#' + CONNECTION_RESULTS_AUDIO_TEXT_ID);
    const resultsVideoText = $('#' + CONNECTION_RESULTS_VIDEO_TEXT_ID);

    resultsMessage.show()

    if (downloadSpeed < 35) {
        resultsListen.show();
    } else if (downloadSpeed < 200) {
        resultsAudioText.html('32 kbps');
        resultsListen.show();
        resultsAudio.show()
    } else if (downloadSpeed < 700) {
        resultsListen.show();
        resultsAudio.show()
    } else if (downloadSpeed < 1800) {
        resultsVideoText.html('480p');
        resultsListen.show();
        resultsAudio.show()
        resultsVideo.show()
    } else if (downloadSpeed < 4500) {
        resultsVideoText.html('720p');
        resultsListen.show();
        resultsAudio.show()
        resultsVideo.show()
    } else {
        resultsVideoText.html('1080p');
        resultsListen.show();
        resultsAudio.show()
        resultsVideo.show()
    }
}

function formatLatency(latency) {
    const roundToNearest = 10 ** (Math.floor(Math.log10(latency)) - 1);
    return (Math.ceil(latency / roundToNearest) * roundToNearest).toFixed() + ' ms';
}

function formatSpeed(speed) {
    speed *= 1e-3; // [Mbps]
    if (speed >= 1e1) {
        return speed.toFixed() + ' Mbps';
    } else if (speed >= 1.0) {
        return speed.toFixed(1) + ' Mbps';
    } else if (speed >= 1e-1) {
        return (Math.ceil(speed * 1e3 / 100) * 100).toFixed() + ' kbps';
    } else if (speed >= 1e-2) {
        return (Math.ceil(speed * 1e3 / 10) * 10).toFixed() + ' kbps';
    } else if (speed >= 1e-3) {
        return (speed * 1e3).toFixed() + ' kbps';
    } else {
        return (speed * 1e3).toFixed(1) + ' kbps';
    }
}

async function measureMeanRoundTripTimeAndDownloadSpeed(iterationCallback, usePartialResults) {
    var fileSize = INITIAL_TEST_FILE_SIZE;
    var roundTripTimes = [];
    var downloadSpeeds = [];
    var aggregateRoundTripTime, aggregateDownloadSpeed;
    for (var i = 0; i < N_INITIAL_MEASUREMENTS + N_MEASUREMENTS; i++) {
        const [roundTripTime, fileTransmitTime, downloadSpeed] = await timeDataRequest(fileSize);
        if (i >= N_INITIAL_MEASUREMENTS) {
            aggregateRoundTripTime = processNewMeasurement(roundTripTimes, roundTripTime);
            aggregateDownloadSpeed = processNewMeasurement(downloadSpeeds, downloadSpeed);
            if (usePartialResults) {
                usePartialResults(aggregateRoundTripTime, aggregateDownloadSpeed);
            }
            fileSize = determineNextFileSize(fileSize, fileTransmitTime, 1.0);
        } else {
            fileSize = determineNextFileSize(fileSize, fileTransmitTime, INITIAL_LIMIT_STRENGTH);
        }
        iterationCallback(i);
    }
    return [aggregateRoundTripTime, aggregateDownloadSpeed];
}

function processNewMeasurement(array, newValue) {
    array.splice(sortedIndex(array, newValue), 0, newValue);
    const discardNum = Math.floor(array.length * DISCARD_LIMIT);
    var total = 0;
    for (var i = discardNum; i < array.length - discardNum; i++) {
        total += array[i];
    }
    return total / (array.length - 2 * discardNum);
}

function sortedIndex(array, value) {
    var low = 0, high = array.length;

    while (low < high) {
        var mid = (low + high) >>> 1;
        if (array[mid] < value) low = mid + 1;
        else high = mid;
    }
    return low;
}

function determineNextFileSize(fileSize, fileTransmitTime, strengthOfChangeLimit) {
    var nextfileSize = fileSize * (1 - STEP_LENGTH_FRACTION) + STEP_LENGTH_FRACTION * fileSize * TARGET_FILE_TRANSIT_TIME / fileTransmitTime;
    if (nextfileSize / fileSize > MAX_RELATIVE_INCREASE * strengthOfChangeLimit) {
        nextfileSize = MAX_RELATIVE_INCREASE * fileSize;
    } else if (nextfileSize / fileSize < MAX_RELATIVE_DECREASE / strengthOfChangeLimit) {
        nextfileSize = MAX_RELATIVE_DECREASE * fileSize;
    }
    return Math.min(MAX_TEST_FILE_SIZE, Math.max(MIN_TEST_FILE_SIZE, nextfileSize));
}

async function timeDataRequest(fileSize) {
    const startTime = performance.now();
    const response = await fetch('send_bandwidth_test_data.php?file_size=' + (fileSize * 1000).toFixed())
        .catch(error => {
            console.log(error)
        });
    const firstResponseTimeMS = performance.now() - startTime;

    const buffer = await response.arrayBuffer();
    const lastResponseTimeMS = performance.now() - startTime;

    const fileReadTimeMS = new Float32Array(buffer, 0, 1)[0];
    const fileTransmitTimeMS = lastResponseTimeMS - fileReadTimeMS;

    const bodySizeBits = fileSize * 1000 * 8 + 32;
    const downloadSpeedKiloBitsPerSec = bodySizeBits / fileTransmitTimeMS;

    return [firstResponseTimeMS, fileTransmitTimeMS, downloadSpeedKiloBitsPerSec];
}
