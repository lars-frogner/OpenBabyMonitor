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
        .then(response_text => handleModeChangeResponse(radio.id, radio.value, response_text))
        .catch(error => {
            console.log(error)
        });

    indicateWaiting();
}

function indicateWaiting() {
    setDisabledForModeRadios(true);
    setVisibleContent(WAITING_CONTENT_ID);
}

function handleModeChangeResponse(checked_radio_id, checked_radio_value, response_text) {
    switch (response_text) {
        case '0':
            setVisibleContent(getContentIdByRadioId(checked_radio_id));
            setDisabledForModeRadios(false);
            setCurrentMode(checked_radio_value);
            break;
        default:
            if (response_text == '') {
                response_text = 'An error occured on the server.'
            }
            $('#' + ERROR_CONTENT_MESSAGE_ID).html(response_text);
            setVisibleContent(ERROR_CONTENT_ID);
            break;
    }
}

function setDisabledForModeRadios(is_disabled) {
    MODE_RADIO_IDS.forEach(radio_id => {
        $('#' + radio_id).prop('disabled', is_disabled);
    });
}

function setVisibleContent(visible_content_id) {
    var error_content = $('#' + ERROR_CONTENT_ID);
    if (visible_content_id == ERROR_CONTENT_ID) {
        error_content.show();
    } else {
        error_content.hide();
    }
    var waiting_content = $('#' + WAITING_CONTENT_ID);
    if (visible_content_id == WAITING_CONTENT_ID) {
        waiting_content.show();
    } else {
        waiting_content.hide();
    }
    if (visible_content_id == MODE_CONTENT_VIDEO_ID) {
        show_video_stream_player();
    } else {
        hide_video_stream_player();
    }
    MODE_CONTENT_IDS.forEach(content_id => {
        var content = $('#' + content_id);
        if (content_id == visible_content_id) {
            content.show();
        } else {
            content.hide();
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

function create_video_element() {
    var video_element = $('<video></video>')
        .addClass('video-js vjs-default-skin vjs-big-play-centered vjs-fill')
        .prop({ id: VIDEO_STREAM_DIV_ID, controls: true })
        .hide();
    $('#' + VIDEO_STREAM_PARENT_ID).append(video_element);
    videojs(video_element.get(0), { autoplay: 'now', preload: 'metadata', responsive: true }, function () {
        this.src({ src: VIDEO_STREAM_SRC, type: VIDEO_STREAM_TYPE });
        $('#' + VIDEO_STREAM_DIV_ID).show(); // New parent element of the video element created by video-js
        $('#' + VIDEO_STREAM_ID).show(); // The actual video element
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
