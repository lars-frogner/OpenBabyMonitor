const MODE_CONTENT_VIDEO_ID = 'mode_content_video';
const VIDEO_STREAM_PARENT_ID = MODE_CONTENT_VIDEO_ID + '_box';
const VIDEO_STREAM_DIV_ID = 'video_stream';
const VIDEO_STREAM_ID = VIDEO_STREAM_DIV_ID + '_html5_api';
const VIDEO_STREAM_SRC = 'hls/index.m3u8';
const VIDEO_STREAM_TYPE = 'application/x-mpegURL';

$(function () {
    if (INITIAL_MODE == VIDEOSTREAM_MODE) {
        enable_video_stream_player();
    }
});

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

function enable_video_stream_player() {
    create_video_element();
}

function disable_video_stream_player() {
    var player = videojs.getPlayer(VIDEO_STREAM_ID);
    if (player && !player.isDisposed()) {
        player.dispose();
    }
}
