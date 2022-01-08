const MODE_CONTENT_AUDIO_ID = 'mode_content_audio';
const AUDIO_ICON_ID = 'audiostream_icon';
const AUDIO_PLAYER_PARENT_ID = 'audiostream_player_box';
const AUDIO_CANVAS_PARENT_ID = 'audiostream_canvas_box';
const AUDIO_VISUALIZATION_MODE_PARENT_ID = 'audiostream_visualization_mode_box';
const AUDIO_FFTSIZE_PARENT_ID = 'audiostream_fftsize_range_box';
const AUDIO_FFTSIZE_RANGE_ID = 'audiostream_fftsize_range';
const AUDIO_PLAYER_ID = 'audiostream_audio';
const AUDIO_CANVAS_ID = 'audiostream_canvas';
const AUDIO_ERROR_ID = 'mode_content_audio_error';

let AUDIO_STREAM_SRC = 'streaming/audiostream/index.m3u8';

const CANVAS_ASPECT_RATIO = 1.7;
const CANVAS_MAX_WIDTH = 600;

const CANVAS_TIME_FOREGROUND = FOREGROUND_COLOR;
const CANVAS_TIME_LINEWIDTH = 2;
const CANVAS_TIME_SAMPLE_OFFSET = -127.5;
const CANVAS_TIME_SAMPLE_SCALE = 1 / 64;

const CANVAS_FREQUENCY_FOREGROUND = FOREGROUND_COLOR;
const CANVAS_FREQUENCY_GRID_COLOR = 'rgb(130, 130, 130)';
const CANVAS_FREQUENCY_GRID_DASH = [10, 10];
const CANVAS_FREQUENCY_GRID_LINE_WIDTH = 1;
const CANVAS_FREQUENCY_N_GRID_LINES = 10;
const CANVAS_FREQUENCY_FONT = 'Helvetica';
const CANVAS_FREQUENCY_FONT_SIZE = 14;
const CANVAS_FREQUENCY_FONT_OFFSET_X = 4;
const CANVAS_FREQUENCY_FONT_OFFSET_Y = 25;
const CANVAS_FREQUENCY_SAMPLE_OFFSET = 0;
const CANVAS_FREQUENCY_SAMPLE_SCALE = (1 / 512);

const VISUALIZATION_MODES = { TIME: 'time', FREQUENCY: 'frequency' };

const CHANNELS = 1;

var _AUDIOSTREAM_CONTEXT = null;
var _CURRENT_VISUALIZATION_MODE = null;

$(function () {
    if (INITIAL_MODE == AUDIOSTREAM_MODE) {
        enableAudioStreamPlayer();
    }
});

function enableAudioStreamPlayer() {
    _AUDIOSTREAM_CONTEXT = new AudiostreamContext();
    switchAudioVisualizationModeTo(_CURRENT_VISUALIZATION_MODE);
}

function disableAudioStreamPlayer() {
    if (_AUDIOSTREAM_CONTEXT != null) {
        _AUDIOSTREAM_CONTEXT.close();
        _AUDIOSTREAM_CONTEXT = null;
    }
}

function switchAudioVisualizationModeTo(mode) {
    _AUDIOSTREAM_CONTEXT.visualizer = mode;
}

function switchFFTSizePowerTo(fftSizePower) {
    _AUDIOSTREAM_CONTEXT.fftSizePower = fftSizePower;
}

class AudiostreamContext {
    #context;
    #hls;
    #source;
    #highpassFilter;
    #lowpassFilter;
    #gain;
    #analyser;
    #fftSizePower;
    #visualizer;
    #analyserSamples;
    #analyserSamplesOffset;
    #rebuildAnalyserSamplesArray = true;

    constructor() {
        [this.playerObject, this.#hls] = AudiostreamContext.createPlayer();

        this.#context = new (window.AudioContext || window.webkitAudioContext)({
            latencyHint: 'balanced',
            sampleRate: SETTING_SAMPLING_RATE,
        });
        this.#source = this.#context.createMediaElementSource(this.player);
        this.#highpassFilter = this.#context.createBiquadFilter();
        this.#lowpassFilter = this.#context.createBiquadFilter();
        this.#gain = this.#context.createGain();
        this.#analyser = this.#context.createAnalyser();
        this.#source.connect(this.#highpassFilter);
        this.#highpassFilter.connect(this.#lowpassFilter);
        this.#lowpassFilter.connect(this.#gain);
        this.#gain.connect(this.#analyser);
        this.#analyser.connect(this.#context.destination);

