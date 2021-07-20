const mode_radio_ids = ['mode_radio_listen', 'mode_radio_audio', 'mode_radio_video', 'mode_radio_standby'];
const mode_content_ids = ['mode_content_listen', 'mode_content_audio', 'mode_content_video', 'mode_content_standby'];
const waiting_content_id = 'mode_content_waiting';

function requestModeChange(radio) {
    var data = new URLSearchParams();
    data.append('checked_radio_id', radio.id);

    fetch('change_mode.php', {
        method: 'post',
        body: data
    })
        .then(response => {
            return handleModeChangeResponse(radio.id, response);
        })
        .catch(error => {
            console.log(error)
        });

    indicateWaiting();
}

function indicateWaiting() {
    setDisabledForModeRadios(true);
    setVisibleContent(waiting_content_id);
}

function handleModeChangeResponse(checked_radio_id, response) {
    setVisibleContent(getContentIdByRadioId(checked_radio_id));
    setDisabledForModeRadios(false);
}

function setDisabledForModeRadios(is_disabled) {
    mode_radio_ids.forEach(radio_id => {
        document.getElementById(radio_id).disabled = is_disabled;
    });
}

function setVisibleContent(visible_content_id) {
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
