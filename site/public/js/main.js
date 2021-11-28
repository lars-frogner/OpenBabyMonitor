const MAIN_CONTAINER_ID = 'main_container';
const FOOTER_CONTAINER_ID = 'footer_container';

var radioIds = ['mode_radio_standby', 'mode_radio_listen', 'mode_radio_audiostream'];
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
            radio.parent().addClass('active');
        } else {
            otherRadio = $('#' + radioId);
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
        .then(responseText => { handleModeChangeResponse(radio.prop('id'), radio.prop('value'), responseText); })
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
        case '-1':
            logout();
            break;
        default:
            if (!responseText) {
                responseText = LANG['server_error']
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
    if (USES_CAMERA && visibleContentId == MODE_CONTENT_VIDEO_ID) {
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