        setupBandpassFilters(this.#highpassFilter, this.#lowpassFilter);
        setupGain(this.#gain);

        this.#fftSizePower = parseInt($('#' + AUDIO_FFTSIZE_RANGE_ID).val());

        $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).show();
    }

    get player() {
        return this.playerObject.get()[0];
    }

    get fftSizePower() {
        return this.#fftSizePower;
    }

    get fftSize() {
        return 2 ** this.fftSizePower;
    }

    get nFrequencies() {
        return this.fftSize / 2;
    }

    get visualizer() {
        if (this.#visualizer == null) {
            return null;
        } else {
            return this.#visualizer.mode;
        }
    }

    get analyserSamplesOffset() {
        return this.#analyserSamplesOffset;
    }

    static get maxFrequency() {
        return SETTING_SAMPLING_RATE / 2;
    }

    get sampleSetDuration() {
        return this.fftSize / SETTING_SAMPLING_RATE;
    }

    get maxToMinFrequencyRatio() {
        return (AudiostreamContext.computeBufferLength(this.nFrequencies) - 1) / AudiostreamContext.computeBufferOffset(this.nFrequencies);
    }

    set visualizer(newVisualizationMode) {
        _CURRENT_VISUALIZATION_MODE = newVisualizationMode;
        if (this.#visualizer == null) {
            if (newVisualizationMode == null) {
                return;
            } else {
                this.#rebuildAnalyserSamplesArray = true;
                this.#visualizer = new AudioVisualizer(this, newVisualizationMode);
                if (!this.player.paused) {
                    this.#visualizer.startAnimation();
                }
            }
        } else if (newVisualizationMode == null) {
            this.#visualizer.destroy();
            this.#visualizer = null;
        } else {
            this.#rebuildAnalyserSamplesArray = true;
            this.#visualizer.mode = newVisualizationMode;
        }
    }

    set fftSizePower(newFFTSizePower) {
        if (newFFTSizePower == this.fftSizePower) {
            return;
        }

        this.#fftSizePower = newFFTSizePower;

        if (this.#visualizer != null) {
            if (this.player.paused) {
                this.#visualizer.clearCanvas();
            } else {
                this.#visualizer.restartAnimation();
            }
        }
    }

    createSampler() {
        if (this.#visualizer == null) {
            return;
        }
        if (this.#analyser.fftSize != this.fftSize) {
            this.#analyser.fftSize = this.fftSize;
            this.#rebuildAnalyserSamplesArray = true;
        }

        this.maintainAnalyserSampleArray();

        switch (this.#visualizer.mode) {
            case VISUALIZATION_MODES.TIME:
                return function () {
                    this.#analyser.getByteTimeDomainData(this.#analyserSamples);
                    return this.#analyserSamples;
                }.bind(this);
            case VISUALIZATION_MODES.FREQUENCY:
                return function () {
                    this.#analyser.getByteFrequencyData(this.#analyserSamples);
                    return this.#analyserSamples;
                }.bind(this);
            default:
                triggerErrorEvent(new Error('createSampler called when mode is not valid'));
                break;
        }
    }

    close() {
        $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).hide();

