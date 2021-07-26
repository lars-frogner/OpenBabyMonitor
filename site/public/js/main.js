const MODE_CONTENT_VIDEO_ID = 'mode_content_video';
const VIDEO_STREAM_PARENT_ID = MODE_CONTENT_VIDEO_ID + '_box';
const VIDEO_STREAM_DIV_ID = 'video_stream';
const VIDEO_STREAM_ID = VIDEO_STREAM_DIV_ID + '_html5_api';
const VIDEO_STREAM_SRC = 'hls/index.m3u8';
const VIDEO_STREAM_TYPE = 'application/x-mpegURL';

const MODE_RADIO_IDS = ['mode_radio_standby', 'mode_radio_listen', 'mode_radio_audio', 'mode_radio_video'];
const MODE_CONTENT_IDS = ['mode_content_standby', 'mode_content_listen', 'mode_content_audio', MODE_CONTENT_VIDEO_ID];

const WAITING_CONTENT_ID = 'mode_content_waiting';

const ERROR_CONTENT_ID = 'mode_content_error';
const ERROR_CONTENT_MESSAGE_ID = 'mode_content_error_message';

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
    setVisibleContent(WAITING_CONTENT_ID);
}

function handleModeChangeResponse(checked_radio_id, response_text) {
    switch (response_text) {
        case '0':
            setVisibleContent(getContentIdByRadioId(checked_radio_id));
            setDisabledForModeRadios(false);
            break;
        default:
            document.getElementById(ERROR_CONTENT_MESSAGE_ID).innerHTML = response_text;
            setVisibleContent(ERROR_CONTENT_ID);
            break;
    }
}

function setDisabledForModeRadios(is_disabled) {
    MODE_RADIO_IDS.forEach(radio_id => {
        document.getElementById(radio_id).disabled = is_disabled;
    });
}

function setVisibleContent(visible_content_id) {
    const error_content = document.getElementById(ERROR_CONTENT_ID);
    if (visible_content_id == ERROR_CONTENT_ID) {
        showElement(error_content);
    } else {
        hideElement(error_content);
    }
    const waiting_content = document.getElementById(WAITING_CONTENT_ID);
    if (visible_content_id == WAITING_CONTENT_ID) {
        showElement(waiting_content);
    } else {
        hideElement(waiting_content);
    }
    if (visible_content_id == MODE_CONTENT_VIDEO_ID) {
        show_video_stream_player();
    } else {
        hide_video_stream_player();
    }
    MODE_CONTENT_IDS.forEach(content_id => {
        const content = document.getElementById(content_id);
        if (content_id == visible_content_id) {
            showElement(content);
        } else {
            hideElement(content);
        }
    });
}

function getContentIdByRadioId(radio_id) {
    const idx = MODE_RADIO_IDS.indexOf(radio_id);
    if (idx >= 0) {
        return MODE_CONTENT_IDS[idx];
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

function create_video_element() {
    var video_element = document.createElement('video');
    video_element.id = VIDEO_STREAM_DIV_ID;
    video_element.className = 'video-js vjs-default-skin vjs-big-play-centered vjs-fill';
    video_element.controls = true;
    hideElement(video_element);
    document.getElementById(VIDEO_STREAM_PARENT_ID).appendChild(video_element);
    videojs(video_element, { autoplay: 'now', preload: 'metadata', responsive: true }, function () {
        this.src({ src: VIDEO_STREAM_SRC, type: VIDEO_STREAM_TYPE });
        var div_element = document.getElementById(VIDEO_STREAM_DIV_ID); // New parent element of the video element created by video-js
        var video_element = document.getElementById(VIDEO_STREAM_ID); // The actual video element
        showElement(div_element);
        showElement(video_element);
    });
}

function show_video_stream_player() {
    create_video_element();
}

function hide_video_stream_player() {
    var player = videojs.getPlayer(VIDEO_STREAM_ID);
    if (player && !player.isDisposed()) {
        player.dispose();
    }
}
