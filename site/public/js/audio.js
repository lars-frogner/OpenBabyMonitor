const MODE_CONTENT_AUDIO_ID = 'mode_content_audio';
const AUDIO_PLAYER_PARENT_ID = 'audiostream_player_box';
const AUDIO_CANVAS_PARENT_ID = 'audiostream_canvas_box';
const AUDIO_VISUALIZATION_MODE_PARENT_ID = 'audiostream_visualization_mode_box';
const AUDIO_FFTSIZE_PARENT_ID = 'audiostream_fftsize_range_box';
const AUDIO_PLAYER_ID = 'audiostream_audio';
const AUDIO_CANVAS_ID = 'audiostream_canvas';

const CANVAS_ASPECT_RATIO = 1.0;
const CANVAS_MAX_WIDTH = 600;
const CANVAS_TIME_BACKGROUND = 'rgb(255, 255, 255)';
const CANVAS_TIME_FOREGROUND = 'rgb(0, 0, 0)';
const CANVAS_TIME_LINEWIDTH = 2;
const CANVAS_TIME_SAMPLE_SCALE = 100;
const CANVAS_FREQUENCY_BACKGROUND = 'rgb(255, 255, 255)';
const CANVAS_FREQUENCY_FOREGROUND = 'rgb(0, 0, 0)';
const CANVAS_FREQUENCY_SAMPLE_OFFSET = 140;
const CANVAS_FREQUENCY_SAMPLE_SCALE = 0.005;

const VISUALIZATION_MODES = { TIME: 'time', FREQUENCY: 'frequency' };

const SAMPLING_RATE = 44100; // [Hz]
const CHANNELS = 1;

var _VISUALIZATION_MODE = null;
var _FFTSIZE_POWER = 11;

var _ANALYSER = null;
var _AUDIO_CONTEXT = null;

var _ANIMATION_IS_RUNNING = false;
var _ANIMATION_REQUEST_ID = null;

$(function () {
    if (INITIAL_MODE == AUDIOSTREAM_MODE) {
        enableAudioStreamPlayer();
    }
});

function getAudioVisualizationMode() {
    return _VISUALIZATION_MODE;
}

function getAudioPlayerElement() {
    return document.getElementById(AUDIO_PLAYER_ID);
}

function getAudioCanvasElement() {
    return document.getElementById(AUDIO_CANVAS_ID);
}

function getAudioContext() {
    return _AUDIO_CONTEXT;
}

function getAudioAnalyser() {
    return _ANALYSER;
}

function getFFTSizePower() {
    return _FFTSIZE_POWER;
}

function getFFTSize() {
    return 2 ** _FFTSIZE_POWER;
}

function setFFTSizePower(fftSize_power) {
    _FFTSIZE_POWER = fftSize_power;
}

function enableAudioStreamPlayer() {
    createAudioPlayerElement();
    if (getAudioVisualizationMode() !== null) {
        $('#' + AUDIO_FFTSIZE_PARENT_ID).show();
        setupAudioVisualization();
    }
    $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).show();
}

function disableAudioStreamPlayer() {
    $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).hide();
    if (getAudioVisualizationMode() !== null) {
        $('#' + AUDIO_FFTSIZE_PARENT_ID).hide();
        teardownAudioVisualization();
    }
    removeAudioPlayerElement();
}

function switchAudioVisualizationModeTo(newVisualizationMode) {
    var oldVisualizationMode = getAudioVisualizationMode();

    if (newVisualizationMode == oldVisualizationMode) {
        return;
    }

    var animationWasDisabled = oldVisualizationMode === null;
    var player = getAudioPlayerElement();

    _VISUALIZATION_MODE = newVisualizationMode;

    if (newVisualizationMode === null) {
        $('#' + AUDIO_FFTSIZE_PARENT_ID).hide();
        teardownAudioVisualization();
    } else {
        if (animationWasDisabled) {
            $('#' + AUDIO_FFTSIZE_PARENT_ID).show();
            setupAudioVisualization();
            if (!player.paused) {
                startAudioAnimation();
            }
        } else if (!player.paused) {
            restartAudioAnimation();
        }
        if (player.paused) {
            clearCanvas(newVisualizationMode);
        }
    }
}

function switchFFTSizePowerTo(fftSizePower) {
    if (fftSizePower == getFFTSizePower()) {
        return;
    }
    setFFTSizePower(fftSizePower);
    var visualizationMode = getAudioVisualizationMode();
    if (visualizationMode !== null) {
        if (getAudioPlayerElement().paused) {
            clearCanvas(visualizationMode);
        } else {
            restartAudioAnimation();
        }
    }
}

function setupAudioVisualization() {
    createAudioCanvasElement();
    _AUDIO_CONTEXT = createAudioContext();
    _ANALYSER = createAudioStreamAnalyser(_AUDIO_CONTEXT, getAudioPlayerElement());
    enableAudioAnimation();
}

function teardownAudioVisualization() {
    disableAudioAnimation();
    _AUDIO_CONTEXT.close();
    _AUDIO_CONTEXT = null;
    _ANALYSER = null;
    removeAudioCanvasElement();
}

