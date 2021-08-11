const MODE_CONTENT_AUDIO_ID = 'mode_content_audio';
const AUDIO_STREAM_PARENT_ID = MODE_CONTENT_AUDIO_ID + '_box';
const AUDIO_ID = 'audiostream_audio';
const AUDIO_CANVAS_ID = 'audiostream_canvas';

const CANVAS_TIME_BACKGROUND = 'rgb(255, 255, 255)';
const CANVAS_TIME_FOREGROUND = 'rgb(0, 0, 0)';
const CANVAS_TIME_LINEWIDTH = 2;
const CANVAS_TIME_SAMPLE_SCALE = 100;
const CANVAS_FREQUENCY_BACKGROUND = 'rgb(255, 255, 255)';
const CANVAS_FREQUENCY_FOREGROUND = 'rgb(0, 0, 0)';
const CANVAS_FREQUENCY_SAMPLE_OFFSET = 140;
const CANVAS_FREQUENCY_SAMPLE_SCALE = 0.005;

const ANIMATION_MODES = { TIME: 'time', FREQUENCY: 'frequency' };

const SAMPLING_RATE = 44100; // [Hz]
const CHANNELS = 1;

var _AUDIO_CONTEXT = createAudioContext();
var _ANIMATION_IS_RUNNING = false;
var _STOP_ANIMATION = false;

$(function () {
    if (INITIAL_MODE == AUDIOSTREAM_MODE) {
        enableAudioStreamPlayer();
    }
});

function getAudioPlayer() {
    return document.getElementById(AUDIO_ID);
}

function getAudioCanvas() {
    return document.getElementById(AUDIO_CANVAS_ID);
}

function enableAudioStreamPlayer() {
    var canvas = createAudioCanvasElement();
    var player = createAudioElement();
    var analyser = createAudioStreamAnalyser(_AUDIO_CONTEXT, player);
    enableAudioAnimation(player, analyser, canvas, ANIMATION_MODES.FREQUENCY, 2 ** 13);
}

function disableAudioStreamPlayer() {
    stopAudioAnimation();
    removeAudioElements();
}

function createAudioElement() {
    var src = AUDIO_SRC + '?salt=' + new Date().getTime();
    var audio = $('<audio></audio>')
        .prop({ id: AUDIO_ID, controls: true, autoplay: true, crossOrigin: 'anonymous' })
        .append($('<source></source>').prop({ src: src, type: 'audio/mpeg' }))
        .append('Denne funksjonaliteten er ikke tilgjengelig i din nettleser.');
    $('#' + AUDIO_STREAM_PARENT_ID).append(audio);
    return audio.get()[0];
}

function createAudioCanvasElement() {
    var canvas = $('<canvas></canvas>').prop({ if: AUDIO_CANVAS_ID, width: 250, height: 250 });
    $('#' + AUDIO_STREAM_PARENT_ID).append(canvas);
    return canvas.get()[0];
}

function removeAudioElements() {
    $('#' + AUDIO_STREAM_PARENT_ID).empty();
}

function removeAudioElement() {
    removeElement(AUDIO_ID);
}

function removeAudioCanvasElement() {
    removeElement(AUDIO_CANVAS_ID);
}

function removeElement(elementId) {
    var element = document.getElementById(elementId)
    if (element) {
        element.remove();
    }
}

function createAudioContext() {
    return new (window.AudioContext || window.webkitAudioContext)();
}

function createAudioStreamAnalyser(audioContext, player) {
    var source = audioContext.createMediaElementSource(player);
    var analyser = audioContext.createAnalyser();
    source.connect(analyser);
    return analyser;
}

function enableAudioAnimation(player, analyser, canvas, animationMode, fftSize) {
    player.onplay = function () {
        animateAudio(analyser, canvas, animationMode, fftSize);
    }
    player.onpause = function () {
        stopAudioAnimation();
    }
}

function reEnableAnimation(player, analyser, canvas, animationMode, fftSize) {
    stopAudioAnimation();
    enableAudioAnimation(player, analyser, canvas, animationMode, fftSize)
}

function getFrequencyRange() {
    return (0, SAMPLING_RATE / 2);
}

function getTimeRange(offset, fftSize) {
    return (offset, offset + getSampleSetDuration(fftSize));
}

function getSampleSetDuration(fftSize) {
    return fftSize / SAMPLING_RATE;
}

