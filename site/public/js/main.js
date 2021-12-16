const MAIN_CONTAINER_ID = 'main_container';
const FOOTER_CONTAINER_ID = 'footer_container';

const MODE_RADIO_STANDBY_ID = 'mode_radio_standby';
const MODE_RADIO_LISTEN_ID = 'mode_radio_listen';
const MODE_RADIO_AUDIO_ID = 'mode_radio_audiostream';

var radioIds = [MODE_RADIO_STANDBY_ID, MODE_RADIO_LISTEN_ID, MODE_RADIO_AUDIO_ID];
var contentIds = ['mode_content_standby', MODE_CONTENT_LISTEN_ID, MODE_CONTENT_AUDIO_ID];
if (USES_CAMERA) {
    radioIds.push('mode_radio_videostream');
    contentIds.push(MODE_CONTENT_VIDEO_ID);
}
const MODE_RADIO_IDS = radioIds;
const MODE_CONTENT_IDS = contentIds;

const WAITING_CONTENT_ID = 'mode_content_waiting';

const ERROR_CONTENT_ID = 'mode_content_error';
const ERROR_CONTENT_MESSAGE_ID = 'mode_content_error_message';

var _CURRENT_MODE = INITIAL_MODE;
var _IS_SWITCHING_MODE = false;
var _ONLY_WAIT_FOR_STREAM = false;

$(function () {
    registerModeChangeHandler();
    setDisabledForRelevantElements(false);
    $('#' + MAIN_CONTAINER_ID).show();
    $('#' + FOOTER_CONTAINER_ID).show();
    setAllowSessionTimeout(INITIAL_MODE == STANDBY_MODE);

    addModeMonitoringEventListener(function (event) {
        if (!isSwitchingMode()) {
            const newModeName = event.data;
            _ONLY_WAIT_FOR_STREAM = true;
            changeModeTo(newModeName);
            _ONLY_WAIT_FOR_STREAM = false;
        }
    });

    setupSleepPrevention();
});

function setCurrentMode(mode) {
    _CURRENT_MODE = mode;
}

function getCurrentMode() {
    return _CURRENT_MODE;
}

function isSwitchingMode() {
    return _IS_SWITCHING_MODE;
}

function onlyWaitForStream() {
    return _ONLY_WAIT_FOR_STREAM;
}

function getModeRadio(modeName) {
    return $('#mode_radio_' + modeName);
}

function setupSleepPrevention() {
    const NO_SLEEP = new NoSleep();

    $('#' + MODE_RADIO_LISTEN_ID).click(function () {
        if (!NO_SLEEP.enabled) {
            NO_SLEEP.enable();
        }
    });

    $('#' + MODE_RADIO_AUDIO_ID).click(function () {
        if (!NO_SLEEP.enabled) {
            NO_SLEEP.enable();
        }
    });

    $('#' + MODE_RADIO_STANDBY_ID).click(function () {
        if (NO_SLEEP.enabled) {
            NO_SLEEP.disable();
        }
    });
}

function registerModeChangeHandler() {
    MODE_RADIO_IDS.forEach(id => {
        var radio = $('#' + id);
        radio.change(function () {
            swapRadio(radio);
            requestModeChange(radio);
        });
    });
}

function changeModeTo(newModeName) {
    getModeRadio(newModeName).click();
}

function swapRadio(radio) {
    MODE_RADIO_IDS.forEach(radioId => {
        if (radioId == radio.prop('id')) {
            radio.parent().addClass('active');
        } else {
            otherRadio = $('#' + radioId);
            otherRadio.parent().removeClass('active');
        }
    });
}

function requestModeChange(radio) {
    _IS_SWITCHING_MODE = true;

    const checkedRadioId = radio.prop('id');
    const newMode = radio.prop('value');

    var data = new URLSearchParams();
    data.append(onlyWaitForStream() ? 'wait_for_mode_stream' : 'requested_mode', newMode);

    fetch('change_mode.php', {
        method: 'post',
        body: data
    })
        .then(response => response.text())
        .then(responseText => { handleModeChangeResponse(checkedRadioId, newMode, responseText); })
        .catch(error => {
            console.error(error)
        });

    indicateWaiting();
}

function indicateWaiting() {
    setDisabledForRelevantElements(true);
    setVisibleContent(WAITING_CONTENT_ID);
}

function handleModeChangeResponse(checkedRadioId, newMode, responseText) {
    switch (responseText) {
        case '0':
            handleSuccessfulModeChangeResponse(checkedRadioId, newMode);
            break;
        case '-1':
            logout();
            break;
        default:
            handleFailedModeChangeResponse(responseText);
            break;
    }
}

function handleSuccessfulModeChangeResponse(checkedRadioId, newMode) {
    setCurrentMode(newMode);
    setVisibleContent(getContentIdByRadioId(checkedRadioId));
    setDisabledForRelevantElements(false);
    setAllowSessionTimeout(checkedRadioId == MODE_RADIO_STANDBY_ID);
    _IS_SWITCHING_MODE = false;
}

function handleFailedModeChangeResponse(responseText) {
    if (!responseText) {
        responseText = LANG['server_error']
    }
    $('#' + ERROR_CONTENT_MESSAGE_ID).html(responseText);
    setVisibleContent(ERROR_CONTENT_ID);
    allowSessionTimeout();
    _IS_SWITCHING_MODE = false;
}

function setDisabledForModeRadios(isDisabled) {
    MODE_RADIO_IDS.forEach(radioId => {
        radio = $('#' + radioId);
        radio.prop('disabled', isDisabled);
        if (isDisabled) {
            radio.parent().addClass('disabled');
        } else {
            radio.parent().removeClass('disabled');
        }
    });
}

function setDisabledForRelevantElements(isDisabled) {
    setDisabledForModeRadios(isDisabled);
    setDisabledForNavbar(isDisabled);
}

function setVisibleContent(visibleContentId) {
    var errorContent = $('#' + ERROR_CONTENT_ID);
    if (visibleContentId == ERROR_CONTENT_ID) {
        errorContent.show();
    } else {
        errorContent.hide();
    }
    var waitingContent = $('#' + WAITING_CONTENT_ID);
    if (visibleContentId == WAITING_CONTENT_ID) {
        waitingContent.show();
    } else {
        waitingContent.hide();
    }
    if (USES_CAMERA) {
        if (visibleContentId == MODE_CONTENT_VIDEO_ID) {
            enableVideoStreamPlayer();
        } else {
            disableVideoStreamPlayer();
        }
    }
    MODE_CONTENT_IDS.forEach(contentId => {
        var content = $('#' + contentId);
        if (contentId == visibleContentId) {
            content.show();
        } else {
            content.hide();
        }
    });
    if (visibleContentId == MODE_CONTENT_LISTEN_ID) {
        initializeListenMode();
    } else {
        deactivateListenMode();
    }
    if (visibleContentId == MODE_CONTENT_AUDIO_ID) {
        enableAudioStreamPlayer();
    } else {
        disableAudioStreamPlayer();
    }
}

function getContentIdByRadioId(radioId) {
    const idx = MODE_RADIO_IDS.indexOf(radioId);
    if (idx >= 0) {
        return MODE_CONTENT_IDS[idx];
    } else {
        console.error('Invalid radio ID' + radioId);
        return null;
    }
}