        if (this.#visualizer != null) {
            this.#visualizer.destroy();
        }
        this.#context.close();
        if (this.#hls) {
            this.#hls.destroy();
        }
        this.player.remove();
    }

    maintainAnalyserSampleArray() {
        if (!this.#rebuildAnalyserSamplesArray || this.#visualizer == null) {
            return;
        }
        switch (this.#visualizer.mode) {
            case VISUALIZATION_MODES.TIME:
                this.#analyserSamples = new Uint8Array(this.#analyser.fftSize);
                this.#analyserSamplesOffset = 0;
                this.#rebuildAnalyserSamplesArray = false;
                break;
            case VISUALIZATION_MODES.FREQUENCY:
                const length = AudiostreamContext.computeBufferLength(this.nFrequencies);
                this.#analyserSamples = new Uint8Array(length);
                this.#analyserSamplesOffset = AudiostreamContext.computeBufferOffset(this.nFrequencies);
                this.#rebuildAnalyserSamplesArray = false;
                break;
            default:
                break;
        }
    }

    static computeBufferOffset(nFrequencies) {
        return Math.max(1, Math.floor(nFrequencies * SETTING_MIN_FREQUENCY / AudiostreamContext.maxFrequency));
    }

    static computeBufferLength(nFrequencies) {
        return Math.min(nFrequencies, Math.ceil(nFrequencies * SETTING_MAX_FREQUENCY / AudiostreamContext.maxFrequency));
    }

    static createPlayer() {
        if (document.getElementById(AUDIO_PLAYER_ID) != null) {
            triggerErrorEvent(new Error('AudiostreamContext constructor called when player already exists'));
        }
        var playerObject = $('<audio></audio>')
            .prop({ id: AUDIO_PLAYER_ID, controls: true, autoplay: true }).css('max-width', CANVAS_MAX_WIDTH + 'px')
            .append('This browser does not support HTML5 audio.');
        $('#' + AUDIO_PLAYER_PARENT_ID).append(playerObject);

        var hls = null;
        if (Hls.isSupported()) {
            hls = new Hls({ backBufferLength: 0 });
            hls.attachMedia(playerObject.get()[0]);

            hls.on(Hls.Events.MEDIA_ATTACHED, () => {
                hls.loadSource(AUDIO_STREAM_SRC);
            });

            hls.on(Hls.Events.MANIFEST_LOADED, () => {
                $('#' + AUDIO_ERROR_ID).parent().hide();
            });

            hls.on(Hls.Events.ERROR, function (event, data) {
                if (data.fatal) {
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            $('#' + AUDIO_ERROR_ID).html('Could not load the audio stream due to a network error');
                            $('#' + AUDIO_ERROR_ID).parent().show();
                            hls.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            $('#' + AUDIO_ERROR_ID).html('Could not load the audio stream due to a media error');
                            $('#' + AUDIO_ERROR_ID).parent().show();
                            hls.recoverMediaError();
                            break;
                        default:
                            hls.destroy();
                            $('#' + AUDIO_ERROR_ID).html('Could not load the audio stream');
                            $('#' + AUDIO_ERROR_ID).parent().show();
                            break;
                    }
                }
            });
        } else {
            $('#' + AUDIO_ERROR_ID).html('This browser does not support HLS streaming');
            $('#' + AUDIO_ERROR_ID).parent().show();
        }

        return [playerObject, hls];
    }
}

class AudioVisualizer {
    #audioContext;
    #canvasObject;
    #animationRequestId;
    #animationIsRunning = false;
    #drawFrame;
    #clearCanvas;
    #resizeCanvasBound;
    #startAnimationBound;
    #stopAnimationBound;
    #drawBound;
    #mode;

