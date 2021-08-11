const MODE_RADIO_IDS = ['mode_radio_standby', 'mode_radio_listen', 'mode_radio_audio', 'mode_radio_video'];
const MODE_CONTENT_IDS = ['mode_content_standby', 'mode_content_listen', MODE_CONTENT_AUDIO_ID, MODE_CONTENT_VIDEO_ID];

const WAITING_CONTENT_ID = 'mode_content_waiting';

const ERROR_CONTENT_ID = 'mode_content_error';
const ERROR_CONTENT_MESSAGE_ID = 'mode_content_error_message';

var _CURRENT_MODE = INITIAL_MODE;

$(function () {
    registerModeChangeHandler();
    setDisabledForRelevantElements(false);
});

function setCurrentMode(mode) {
    _CURRENT_MODE = mode;
}

function getCurrentMode() {
    return _CURRENT_MODE;
}

function registerModeChangeHandler() {
    MODE_RADIO_IDS.forEach(id => {
        var radio = $('#' + id);
        radio.change(function () {
            requestModeChange(radio);
        });
    });
}

function requestModeChange(radio) {
    var data = new URLSearchParams();
    data.append('requested_mode', radio.prop('value'));

    fetch('change_mode.php', {
        method: 'post',
        body: data
    })
        .then(response => response.text())
        .then(responseText => handleModeChangeResponse(radio.prop('id'), radio.prop('value'), responseText))
        .catch(error => {
            alert(error)
        });

    indicateWaiting();
}

function indicateWaiting() {
    setDisabledForRelevantElements(true);
    setVisibleContent(WAITING_CONTENT_ID);
}

function handleModeChangeResponse(checkedRadioId, checkedRadioValue, responseText) {
    switch (responseText) {
        case '0':
            setCurrentMode(checkedRadioValue);
            setVisibleContent(getContentIdByRadioId(checkedRadioId));
            setDisabledForRelevantElements(false);
            break;
        default:
            if (!responseText) {
                responseText = 'An error occured on the server.'
            }
            $('#' + ERROR_CONTENT_MESSAGE_ID).html(responseText);
            setVisibleContent(ERROR_CONTENT_ID);
            break;
    }
}

function setDisabledForModeRadios(isDisabled) {
    MODE_RADIO_IDS.forEach(radioId => {
        $('#' + radioId).prop('disabled', isDisabled);
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
    if (visibleContentId == MODE_CONTENT_VIDEO_ID) {
        enableVideoStreamPlayer();
    } else {
        disableVideoStreamPlayer();
    }
    MODE_CONTENT_IDS.forEach(contentId => {
        var content = $('#' + contentId);
        if (contentId == visibleContentId) {
            content.show();
        } else {
            content.hide();
        }
    });
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
        alert('Invalid radio ID' + radioId);
        return null;
    }
}
