import time
import numpy as np
import matplotlib.pyplot as plt
import librosa.feature


def next_power_of_2(x):
    return 1 if x == 0 else 2**(x - 1).bit_length()


def get_striding_windows(arr, window_size, stride=1):
    shape = (1 + (arr.size - window_size) // stride, window_size)
    strides = (stride * arr.strides[0], arr.strides[0])
    return np.lib.stride_tricks.as_strided(arr, shape=shape, strides=strides)


class AudioFeatureExtractor:
    def __init__(self,
                 dtype=np.float32,
                 sampling_rate=8000,
                 n_mfc_coefs=16,
                 window_length=256,
                 window_separation=128,
                 n_mel_bands=64,
                 min_frequency=160,
                 max_frequency=3600,
                 lifter=22,
                 normalize_coefs=True,
                 feature_duration=1.0,
                 feature_overlap_fraction=0.5,
                 min_feature_energy=-950.0):
        self.dtype = np.dtype(dtype)
        assert self.dtype.kind == 'f'
        self.sampling_rate = sampling_rate
        self.n_mfc_coefs = n_mfc_coefs
        self.window_length = window_length
        self.window_separation = window_separation
        self.min_frequency = min_frequency
        self.max_frequency = max_frequency
        self.n_mel_bands = n_mel_bands
        self.lifter = 0 if normalize_coefs else lifter
        self.normalize_coefs = normalize_coefs
        self.fft_length = next_power_of_2(self.window_length)

        self.feature_length = self.adjust_waveform_length_to_fit_windows(
            self.time_to_sample_num(feature_duration))
        self.feature_duration = self.sample_num_to_time(self.feature_length)

        self.feature_overlap_length = min(
            int(feature_overlap_fraction * self.feature_length),
            self.feature_length - 1)
        self.feature_stride = self.feature_length - self.feature_overlap_length
        self.feature_overlap_fraction = self.feature_overlap_length / self.feature_length

        self.min_feature_energy = min_feature_energy

        self.mfcc_kwargs = dict(sr=self.sampling_rate,
                                n_mfcc=self.n_mfc_coefs,
                                win_length=self.window_length,
                                hop_length=self.window_separation,
                                fmin=self.min_frequency,
                                fmax=self.max_frequency,
                                n_mels=self.n_mel_bands,
                                lifter=self.lifter,
                                n_fft=self.fft_length,
                                center=False)

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

    def convert_waveform(self, waveform):
        dtype = waveform.dtype
        converted_waveform = np.asfarray(waveform, dtype=self.dtype)
        if dtype.kind == 'i':
            converted_waveform /= -float(np.iinfo(dtype).min)
        elif dtype.kind == 'u':
            converted_waveform = converted_waveform * (
                2 / float(np.iinfo(dtype).max)) - 1.0
        return converted_waveform

    def __call__(self, waveform, split_into_features=True):
        assert waveform.size > 0
        if split_into_features:
            if self.min_feature_energy is None:
                features = [
                    self.compute_mfcc(splitted_waveform)
                    for splitted_waveform in self.split_waveform(waveform)
                ]
            else:
                features = []
                for splitted_waveform in self.split_waveform(waveform):
                    feature, mean_energy = self.compute_mfcc(
                        splitted_waveform, return_energy=True)
                    if mean_energy >= self.min_feature_energy:
                        features.append(feature)
        else:
            features = self.compute_mfcc(waveform)

        return features

    def split_waveform(self, waveform):
        return get_striding_windows(waveform,
                                    self.feature_length,
                                    stride=self.feature_stride)

    def compute_mfcc(self, waveform, return_energy=False):
        mfcc = librosa.feature.mfcc(y=waveform, **self.mfcc_kwargs)
        if self.normalize_coefs:
            mean = np.mean(mfcc, axis=1)
            mfcc -= mean[:, np.newaxis]
            standard_deviation = np.std(mfcc, axis=1)
            valid = standard_deviation > 0
            mfcc[valid, :] /= standard_deviation[valid, np.newaxis]
            if return_energy:
                return mfcc, mean[0]
            else:
                return mfcc
        elif return_energy:
            return mfcc, np.mean(mfcc[0, :])
        else:
            return mfcc

    def benchmark(self, waveform, n_repeats=1000):
        start_time = time.time()
        for _ in range(n_repeats):
            self.compute_mfcc(waveform)
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
             skip_first_filter=True,
             show_feature_boundaries=True,
             average_features_over_time=False,
             **kwargs):
        fig, axes = plt.subplots(nrows=2)
        self.plot_waveform(axes[0], waveform, xlabel=None)
        self.plot_cepstrum(
            axes[1],
            self(waveform, **kwargs),
            skip_first_filter=skip_first_filter,
            show_feature_boundaries=show_feature_boundaries,
            average_features_over_time=average_features_over_time)
        fig.tight_layout()
        plt.show()

    def plot_waveform(self, ax, waveform, xlabel='Time [s]'):
        end_time = self.sample_num_to_time(waveform.size - 1)
        ax.plot(np.linspace(0, end_time, waveform.size), waveform)
        ax.set_xlim(0, end_time)
        if xlabel:
            ax.set_xlabel(xlabel)
        ax.set_ylabel('Amplitude')

    def plot_cepstrum(self,
                      ax,
                      mfcc,
                      skip_first_filter=True,
                      show_feature_boundaries=True,
                      average_features_over_time=False):
        if isinstance(mfcc, (list, tuple)):
            times = self.windown_num_to_time(
                np.cumsum(
                    np.fromiter(map(lambda c: c.shape[1], mfcc),
                                int)))[:-1] if show_feature_boundaries else []
            if average_features_over_time:
                mfcc = [
                    np.broadcast_to(np.mean(m, axis=1), m.T.shape).T
                    for m in mfcc
                ]
            mfcc = np.concatenate(mfcc, axis=1)
        else:
            times = []

        if skip_first_filter:
            mfcc = mfcc[1:, :]

        offset = int(skip_first_filter)
        ax.imshow(mfcc,
                  origin='lower',
                  interpolation='none',
                  aspect='auto',
                  extent=[
                      self.windown_num_to_time(0),
                      self.windown_num_to_time(mfcc.shape[1] - 1),
                      0.5 + offset, self.n_mfc_coefs + 0.5
                  ])
        for time in times:
            ax.axvline(time, color='white', ls='--')
        ax.set_yticks(np.arange(1 + offset, self.n_mfc_coefs + 1))
        ax.set_xlabel('Time [s]')
        ax.set_ylabel('Filter')


if __name__ == '__main__':
    e = AudioFeatureExtractor()
    # waveform = np.zeros(8000, dtype=np.float32)
    # waveform[10] = 0.1
    # waveform[4000:] = np.sin(np.linspace(0, 40 * 2 * np.pi, 4000))
    # e.plot(waveform, skip_first_filter=False, split_into_features=False)
    waveform = e.read_wav('data/cry.wav')
    # waveform = e.read_wav('data/train/bad/BNfclSQ7ZKk.wav')

    e.plot(waveform[:80000],
           skip_first_filter=False,
           split_into_features=True,
           show_feature_boundaries=True,
           average_features_over_time=False)
