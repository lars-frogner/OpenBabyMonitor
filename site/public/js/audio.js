const MODE_CONTENT_AUDIO_ID = 'mode_content_audio';
const AUDIO_STREAM_PARENT_ID = MODE_CONTENT_AUDIO_ID + '_box';
const AUDIO_ID = 'audiostream_audio';

$(function () {
    if (INITIAL_MODE == AUDIOSTREAM_MODE) {
        enable_audio_stream_player();
    }
});

function enable_audio_stream_player() {
    create_audio_element();
}

function create_audio_element() {
    var audio_element = $('<audio></audio>')
        .prop({ id: AUDIO_ID, controls: true, autoplay: true, preload: 'none' })
        .append($('<source></source>').prop({ src: AUDIO_SRC, type: 'audio/mpeg' }))
        .append('Denne funksjonaliteten er ikke tilgjengelig i din nettleser.');
    $('#' + AUDIO_STREAM_PARENT_ID).append(audio_element);
}

function disable_audio_stream_player() {
    var player = document.getElementById(AUDIO_ID);
    if (player) {
        player.remove();
    }
}