function createAudioPlayerElement() {
    var src = AUDIO_SRC + '?salt=' + new Date().getTime();
    var audio = $('<audio></audio>')
        .prop({ id: AUDIO_PLAYER_ID, controls: true, autoplay: true, crossOrigin: 'anonymous' }).css('max-width', CANVAS_MAX_WIDTH + 'px')
        .append($('<source></source>').prop({ src: src, type: 'audio/mpeg' }))
        .append('Denne funksjonaliteten er ikke tilgjengelig i din nettleser.');
    $('#' + AUDIO_PLAYER_PARENT_ID).append(audio);
}

function removeAudioPlayerElement() {
    var player = getAudioPlayerElement();
    if (player) {
        player.remove();
    }
}

function createAudioCanvasElement() {
    var canvas = $('<canvas></canvas>').prop('id', AUDIO_CANVAS_ID).addClass('px-0');
    $('#' + AUDIO_CANVAS_PARENT_ID).append(canvas);
    window.addEventListener('resize', resizeAudioCanvas);
    resizeAudioCanvas();
}

function removeAudioCanvasElement() {
    var canvas = getAudioCanvasElement();
    if (canvas) {
        window.removeEventListener('resize', resizeAudioCanvas);
        canvas.remove();
    }
}

function resizeAudioCanvas() {
    var canvas = $('#' + AUDIO_CANVAS_ID);
    var parent = $('#main');
    var player = $('#' + AUDIO_PLAYER_ID);
    var width = player.width();
    var max_height = parent.height() - player.height() - $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).height() - $('#' + AUDIO_FFTSIZE_PARENT_ID).height();
    var target_height = width / CANVAS_ASPECT_RATIO;
    var height = Math.min(max_height, target_height);
    canvas.prop({ width: width, height: height }).css({ width: width + 'px', height: height + 'px' });
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

function enableAudioAnimation() {
    var player = getAudioPlayerElement();
    player.addEventListener('play', startAudioAnimation);
    player.addEventListener('pause', stopAudioAnimation);
}

function disableAudioAnimation() {
    var player = getAudioPlayerElement();
    player.removeEventListener('play', startAudioAnimation);
    player.removeEventListener('pause', stopAudioAnimation);
}

function startAudioAnimation() {
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
    [dataArray, fetchData, drawFrame] = selectDrawingObjects();

    var canvas = getAudioCanvasElement();
    var canvasContext = canvas.getContext('2d');

    var previousTimestamp = null;

    function draw(timestamp) {
        if (timestamp !== previousTimestamp) {
            _ANIMATION_IS_RUNNING = true;

            fetchData(dataArray);
            drawFrame(canvasContext, canvas.width, canvas.height, dataArray);

            previousTimestamp = timestamp;
        }
        _ANIMATION_REQUEST_ID = requestAnimationFrame(draw);
    }

    _ANIMATION_REQUEST_ID = requestAnimationFrame(draw);
}

function selectDrawingObjects() {
    var analyser = getAudioAnalyser();
    var visualizationMode = getAudioVisualizationMode();
    var fftSize = getFFTSize();

    var dataArray = selectForVisualizationMode(visualizationMode, {
        TIME: createDataArrayForTimeDomain,
        FREQUENCY: createDataArrayForFrequencyDomain
    })(analyser, fftSize);

    var fetchData = selectForVisualizationMode(visualizationMode, {
        TIME: (dataArray) => analyser.getFloatTimeDomainData(dataArray),
        FREQUENCY: (dataArray) => analyser.getFloatFrequencyData(dataArray)
    });

    var drawFrame = selectForVisualizationMode(visualizationMode, {
        TIME: drawAnimationFrameForTimeDomain,
        FREQUENCY: drawAnimationFrameForFrequencyDomain
    });

    return [dataArray, fetchData, drawFrame];
}

function stopAudioAnimation() {
    if (_ANIMATION_IS_RUNNING) {
        cancelAnimationFrame(_ANIMATION_REQUEST_ID);
        _ANIMATION_IS_RUNNING = false;
    }
}

function restartAudioAnimation() {
    stopAudioAnimation();
    startAudioAnimation();
}

function selectForVisualizationMode(visualizationMode, actions) {
    switch (visualizationMode) {
        case VISUALIZATION_MODES.TIME:
            return actions.TIME;
        case VISUALIZATION_MODES.FREQUENCY:
            return actions.FREQUENCY;
        default:
            alert('Invalid animation mode: ' + visualizationMode);
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

function getFrequencyRange() {
    return (0, SAMPLING_RATE / 2);
}

function getTimeRange(offset, fftSize) {
    return (offset, offset + getSampleSetDuration(fftSize));
}

function getSampleSetDuration(fftSize) {
    return fftSize / SAMPLING_RATE;
}

function clearCanvasTimeDomain(canvasContext, width, height) {
    canvasContext.fillStyle = CANVAS_TIME_BACKGROUND;
    canvasContext.fillRect(0, 0, width, height);
}

function clearCanvasFrequencyDomain(canvasContext, width, height) {
    canvasContext.fillStyle = CANVAS_FREQUENCY_BACKGROUND;
    canvasContext.fillRect(0, 0, width, height);
}

function clearCanvas(visualizationMode) {
    var canvas = getAudioCanvasElement();
    var canvasContext = canvas.getContext('2d');
    selectForVisualizationMode(visualizationMode, {
        TIME: () => clearCanvasTimeDomain(canvasContext, canvas.width, canvas.height),
        FREQUENCY: () => clearCanvasTimeDomain(canvasContext, canvas.width, canvas.height)
    })();
}
