import time
import numpy as np
import matplotlib.pyplot as plt
from mpl_toolkits.axes_grid1 import make_axes_locatable
import librosa
import soundfile


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
                 feature_window_count=64,
                 feature_overlap_fraction=0.5,
                 low_energy_threshold=0.15,
                 max_low_energy_feature_proportion=0.8):
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
        self.feature_length = self.compute_waveform_length_for_window_count(feature_window_count)
        self.feature_duration = self.sample_num_to_time(self.feature_length)

        self.feature_overlap_length = min(
            int(feature_overlap_fraction * self.feature_length),
            self.feature_length - 1)
        self.feature_stride = self.feature_length - self.feature_overlap_length
        self.feature_overlap_fraction = self.feature_overlap_length / self.feature_length

        self.low_energy_threshold = low_energy_threshold
        self.max_low_energy_feature_proportion = max_low_energy_feature_proportion
        self.filter_features = self.low_energy_threshold is not None and self.max_low_energy_feature_proportion is not None

        self.mel_spectrogram_kwargs = dict(sr=self.sampling_rate,
                                win_length=self.window_length,
                                hop_length=self.window_separation,
                                fmin=self.min_frequency,
                                fmax=self.max_frequency,
                                n_mels=self.n_mel_bands,
                                n_fft=self.fft_length,
                                htk=False,
                                center=False, power=1)

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
        return self.fft_length + (window_count - 1)*self.window_separation

    def compute_waveform_duration_for_window_count(self, window_count):
        return self.sample_num_to_time(self.compute_waveform_length_for_window_count(window_count))

    def adjust_waveform_length_to_fit_windows(self, waveform_length):
        _, cutoff_length = self.compute_window_count(waveform_length,
                                                     return_cutoff=True)
        down_adjustment = -cutoff_length
        up_adjustment = self.window_separation - cutoff_length
        return waveform_length + (up_adjustment if up_adjustment <
                                  -down_adjustment else down_adjustment)

    def read_wav(self, file_path):
        waveform, _ = librosa.load(file_path,
                                   sr=self.sampling_rate,
                                   dtype=self.dtype,
                                   mono=True)
        return waveform

    def write_wav(self, file_path, waveform):
        soundfile.write(file_path, waveform, self.sampling_rate)

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
        return np.mean(energies < self.low_energy_threshold) <= self.max_low_energy_feature_proportion

    def __call__(self, waveform, split_into_features=True, allow_all_energies=False, return_waveforms=False):
        if split_into_features:
            assert self.can_extract_feature(waveform), 'Input waveform shorter than feature length'
            waveforms = self.split_waveform(waveform)
            if allow_all_energies or not self.filter_features:
                features = self.compute_splitted_features(waveforms)
                return (features, waveforms) if return_waveforms else (features,)
            else:
                features, energies = self.compute_splitted_features(waveforms, return_energies=True)
                is_accepted = list(map(self.feature_is_accepted, energies))
                return is_accepted, *((features, waveforms) if return_waveforms else (features,))
        else:
            return self.compute_feature(waveform)

    def compute_splitted_features(self, waveform, return_energies=False):
        waveforms = waveform if (isinstance(waveform, (list, tuple)) or waveform.ndim == 2) else self.split_waveform(waveform)
        features = [self.compute_feature(splitted_waveform, return_energies=return_energies) for splitted_waveform in waveforms]
        return zip(*features) if return_energies else features

    def split_waveform(self, waveform):
        return get_striding_windows(waveform,
                                    self.feature_length,
                                    stride=self.feature_stride)

    def take_log_of_mel_spectrogram(self, mel_spectrogram):
        return np.log(mel_spectrogram + self.log_offset)

    def compute_log_mel_spectrogram(self, waveform, return_energies=False):
        mel_spectrogram = librosa.feature.melspectrogram(y=waveform, **self.mel_spectrogram_kwargs)
        if return_energies:
            energies = np.sum(mel_spectrogram, axis=0)
            return self.take_log_of_mel_spectrogram(mel_spectrogram), energies
        else:
            return self.take_log_of_mel_spectrogram(mel_spectrogram)

    def compute_feature(self, *args, **kwargs):
        return self.compute_log_mel_spectrogram(*args, **kwargs)

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
             **kwargs):
        fig, axes = plt.subplots(nrows=2)
        self.plot_waveform(axes[0], waveform, xlabel=None)
        self.plot_feature(
            self(waveform, **kwargs),
            fig=fig,
            ax=axes[1],
            show_feature_boundaries=show_feature_boundaries)

    def plot_waveform(self, ax, waveform, xlabel='Time [s]'):
        end_time = self.sample_num_to_time(waveform.size - 1)
        ax.plot(np.linspace(0, end_time, waveform.size), waveform)
        ax.set_xlim(0, end_time)
        if xlabel:
            ax.set_xlabel(xlabel)
        ax.set_ylabel('Amplitude')

    def plot_feature(self,
                      feature,
                      energy=None,
                      fig=None,
                      ax=None,
                      show_feature_boundaries=True,
                      fig_kwargs={},
                      show=True):
        if fig is None or ax is None:
            fig, ax = plt.subplots(**fig_kwargs)

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

        im = ax.imshow(feature,
                  origin='lower',
                  interpolation='nearest',
                  aspect='auto',
                  extent=[
                      start_time,
                      end_time,
                      0.5, feature.shape[0] + 0.5
                  ])

        for time in times:
            ax.axvline(time, color='white', lw=1.0, ls='--')

        if energy is not None:
            energy_ax = ax.twinx()
            energy_ax.plot(np.linspace(start_time, end_time, energy.size), energy, color='tab:orange', lw=1.0)
            energy_ax.axhline(self.low_energy_threshold, color='tab:red', lw=1.0, ls='--')
            energy_ax.set_ylabel('Energy')

        divider = make_axes_locatable(ax)
        cax = divider.append_axes('top', size='5%', pad=0.05)
        fig.colorbar(im, cax=cax, orientation='horizontal')
        cax.xaxis.set_label_position('top')
        cax.xaxis.set_ticks_position('top')

        ax.set_yticks(np.arange(1, feature.shape[0] + 1, 4))
        ax.set_xlabel('Time [s]')
        ax.set_ylabel('Filter')

        if show:
            fig.tight_layout()
            plt.show()

        return fig, ax


if __name__ == '__main__':

    e = AudioFeatureExtractor()
    waveform = e.read_wav('speech_whistling2.wav')
    e.plot(waveform, split_into_features=False)

    # plt.ion()
    # e = AudioFeatureExtractor()
    # waveform = e.read_wav('data/cry.wav')[:80000]
    # e.plot_feature(*e.compute_splitted_features(waveform, return_energies=True))

    # for video_id in ['_-VqjVHYz5Y', '_0OTlXMOt0g', '_2KRnyRFSAk', '_4a8kWWGCxY', '_8rm9cSEjnM']:
    #     waveform = e.read_wav(f'data/raw/bad/{video_id}.wav')
    #     e.plot_feature(*e.compute_splitted_features(waveform, return_energies=True))

    # plt.pause(np.inf)
