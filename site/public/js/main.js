const MAIN_CONTAINER_ID = 'main_container';
const FOOTER_CONTAINER_ID = 'footer_container';

const MODE_RADIO_IDS = ['mode_radio_standby', 'mode_radio_listen', 'mode_radio_audiostream', 'mode_radio_videostream'];
const MODE_CONTENT_IDS = ['mode_content_standby', MODE_CONTENT_LISTEN_ID, MODE_CONTENT_AUDIO_ID, MODE_CONTENT_VIDEO_ID];

const WAITING_CONTENT_ID = 'mode_content_waiting';

const ERROR_CONTENT_ID = 'mode_content_error';
const ERROR_CONTENT_MESSAGE_ID = 'mode_content_error_message';

var _CURRENT_MODE = INITIAL_MODE;

$(function () {
    registerModeChangeHandler();
    setDisabledForRelevantElements(false);
    $('#' + MAIN_CONTAINER_ID).show();
    $('#' + FOOTER_CONTAINER_ID).show();
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
            swapRadio(radio);
            requestModeChange(radio);
        });
    });
}

function changeModeTo(modeName) {
    $('#mode_radio_' + modeName).click();
}

function swapRadio(radio) {
    MODE_RADIO_IDS.forEach(radioId => {
        if (radioId == radio.prop('id')) {
            radio.prop('checked', true);
            radio.parent().addClass('active');
        } else {
            otherRadio = $('#' + radioId);
            otherRadio.prop('checked', false);
            otherRadio.parent().removeClass('active');
        }
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
        .then(responseText => { logoutIfSessionExpired(responseText); handleModeChangeResponse(radio.prop('id'), radio.prop('value'), responseText); })
        .catch(error => {
            console.error(error)
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
