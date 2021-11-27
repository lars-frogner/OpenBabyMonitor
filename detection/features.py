import time
import subprocess
import queue
import numpy as np
import librosa_destilled


def next_power_of_2(x):
    return 1 if x == 0 else 2**(x - 1).bit_length()


def get_striding_windows(arr, window_size, stride=1):
    assert arr.ndim == 1
    shape = (1 + (arr.size - window_size) // stride, window_size)
    strides = (stride * arr.strides[0], arr.strides[0])
    return np.lib.stride_tricks.as_strided(arr, shape=shape, strides=strides)


class AudioFeatureExtractor:
    def __init__(self,
                 dtype=np.float32,
                 sampling_rate=8000,
                 n_mel_bands=64,
                 window_length=256,
                 window_separation=128,
                 min_frequency=125,
                 max_frequency=3600,
                 log_offset=1e-9,
                 feature_window_count=128,
                 feature_overlap_fraction=0.25,
                 low_energy_threshold=0.15,
                 max_low_energy_feature_proportion=0.8,
                 backend='librosa',
                 disable_io=False):
        self.dtype = np.dtype(dtype)
        assert self.dtype.kind == 'f'
        self.sampling_rate = sampling_rate
        self.n_mel_bands = n_mel_bands
        self.window_length = window_length
        self.window_separation = window_separation
        self.min_frequency = min_frequency
        self.max_frequency = max_frequency
        self.log_offset = log_offset
        self.fft_length = next_power_of_2(self.window_length)

        self.feature_window_count = feature_window_count
        self.feature_length = self.compute_waveform_length_for_window_count(
            feature_window_count)
        self.feature_duration = self.sample_num_to_time(self.feature_length)

        self.set_feature_overlap_fraction(feature_overlap_fraction)

        self.low_energy_threshold = low_energy_threshold
        self.max_low_energy_feature_proportion = max_low_energy_feature_proportion
        self.filter_features = self.low_energy_threshold is not None and self.max_low_energy_feature_proportion is not None

        self.select_backend(backend)

        if not disable_io:
            import soundfile
            self.soundfile = soundfile

    def select_backend(self, backend):
        self.backend = backend
        if backend == 'librosa':
            import librosa
            self.librosa = librosa
            self.compute_mel_spectrogram = self.compute_mel_spectrogram_librosa
        elif backend == 'python_speech_features':
            import python_speech_features
            from scipy.signal.windows import hann
            self.python_speech_features = python_speech_features
            self.hann_window = hann
            self.create_mel_filterbank()
            self.compute_mel_spectrogram = self.compute_mel_spectrogram_python_speech_features
        else:
            raise ValueError(f'Invalid backend {backend}')

    def set_feature_overlap_fraction(self, feature_overlap_fraction):
        self.feature_overlap_fraction = feature_overlap_fraction
        self.feature_overlap_length = min(
            int(feature_overlap_fraction * self.feature_length),
            self.feature_length - 1)
        self.feature_stride = self.feature_length - self.feature_overlap_length

    def can_extract_feature(self, waveform):
        return waveform.size >= self.feature_length

    def compute_window_count(self, waveform_length, return_cutoff=False):
        whole_windows = 1 + int(
            np.ceil(
                (waveform_length - self.fft_length) // self.window_separation))
        if return_cutoff:
            cutoff_length = waveform_length - (
                self.fft_length + (whole_windows - 1) * self.window_separation)
            return whole_windows, cutoff_length
        else:
            return whole_windows

    def compute_waveform_length_for_window_count(self, window_count):
        return self.fft_length + (window_count - 1) * self.window_separation

    def compute_waveform_duration_for_window_count(self, window_count):
        return self.sample_num_to_time(
            self.compute_waveform_length_for_window_count(window_count))

    def adjust_waveform_length_to_fit_windows(self, waveform_length):
        _, cutoff_length = self.compute_window_count(waveform_length,
                                                     return_cutoff=True)
        down_adjustment = -cutoff_length
        up_adjustment = self.window_separation - cutoff_length
        return waveform_length + (up_adjustment if up_adjustment <
                                  -down_adjustment else down_adjustment)

    def read_wav(self, file_path):
        waveform, sampling_rate = self.soundfile.read(file_path,
                                                      dtype=self.dtype)
        assert sampling_rate == self.sampling_rate
        return waveform

    def write_wav(self, file_path, waveform):
        self.soundfile.write(file_path, waveform, self.sampling_rate)

    def convert_waveform(self, waveform):
        dtype = waveform.dtype
        converted_waveform = np.asfarray(waveform, dtype=self.dtype)
        if dtype.kind == 'i':
            converted_waveform /= -float(np.iinfo(dtype).min)
        elif dtype.kind == 'u':
            converted_waveform = converted_waveform * (
                2 / float(np.iinfo(dtype).max)) - 1.0
        return converted_waveform

    def feature_is_accepted(self, energies):
        return np.mean(energies < self.low_energy_threshold
                       ) <= self.max_low_energy_feature_proportion

    def __call__(self,
                 waveform,
                 split_into_features=True,
                 allow_all_energies=False,
                 return_waveforms=False):
        if split_into_features:
            assert self.can_extract_feature(
                waveform), 'Input waveform shorter than feature length'
            waveforms = self.split_waveform(waveform)
            if allow_all_energies or not self.filter_features:
                features = self.compute_splitted_features(waveforms)
                return (features,
                        waveforms) if return_waveforms else (features, )
            else:
                features, energies = self.compute_splitted_features(
                    waveforms, return_energies=True)

                # Note: Probably better to use loudness than energy here
                is_accepted = list(map(self.feature_is_accepted, energies))
                return (is_accepted,
                        *((features, waveforms) if return_waveforms else
                          (features, )))
        else:
            return self.compute_feature(waveform)

    def compute_splitted_features(self, waveform, return_energies=False):
        waveforms = waveform if (isinstance(waveform,
                                            (list, tuple)) or waveform.ndim
                                 == 2) else self.split_waveform(waveform)
        features = [
            self.compute_feature(splitted_waveform,
                                 return_energies=return_energies)
            for splitted_waveform in waveforms
        ]
        return zip(*features) if return_energies else features

    def split_waveform(self, waveform):
        return get_striding_windows(waveform,
                                    self.feature_length,
                                    stride=self.feature_stride)

    def take_log_of_mel_spectrogram(self, mel_spectrogram):
        return np.log(mel_spectrogram + self.log_offset)

    def compute_mel_spectrogram_librosa(self, waveform):
        mel_spectrogram = self.librosa.feature.melspectrogram(
            y=waveform,
            sr=self.sampling_rate,
            win_length=self.window_length,
            hop_length=self.window_separation,
            fmin=self.min_frequency,
            fmax=self.max_frequency,
            n_mels=self.n_mel_bands,
            n_fft=self.fft_length,
            htk=False,
            center=False,
            power=1)
        return mel_spectrogram

    def create_mel_filterbank(self):
        self.mel_filterbank = librosa_destilled.mel_filters(
            self.sampling_rate,
            self.fft_length,
            n_mels=self.n_mel_bands,
            fmin=self.min_frequency,
            fmax=self.max_frequency)

    def compute_mel_spectrogram_python_speech_features(self,
                                                       waveform,
                                                       return_spectrogram=False
                                                       ):
        frames = self.python_speech_features.sigproc.framesig(
            waveform,
            self.window_length,
            self.window_separation,
            winfunc=self.hann_window)
        spectrogram = self.python_speech_features.sigproc.magspec(
            frames, self.fft_length).T
        mel_spectrogram = np.dot(self.mel_filterbank, spectrogram)
        if return_spectrogram:
            return mel_spectrogram, spectrogram
        else:
            return mel_spectrogram

    def compute_log_mel_spectrogram(self, waveform, return_energies=False):
        mel_spectrogram = self.compute_mel_spectrogram(waveform)

        if return_energies:
            energies = np.sum(mel_spectrogram, axis=0)
            return self.take_log_of_mel_spectrogram(mel_spectrogram), energies
        else:
            return self.take_log_of_mel_spectrogram(mel_spectrogram)

    def compute_feature(self, *args, **kwargs):
        return self.compute_log_mel_spectrogram(*args, **kwargs)

    def compute_feature_and_loudness(self, waveform):
        mel_spectrogram, spectrogram = self.compute_mel_spectrogram_python_speech_features(
            waveform, return_spectrogram=True)
        return mel_spectrogram, self.compute_loudness(spectrogram)

    def compute_loudness(self, spectrogram):
        return librosa_destilled.spectrogram_rms(
            self.compute_A_weighted_spectrogram(spectrogram)).squeeze()

    def create_A_weights(self):
        frequencies = librosa_destilled.fft_frequencies(
            self.sampling_rate, self.fft_length)
        self.a_weights = librosa_destilled.A_amplitude_weighting(frequencies)

    def compute_A_weighted_spectrogram(self, spectrogram):
        return self.a_weights[:, np.newaxis] * spectrogram

    def benchmark(self, waveform, n_repeats=1000):
        start_time = time.time()
        for _ in range(n_repeats):
            self.compute_feature(waveform)
        elapsed = time.time() - start_time
        print(
            f'Time per {waveform.size} samples: {1e3*elapsed/n_repeats:g} ms')

    def sample_num_to_time(self, sample_num):
        return sample_num / self.sampling_rate

    def time_to_sample_num(self, time):
        return int(time * self.sampling_rate)

    def windown_num_to_time(self, window_num):
        return self.sample_num_to_time(self.window_length / 2 +
                                       window_num * self.window_separation)

    def plot(self,
             waveform,
             show_feature_boundaries=True,
             fig_kwargs={},
             **kwargs):
        import matplotlib.pyplot as plt
        fig, axes = plt.subplots(nrows=2, **fig_kwargs)
        self.plot_waveform(axes[0], waveform, xlabel=None)
        self.plot_feature(fig,
                          axes[1],
                          self(waveform, **kwargs),
                          show_feature_boundaries=show_feature_boundaries)
        fig.tight_layout()
        plt.show()

    def plot_with_loudness(self, waveform, fig_kwargs={}):
        import matplotlib.pyplot as plt
        fig, axes = plt.subplots(nrows=2, **fig_kwargs)
        self.plot_waveform(axes[0], waveform, xlabel=None)
        feature, loudness = self.compute_feature_and_loudness(waveform)
        self.plot_feature(fig, axes[1], feature, loudness=loudness)
        fig.tight_layout()
        plt.show()

    def plot_waveform(self, ax, waveform, xlabel='Time [s]'):
        end_time = self.sample_num_to_time(waveform.size - 1)
        ax.plot(np.linspace(0, end_time, waveform.size), waveform)
        ax.set_xlim(0, end_time)
        if xlabel:
            ax.set_xlabel(xlabel)
        ax.set_ylabel('Amplitude')

    def plot_feature(self,
                     fig,
                     ax,
                     feature,
                     energy=None,
                     loudness=None,
                     show_feature_boundaries=True):

        if isinstance(feature, (list, tuple)):
            times = self.windown_num_to_time(
                np.cumsum(
                    np.fromiter(map(lambda c: c.shape[1], feature),
                                int)))[:-1] if show_feature_boundaries else []
            feature = np.concatenate(feature, axis=1)
            if energy is not None:
                energy = np.concatenate(energy)
        else:
            times = []

        start_time = self.windown_num_to_time(0)
        end_time = self.windown_num_to_time(feature.shape[1] - 1)

        im = ax.imshow(
            feature,
            origin='lower',
            interpolation='nearest',
            aspect='auto',
            extent=[start_time, end_time, 0.5, feature.shape[0] + 0.5])

        for time in times:
            ax.axvline(time, color='white', lw=1.0, ls='--')

        if energy is not None:
            energy_ax = ax.twinx()
            energy_ax.plot(np.linspace(start_time, end_time, energy.size),
                           energy,
                           color='tab:orange',
                           lw=1.0)
            energy_ax.axhline(self.low_energy_threshold,
                              color='tab:red',
                              lw=1.0,
                              ls='--')
            energy_ax.set_ylabel('Energy')

        if loudness is not None:
            loudness_ax = ax.twinx()
            loudness_ax.plot(np.linspace(start_time, end_time, loudness.size),
                             loudness,
                             color='tab:red',
                             lw=1.0)
            loudness_ax.set_ylabel('Loudness')

        from mpl_toolkits.axes_grid1 import make_axes_locatable
        divider = make_axes_locatable(ax)
        cax = divider.append_axes('top', size='5%', pad=0.05)
        fig.colorbar(im, cax=cax, orientation='horizontal')
        cax.xaxis.set_label_position('top')
        cax.xaxis.set_ticks_position('top')

        ax.set_yticks(np.arange(1, feature.shape[0] + 1, 4))
        ax.set_xlabel('Time [s]')
        ax.set_ylabel('Filter')


def amplitude_to_db(amplitude):
    return 20 * np.log10(amplitude)


def db_to_amplitude(db):
    return 10**(0.05 * db)


class AudioByteInterpreter:
    def __init__(self, output_format='FLOAT_LE'):
        self.output_format = output_format
        self.parse_format()

    def __call__(self, bytes):
        return np.frombuffer(bytes, dtype=self.dtype)

    def compute_n_bytes(self, n_samples):
        return n_samples * self.dtype.itemsize

    def parse_format(self):
        byteorders = dict(LE='<', BE='>')
        kinds = dict(S='i', U='u', FLOAT='f')
        bits2bytes = lambda x: int(x) // 8

        if '_' in self.output_format:
            type_size, byteorder = self.output_format.split('_')
        else:
            type_size = self.output_format
            byteorder = 'LE'

        if 'FLOAT' in type_size:
            if type_size == 'FLOAT':
                kind = type_size
                bits = 32
            else:
                kind = 'FLOAT'
                bits = int(type_size[5:])
            self.max_sample_range = 2
        else:
            kind = type_size[0]
            bits = int(type_size[1:])
            self.max_sample_range = 2**bits

        self.dtype = np.dtype('{}{}{:d}'.format(byteorders[byteorder],
                                                kinds[kind], bits2bytes(bits)))


class Recorder:
    def __init__(self,
                 device,
                 sampling_rate=8000,
                 amplification=1,
                 output_format='FLOAT_LE'):
        self.device = device
        self.sampling_rate = sampling_rate
        self.amplification = amplification
        self.output_format = output_format

        self.interpreter = AudioByteInterpreter(
            output_format=self.output_format)

        self.arecord_args = [
            'arecord',
            f'--device={device}',
            '--quiet',
            '--file-type',
            'raw',
            f'--format={self.output_format}',
            f'--rate={sampling_rate:d}',
            '--channels=1',
        ]

    def record_waveform(self, n_samples):
        with subprocess.Popen(self.arecord_args + [f'--samples={n_samples:d}'],
                              stdout=subprocess.PIPE,
                              stderr=subprocess.DEVNULL) as process:
            record_time = time.time() + 0.5 * n_samples / self.sampling_rate
            waveform_bytes = process.stdout.read(
                self.interpreter.compute_n_bytes(n_samples))
        waveform = self.interpreter(waveform_bytes)
        return waveform, record_time

    def amplify_waveform(self, waveform):
        return waveform if self.amplification == 1 else waveform * self.amplification

    def amplify_feature(self, feature):
        return feature if self.amplification == 1 else feature + np.log(
            self.amplification)


class LoudnessAnalyzer:
    def __init__(self,
                 foreground_fraction=0.05,
                 background_fraction=0.05,
                 background_averaging_count=10):
        self.foreground_fraction = foreground_fraction
        self.background_fraction = background_fraction
        self.background_averaging_count = background_averaging_count

        self.foreground_loudness_level = None

        self.background_loudness = queue.Queue(
            maxsize=background_averaging_count)
        self.total_background_loudness = 0

    def add_loudness(self, loudness):
        loudness.sort()

        n_foreground_loudnesses = int(self.foreground_fraction * loudness.size)
        foreground_loudness = np.mean(loudness[-n_foreground_loudnesses:])
        self.add_foreground_loudness(foreground_loudness)

        n_background_loudnesses = int(self.background_fraction * loudness.size)
        background_loudness = np.mean(loudness[:n_background_loudnesses])
        self.add_background_loudness(background_loudness)

    def get_loudness_levels(self):
        foreground_loudness_level = self.get_foreground_loudness_level()
        background_loudness_level = self.get_background_loudness_level()
        return background_loudness_level, foreground_loudness_level - background_loudness_level

    def get_foreground_loudness_level(self):
        assert self.foreground_loudness_level is not None
        return self.foreground_loudness_level

    def get_background_loudness_level(self):
        assert self.background_loudness.qsize() > 0
        average_background_loudness = self.total_background_loudness / self.background_loudness.qsize(
        )
        return amplitude_to_db(average_background_loudness)

    def add_foreground_loudness(self, foreground_loudness):
        self.foreground_loudness_level = amplitude_to_db(foreground_loudness)

    def add_background_loudness(self, background_loudness):
        background_loudness_change = (
            background_loudness - self.background_loudness.get()
        ) if self.background_loudness.full() else background_loudness
        self.total_background_loudness += background_loudness_change
        self.background_loudness.put(background_loudness)


class Standardizer:
    def __init__(self, standardization_file):
        self.read_standardization_file(standardization_file)

    def read_standardization_file(self, standardization_file):
        standardization = np.load(standardization_file)
        self.mean = standardization['mean']
        self.standard_deviation = standardization['standard_deviation']

    def __call__(self, feature):
        feature -= self.mean
        feature /= self.standard_deviation


class FeatureProvider:
    def __init__(self,
                 audio_device,
                 feature_extractor,
                 min_sound_contrast=0,
                 amplification=1,
                 standardization_file=None):
        self.feature_extractor = feature_extractor
        self.feature_extractor.create_A_weights()
        self.recorder = Recorder(audio_device,
                                 sampling_rate=feature_extractor.sampling_rate,
                                 amplification=amplification)
        self.analyzer = LoudnessAnalyzer()
        self.min_sound_contrast = min_sound_contrast
        self.standardizer = None if standardization_file is None else Standardizer(
            standardization_file)

    def __call__(self):
        waveform, record_time = self.recorder.record_waveform(
            self.feature_extractor.feature_length)

        feature, loudness = self.feature_extractor.compute_feature_and_loudness(
            waveform)

        self.analyzer.add_loudness(loudness)
        background_sound_level, sound_level_over_background = self.analyzer.get_loudness_levels(
        )

        if sound_level_over_background < self.min_sound_contrast:
            return None, background_sound_level, sound_level_over_background, record_time

        feature = self.recorder.amplify_feature(feature)

        if self.standardizer is not None:
            self.standardizer(feature)

        return feature, background_sound_level, sound_level_over_background, record_time
