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

var _AUDIOSTREAM_CONTEXT = null;

$(function () {
    if (INITIAL_MODE == AUDIOSTREAM_MODE) {
        enableAudioStreamPlayer();
    }
});

function enableAudioStreamPlayer() {
    _AUDIOSTREAM_CONTEXT = new AudiostreamContext();
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
    #source;
    #analyser;
    #gain;
    #fftSizePower = 11;
    #visualizer;
    #analyserSamples;
    #rebuildAnalyserSamplesArray = true;

    constructor() {
        this.playerObject = AudiostreamContext.#createPlayer();

        this.#context = new (window.AudioContext || window.webkitAudioContext)();
        this.#source = this.#context.createMediaElementSource(this.player);
        this.#analyser = this.#context.createAnalyser();
        this.#gain = this.#context.createGain();
        this.#source.connect(this.#analyser);
        this.#analyser.connect(this.#context.destination);

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

    get visualizer() {
        if (this.#visualizer == null) {
            return null;
        } else {
            return this.#visualizer.mode;
        }
    }

    static get frequencyRange() {
        return (0, SAMPLING_RATE / 2);
    }

    get sampleSetDuration() {
        return this.fftSize / SAMPLING_RATE;
    }

    timeRange(offset) {
        return (offset, offset + this.sampleSetDuration);
    }

    set visualizer(newVisualizationMode) {
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

        this.#maintainAnalyserSampleArray();

        switch (this.#visualizer.mode) {
            case VISUALIZATION_MODES.TIME:
                return function () {
                    this.#analyser.getFloatTimeDomainData(this.#analyserSamples);
                    return this.#analyserSamples;
                }.bind(this);
            case VISUALIZATION_MODES.FREQUENCY:
                return function () {
                    this.#analyser.getFloatFrequencyData(this.#analyserSamples);
                    return this.#analyserSamples;
                }.bind(this);
            default:
                alert('createSampler called when mode is not valid');
                break;
        }
    }

    close() {
        $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).hide();

        if (this.#visualizer != null) {
            this.#visualizer.destroy();
        }
        this.#context.close();
        this.player.remove();
    }

    #maintainAnalyserSampleArray() {
        if (!this.#rebuildAnalyserSamplesArray || this.#visualizer == null) {
            return;
        }
        switch (this.#visualizer.mode) {
            case VISUALIZATION_MODES.TIME:
                this.#analyserSamples = new Float32Array(this.#analyser.fftSize);
                this.#rebuildAnalyserSamplesArray = false;
                break;
            case VISUALIZATION_MODES.FREQUENCY:
                this.#analyserSamples = new Float32Array(this.#analyser.frequencyBinCount);
                this.#rebuildAnalyserSamplesArray = false;
                break;
            default:
                break;
        }
    }

    static #createPlayer() {
        if (document.getElementById(AUDIO_PLAYER_ID) != null) {
            alert('AudiostreamContext constructor called when player already exists');
        }
        var src = AUDIO_SRC + '?salt=' + new Date().getTime();
        var playerObject = $('<audio></audio>')
            .prop({ id: AUDIO_PLAYER_ID, controls: true, autoplay: true, crossOrigin: 'anonymous' }).css('max-width', CANVAS_MAX_WIDTH + 'px')
            .append($('<source></source>').prop({ src: src, type: 'audio/mpeg' }))
            .append('Denne funksjonaliteten er ikke tilgjengelig i din nettleser.');
        $('#' + AUDIO_PLAYER_PARENT_ID).append(playerObject);
        return playerObject;
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
        $('#' + AUDIO_FFTSIZE_PARENT_ID).show();

        this.#audioContext = audioContext;

        this.#canvasObject = AudioVisualizer.#createCanvas();
        this.#resizeCanvasBound = this.#resizeCanvas.bind(this);
        addEventListener('resize', this.#resizeCanvasBound);
        this.#resizeCanvas();

        this.#startAnimationBound = this.startAnimation.bind(this);
        this.#stopAnimationBound = this.stopAnimation.bind(this);
        this.#audioContext.player.addEventListener('play', this.#startAnimationBound);
        this.#audioContext.player.addEventListener('pause', this.#stopAnimationBound);

        this.#setMode(mode);
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
        this.#setMode(newMode);
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
        var canvas = this.#canvas;
        var canvasContext = canvas.getContext('2d');

        var previousTimestamp = null;

        function draw(timestamp) {
            if (timestamp !== previousTimestamp) {
                this.#animationIsRunning = true;
                this.#drawFrame(canvasContext, canvas.width, canvas.height, sampler());
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
        var canvas = this.#canvas;
        var canvasContext = canvas.getContext('2d');
        this.#clearCanvas(canvasContext, canvas.width, canvas.height);
    }

    destroy() {
        $('#' + AUDIO_FFTSIZE_PARENT_ID).hide();
        this.stopAnimation();
        this.#audioContext.player.removeEventListener('play', this.#startAnimationBound);
        this.#audioContext.player.removeEventListener('pause', this.#stopAnimationBound);
        removeEventListener('resize', this.#resizeCanvasBound);
        this.#canvas.remove();
    }

    get #canvas() {
        return this.#canvasObject.get()[0];
    }

    #setMode(newMode) {
        switch (newMode) {
            case VISUALIZATION_MODES.TIME:
                this.#drawFrame = AudioVisualizer.#drawFrameTimeDomain;
                this.#clearCanvas = AudioVisualizer.#clearCanvasTimeDomain;
                break;
            case VISUALIZATION_MODES.FREQUENCY:
                this.#drawFrame = AudioVisualizer.#drawFrameFrequencyDomain;
                this.#clearCanvas = AudioVisualizer.#clearCanvasFrequencyDomain;
                break;
            default:
                alert('Mode is not valid: ' + newMode);
                break;
        }
        this.#mode = newMode;
    }

    #resizeCanvas() {
        var parent = $('#main');
        var width = this.#audioContext.playerObject.width();
        var maxHeight = parent.height() - this.#audioContext.playerObject.height() - $('#' + AUDIO_VISUALIZATION_MODE_PARENT_ID).height() - $('#' + AUDIO_FFTSIZE_PARENT_ID).height();
        var targetHeight = width / CANVAS_ASPECT_RATIO;
        var height = Math.min(maxHeight, targetHeight);
        this.#canvasObject.prop({ width: width, height: height }).css({ width: width + 'px', height: height + 'px' });
    }

    static #createCanvas() {
        if (document.getElementById(AUDIO_CANVAS_ID) != null) {
            alert('AudioVisualizer constructor called when audio visualization canvas already exists');
        }
        var canvasObject = $('<canvas></canvas>').prop('id', AUDIO_CANVAS_ID).addClass('px-0');
        $('#' + AUDIO_CANVAS_PARENT_ID).append(canvasObject);
        return canvasObject;
    }

    static #drawFrameTimeDomain(canvasContext, width, height, samples) {
        AudioVisualizer.#clearCanvasTimeDomain(canvasContext, width, height);

        canvasContext.lineWidth = CANVAS_TIME_LINEWIDTH;
        canvasContext.strokeStyle = CANVAS_TIME_FOREGROUND;
        canvasContext.beginPath();

        var sliceWidth = width / samples.length;
        var x = 0;
        var y;
        for (var i = 0; i < samples.length; i++) {
            y = height / 2 + samples[i] * height * CANVAS_TIME_SAMPLE_SCALE;

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

    static #drawFrameFrequencyDomain(canvasContext, width, height, samples) {
        AudioVisualizer.#clearCanvasFrequencyDomain(canvasContext, width, height);

        canvasContext.fillStyle = CANVAS_FREQUENCY_FOREGROUND;

        var barWidth = width / samples.length;
        var barHeight;
        var x = 0;
        for (var i = 0; i < samples.length; i++) {
            barHeight = (samples[i] + CANVAS_FREQUENCY_SAMPLE_OFFSET) * height * CANVAS_FREQUENCY_SAMPLE_SCALE;
            canvasContext.fillRect(x, height - barHeight, barWidth, barHeight);
            x += barWidth;
        }
    }

    static #clearCanvasTimeDomain(canvasContext, width, height) {
        canvasContext.fillStyle = CANVAS_TIME_BACKGROUND;
        canvasContext.fillRect(0, 0, width, height);
    }

    static #clearCanvasFrequencyDomain(canvasContext, width, height) {
        canvasContext.fillStyle = CANVAS_FREQUENCY_BACKGROUND;
        canvasContext.fillRect(0, 0, width, height);
    }
}