function animateAudio(analyser, canvas, animationMode, fftSize) {
    /*
    Sample rate: 44100 Hz
    Channel: 1
    Target animation framerate: ~30 FPS => 30 ms per frame

    getFloatTimeDomainData with fftSize=1024 gives 1024 samples covering a duration of 1024/(44100 Hz) = 23 ms

    For coherence, the displayed set of samples should cover a duration longer than the frame duration (~30 ms),
    so that some of the waveform from the previous frame is still visible in the next frame.

    But the fftSize should not be so large that the frame takes longer than 30 ms to render, which would lead to
    stuttering.

    getFloatTimeDomainData with fftSize=1024 gives the strength in dB of 512 frequencies from 0 Hz to (sample rate)/2.

    The Fourier transform will be performed over a set of samples spanning 1024/(44100 Hz) = 23 ms.

    So higher fftSize gives higher frequency resolution and is taken over a larger duration.
    */
    var dataArray = selectForAnimationMode(animationMode, {
        TIME: createDataArrayForTimeDomain,
        FREQUENCY: createDataArrayForFrequencyDomain
    })(analyser, fftSize);

    var fetchData = selectForAnimationMode(animationMode, {
        TIME: (dataArray) => analyser.getFloatTimeDomainData(dataArray),
        FREQUENCY: (dataArray) => analyser.getFloatFrequencyData(dataArray)
    });

    var drawFrame = selectForAnimationMode(animationMode, {
        TIME: drawAnimationFrameForTimeDomain,
        FREQUENCY: drawAnimationFrameForFrequencyDomain
    });

    var canvasContext = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;

    canvasContext.clearRect(0, 0, width, height);

    var previousTimestamp = null;
    var frameCounter = 0;
    var requestId;

    function draw(timestamp) {
        if (_STOP_ANIMATION) {
            cancelAnimationFrame(requestId);
            _ANIMATION_IS_RUNNING = false;
            _STOP_ANIMATION = false;

            var endTime = performance.now();
            console.log('Average frame duration: ' + (endTime - startTime) / frameCounter + ' ms');
            console.log('Sound duration analysed per frame: ' + getSampleSetDuration(fftSize) * 1e3 + ' ms');
            return;
        }
        if (timestamp !== previousTimestamp) {
            _ANIMATION_IS_RUNNING = true;
            frameCounter++;

            fetchData(dataArray);
            drawFrame(canvasContext, width, height, dataArray);

            previousTimestamp = timestamp;
        }
        requestId = requestAnimationFrame(draw);
    }

    var startTime = performance.now();
    requestId = requestAnimationFrame(draw);
}

function selectForAnimationMode(animationMode, actions) {
    switch (animationMode) {
        case ANIMATION_MODES.TIME:
            return actions.TIME;
        case ANIMATION_MODES.FREQUENCY:
            return actions.FREQUENCY;
        default:
            alert('Invalid animation mode: ' + animationMode);
            break;
    }
}

function createDataArrayForTimeDomain(analyser, fftSize) {
    analyser.fftSize = fftSize;
    var dataArray = new Float32Array(analyser.fftSize);
    return dataArray;
}

function createDataArrayForFrequencyDomain(analyser, fftSize) {
    analyser.fftSize = fftSize;
    var dataArray = new Float32Array(analyser.frequencyBinCount);
    return dataArray;
}

function drawAnimationFrameForTimeDomain(canvasContext, width, height, dataArray) {
    clearCanvasTimeDomain(canvasContext, width, height);

    canvasContext.lineWidth = CANVAS_TIME_LINEWIDTH;
    canvasContext.strokeStyle = CANVAS_TIME_FOREGROUND;
    canvasContext.beginPath();

    var sliceWidth = width / dataArray.length;
    var x = 0;
    var y;
    for (var i = 0; i < dataArray.length; i++) {
        y = height / 2 + dataArray[i] * height * CANVAS_TIME_SAMPLE_SCALE;

        if (i === 0) {
            canvasContext.moveTo(x, y);
        } else {
            canvasContext.lineTo(x, y);
        }
        x += sliceWidth;
    }
    canvasContext.lineTo(width, height / 2);
    canvasContext.stroke();
}

function drawAnimationFrameForFrequencyDomain(canvasContext, width, height, dataArray) {
    clearCanvasFrequencyDomain(canvasContext, width, height);

    canvasContext.fillStyle = CANVAS_FREQUENCY_FOREGROUND;

    var barWidth = width / dataArray.length;
    var barHeight;
    var x = 0;
    for (var i = 0; i < dataArray.length; i++) {
        barHeight = (dataArray[i] + CANVAS_FREQUENCY_SAMPLE_OFFSET) * height * CANVAS_FREQUENCY_SAMPLE_SCALE;
        canvasContext.fillRect(x, height - barHeight, barWidth, barHeight);
        x += barWidth;
    }
}

function clearCanvasTimeDomain(canvasContext, width, height) {
    canvasContext.fillStyle = CANVAS_TIME_BACKGROUND;
    canvasContext.fillRect(0, 0, width, height);
}

function clearCanvasFrequencyDomain(canvasContext, width, height) {
    canvasContext.fillStyle = CANVAS_FREQUENCY_BACKGROUND;
    canvasContext.fillRect(0, 0, width, height);
}

function stopAudioAnimation() {
    if (_ANIMATION_IS_RUNNING) {
        _STOP_ANIMATION = true;
    }
}