    constructor(audioContext, mode) {
        this.shrinkAudioIcon();
        $('#' + AUDIO_FFTSIZE_PARENT_ID).show();

        this.#audioContext = audioContext;

        this.#canvasObject = AudioVisualizer.createCanvas();
        this.#resizeCanvasBound = this.resizeCanvas.bind(this);
        addEventListener('resize', this.#resizeCanvasBound);
        this.resizeCanvas();

        this.#startAnimationBound = this.startAnimation.bind(this);
        this.#stopAnimationBound = this.stopAnimation.bind(this);
        this.#audioContext.player.addEventListener('play', this.#startAnimationBound);
        this.#audioContext.player.addEventListener('pause', this.#stopAnimationBound);

        this.setMode(mode);
        if (this.#audioContext.player.paused) {
            this.clearCanvas();
        }
    }

    get mode() {
        return this.#mode;
    }

    get animationIsRunning() {
        return this.#animationIsRunning;
    }

    set mode(newMode) {
        if (newMode == this.mode) {
            return;
        }
        this.setMode(newMode);
        if (this.#audioContext.player.paused) {
            this.clearCanvas();
        } else {
            this.restartAnimation();
        }
    }

    startAnimation() {
        /*
        Sample rate: 44100 Hz
        Channel: 1
        Target animation framerate: ~30 FPS => 30 ms per frame

        getFloatTimeDomainData with fftSize=1024 gives 1024 samples covering a duration of 1024/(44100 Hz) = 23 ms

        For coherence, the displayed set of samples should cover a duration longer than the frame duration (~30 ms),
        so that some of the waveform from the previous frame is still visible in the next frame.

        But the fftSize should not be so large that the frame takes longer than 30 ms to render, which would lead to
        stuttering.

        getFloatFrequencyData with fftSize=1024 gives the strength in dB of 512 frequencies from 0 Hz to (sample rate)/2.

        The Fourier transform will be performed over a set of samples spanning 1024/(44100 Hz) = 23 ms.

        So higher fftSize gives higher frequency resolution and is taken over a larger duration.
        */
        var sampler = this.#audioContext.createSampler();
        var canvas = this.canvas;
        var canvasContext = canvas.getContext('2d');

        var previousTimestamp = null;

        function draw(timestamp) {
            if (timestamp !== previousTimestamp) {
                this.#animationIsRunning = true;
                var samples = sampler();
                this.#drawFrame(canvasContext, canvas.width, canvas.height, samples, this.#audioContext.analyserSamplesOffset);
                previousTimestamp = timestamp;
            }
            this.#animationRequestId = requestAnimationFrame(this.#drawBound);
        }
        this.#drawBound = draw.bind(this);
        this.#animationRequestId = requestAnimationFrame(this.#drawBound);
    }

    stopAnimation() {
        if (this.animationIsRunning) {
            cancelAnimationFrame(this.#animationRequestId);
            this.#animationRequestId = null;
            this.#animationIsRunning = false;
        }
    }

    restartAnimation() {
        this.stopAnimation();
        this.startAnimation();
    }

    clearCanvas() {
        var canvas = this.canvas;
        var canvasContext = canvas.getContext('2d');
        this.#clearCanvas(canvasContext, canvas.width, canvas.height);
    }

    destroy() {
        $('#' + AUDIO_FFTSIZE_PARENT_ID).hide();
        this.unshrinkAudioIcon();
        this.stopAnimation();
        this.#audioContext.player.removeEventListener('play', this.#startAnimationBound);
        this.#audioContext.player.removeEventListener('pause', this.#stopAnimationBound);
        removeEventListener('resize', this.#resizeCanvasBound);
        this.canvas.remove();
    }

    get canvas() {
        return this.#canvasObject.get()[0];
    }
    static get MIN_PITCH() { return AudioVisualizer.hertzToMels(SETTING_MIN_FREQUENCY); }
    static get MAX_PITCH() { return AudioVisualizer.hertzToMels(SETTING_MAX_FREQUENCY); }

    setMode(newMode) {
        switch (newMode) {
            case VISUALIZATION_MODES.TIME:
                this.#drawFrame = AudioVisualizer.drawFrameTimeDomain;
                this.#clearCanvas = AudioVisualizer.clearCanvasTimeDomain;
                break;
            case VISUALIZATION_MODES.FREQUENCY:
                this.#drawFrame = AudioVisualizer.drawFrameFrequencyDomain;
                this.#clearCanvas = AudioVisualizer.clearCanvasFrequencyDomain;
                break;
            default:
                triggerErrorEvent(new Error('Mode is not valid: ' + newMode));
                break;
        }
        this.#mode = newMode;
    }

    resizeCanvas() {
        var parent = $('#main');
        var width = this.#audioContext.playerObject.width();
        var maxHeight = parent.height() - this.#audioContext.playerObject.height() - $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).height() - $('#' + AUDIO_FFTSIZE_PARENT_ID).height();
        var targetHeight = width / CANVAS_ASPECT_RATIO;
        var height = Math.min(maxHeight, targetHeight);
        this.#canvasObject.prop({ width: width, height: height }).css({ width: width + 'px', height: height + 'px' });
    }

    shrinkAudioIcon() {
        $('#' + AUDIO_ICON_ID).css({ width: '5vh', height: '5vh' });
        $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).removeClass('mt-5');
        $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).addClass('mt-4');
    }

    unshrinkAudioIcon() {
        $('#' + AUDIO_ICON_ID).css({ width: '15vh', height: '15vh' });
        $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).removeClass('mt-4');
        $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).addClass('mt-5');
    }

    static createCanvas() {
        if (document.getElementById(AUDIO_CANVAS_ID) != null) {
            triggerErrorEvent(new Error('AudioVisualizer constructor called when audio visualization canvas already exists'));
        }
        var canvasObject = $('<canvas></canvas>').prop('id', AUDIO_CANVAS_ID).addClass('px-0');
        $('#' + AUDIO_CANVAS_PARENT_ID).append(canvasObject);
        return canvasObject;
    }

    static drawFrameTimeDomain(canvasContext, width, height, samples) {
        AudioVisualizer.clearCanvasTimeDomain(canvasContext, width, height);

        canvasContext.lineWidth = CANVAS_TIME_LINEWIDTH;
        canvasContext.strokeStyle = CANVAS_TIME_FOREGROUND;
        canvasContext.setLineDash([]);
        canvasContext.beginPath();

        var sliceWidth = width / samples.length;
        var x = 0;
        var y;
        for (var i = 0; i < samples.length; i++) {
            y = height / 2 + (samples[i] + CANVAS_TIME_SAMPLE_OFFSET) * height * CANVAS_TIME_SAMPLE_SCALE;

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

    static hertzToMels(frequency) {
        return 2595 * Math.log10(1 + frequency / 700);
    }

    static melsToHertz(pitch) {
        return 700 * (10 ** (pitch / 2595) - 1);
    }

    static drawFrameFrequencyDomain(canvasContext, width, height, samples, start_idx) {
        AudioVisualizer.clearCanvasFrequencyDomain(canvasContext, width, height);

        const barWidth = width / (samples.length - start_idx);
        const actualBarWidth = barWidth * 0.95;
        var x = 0;
        var barHeight;

        const end_idx = samples.length - 1;
        var idx = start_idx;
        var idx_lower, idx_upper, fraction, sample_value;

        const frequencyToIdx = (frequency) => frequency * (samples.length - 1) / SETTING_MAX_FREQUENCY;
        const xToPitch = (x) => AudioVisualizer.MIN_PITCH + (x / width) * (AudioVisualizer.MAX_PITCH - AudioVisualizer.MIN_PITCH);
        const xToIdx = (x) => frequencyToIdx(AudioVisualizer.melsToHertz(xToPitch(x)));

        for (var i = start_idx; i < samples.length; i++) {
            idx = xToIdx(x);
            idx_lower = Math.floor(idx);
            idx_upper = Math.ceil(idx);
            if (idx_lower == idx_upper || idx_upper > end_idx) {
                sample_value = samples[idx_lower];
            } else {
                fraction = idx - idx_lower;
                sample_value = (1 - fraction) * samples[idx_lower] + fraction * samples[idx_upper];
            }
            barHeight = (sample_value + CANVAS_FREQUENCY_SAMPLE_OFFSET) * height * CANVAS_FREQUENCY_SAMPLE_SCALE;
            canvasContext.fillRect(x, height - barHeight, actualBarWidth, barHeight);
            x += barWidth;
        }
    }

    static clearCanvasTimeDomain(canvasContext, width, height) {
        canvasContext.clearRect(0, 0, width, height);
    }

    static clearCanvasFrequencyDomain(canvasContext, width, height) {
        canvasContext.clearRect(0, 0, width, height);

        const idxToFrequency = (idx) => SETTING_MIN_FREQUENCY + idx * (SETTING_MAX_FREQUENCY - SETTING_MIN_FREQUENCY) / (CANVAS_FREQUENCY_N_GRID_LINES - 1);
        const pitchToX = (m) => width * (m - AudioVisualizer.MIN_PITCH) / (AudioVisualizer.MAX_PITCH - AudioVisualizer.MIN_PITCH);
        const idxToX = (idx) => pitchToX(AudioVisualizer.hertzToMels(idxToFrequency(idx)));
        var x;
        canvasContext.lineWidth = CANVAS_FREQUENCY_GRID_LINE_WIDTH;
        canvasContext.strokeStyle = CANVAS_FREQUENCY_GRID_COLOR;
        canvasContext.setLineDash(CANVAS_FREQUENCY_GRID_DASH);
        canvasContext.beginPath();
        for (var i = 0; i < CANVAS_FREQUENCY_N_GRID_LINES; i++) {
            x = idxToX(i);
            if (i == 0) {
                x += CANVAS_FREQUENCY_GRID_LINE_WIDTH;
            } else if (i == CANVAS_FREQUENCY_N_GRID_LINES - 1) {
                x -= CANVAS_FREQUENCY_GRID_LINE_WIDTH;
            }
            canvasContext.moveTo(x, 0);
            canvasContext.lineTo(x, height);
        }
        canvasContext.stroke();

        canvasContext.fillStyle = CANVAS_FREQUENCY_FOREGROUND;
        canvasContext.font = CANVAS_FREQUENCY_FONT_SIZE + 'px ' + CANVAS_FREQUENCY_FONT;

        var min_label = SETTING_MIN_FREQUENCY + ' Hz';
        var max_label = SETTING_MAX_FREQUENCY + ' Hz';
        canvasContext.fillText(min_label, CANVAS_FREQUENCY_FONT_OFFSET_X, CANVAS_FREQUENCY_FONT_OFFSET_Y);
        canvasContext.fillText(max_label, width - Math.round(0.57 * CANVAS_FREQUENCY_FONT_SIZE * max_label.length) - CANVAS_FREQUENCY_FONT_OFFSET_X, CANVAS_FREQUENCY_FONT_OFFSET_Y);
    }
}
