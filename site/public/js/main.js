const mode_radio_ids = ['mode_radio_standby', 'mode_radio_listen', 'mode_radio_audio', 'mode_radio_video'];
const mode_content_ids = ['mode_content_standby', 'mode_content_listen', 'mode_content_audio', 'mode_content_video'];
const waiting_content_id = 'mode_content_waiting';
const error_content_id = 'mode_content_error';
const error_content_message_id = 'mode_content_error_message';

function requestModeChange(radio) {
    var data = new URLSearchParams();
    data.append('requested_mode', radio.value);

    fetch('change_mode.php', {
        method: 'post',
        body: data
    })
        .then(response => response.text())
        .then(response_text => handleModeChangeResponse(radio.id, response_text))
        .catch(error => {
            console.log(error)
        });

    indicateWaiting();
}

function indicateWaiting() {
    setDisabledForModeRadios(true);
    setVisibleContent(waiting_content_id);
}

function handleModeChangeResponse(checked_radio_id, response_text) {
    console.log(response_text);
    switch (response_text) {
        case '0':
            setVisibleContent(getContentIdByRadioId(checked_radio_id));
            setDisabledForModeRadios(false);
            break;
        default:
            document.getElementById(error_content_message_id).innerHTML = response_text;
            setVisibleContent(error_content_id);
            break;
    }
}

function setDisabledForModeRadios(is_disabled) {
    mode_radio_ids.forEach(radio_id => {
        document.getElementById(radio_id).disabled = is_disabled;
    });
}

function setVisibleContent(visible_content_id) {
    const error_content = document.getElementById(error_content_id);
    if (visible_content_id == error_content_id) {
        showElement(error_content);
    } else {
        hideElement(error_content);
    }
    const waiting_content = document.getElementById(waiting_content_id);
    if (visible_content_id == waiting_content_id) {
        showElement(waiting_content);
    } else {
        hideElement(waiting_content);
    }
    mode_content_ids.forEach(content_id => {
        const content = document.getElementById(content_id);
        if (content_id == visible_content_id) {
            showElement(content);
        } else {
            hideElement(content);
        }
    });
}

function getContentIdByRadioId(radio_id) {
    const idx = mode_radio_ids.indexOf(radio_id);
    if (idx >= 0) {
        return mode_content_ids[idx];
    } else {
        alert('Invalid radio ID' + radio_id);
        return NULL;
    }
}

function hideElement(element) {
    element.style.display = 'none';
}

function showElement(element) {
    element.style.display = 'block';
}
