import sys
import numpy as np


def hz_to_mel(frequencies):
    frequencies = np.asanyarray(frequencies)

    # Fill in the linear part
    f_min = 0.0
    f_sp = 200.0 / 3

    mels = (frequencies - f_min) / f_sp

    # Fill in the log-scale part

    min_log_hz = 1000.0  # beginning of log region (Hz)
    min_log_mel = (min_log_hz - f_min) / f_sp  # same (Mels)
    logstep = np.log(6.4) / 27.0  # step size for log region

    if frequencies.ndim:
        # If we have array data, vectorize
        log_t = frequencies >= min_log_hz
        mels[log_t] = min_log_mel + np.log(
            frequencies[log_t] / min_log_hz) / logstep
    elif frequencies >= min_log_hz:
        # If we have scalar data, heck directly
        mels = min_log_mel + np.log(frequencies / min_log_hz) / logstep

    return mels


def mel_to_hz(mels):
    mels = np.asanyarray(mels)

    # Fill in the linear scale
    f_min = 0.0
    f_sp = 200.0 / 3
    freqs = f_min + f_sp * mels

    # And now the nonlinear scale
    min_log_hz = 1000.0  # beginning of log region (Hz)
    min_log_mel = (min_log_hz - f_min) / f_sp  # same (Mels)
    logstep = np.log(6.4) / 27.0  # step size for log region

    if mels.ndim:
        # If we have vector data, vectorize
        log_t = mels >= min_log_mel
        freqs[log_t] = min_log_hz * np.exp(logstep *
                                           (mels[log_t] - min_log_mel))
    elif mels >= min_log_mel:
        # If we have scalar data, check directly
        freqs = min_log_hz * np.exp(logstep * (mels - min_log_mel))

    return freqs


def fft_frequencies(sr, n_fft):
    return np.linspace(0, float(sr) / 2, int(1 + n_fft // 2), endpoint=True)


def mel_frequencies(n_mels, fmin, fmax):
    min_mel = hz_to_mel(fmin)
    max_mel = hz_to_mel(fmax)

    mels = np.linspace(min_mel, max_mel, n_mels)

    return mel_to_hz(mels)


def mel_filters(sr, n_fft, n_mels=128, fmin=0.0, fmax=None, dtype=np.float32):
    if fmax is None:
        fmax = float(sr) / 2

    # Initialize the weights
    n_mels = int(n_mels)
    weights = np.zeros((n_mels, int(1 + n_fft // 2)), dtype=dtype)

    # Center freqs of each FFT bin
    fftfreqs = fft_frequencies(sr, n_fft)

    # 'Center freqs' of mel bands - uniformly spaced between limits
    mel_f = mel_frequencies(n_mels + 2, fmin, fmax)

    fdiff = np.diff(mel_f)
    ramps = np.subtract.outer(mel_f, fftfreqs)

    for i in range(n_mels):
        # lower and upper slopes for all bins
        lower = -ramps[i] / fdiff[i]
        upper = ramps[i + 2] / fdiff[i + 1]

        # .. then intersect them with each other and zero
        weights[i] = np.maximum(0, np.minimum(lower, upper))

    # Slaney-style mel is scaled to be approx constant energy per channel
    enorm = 2.0 / (mel_f[2:n_mels + 2] - mel_f[:n_mels])
    weights *= enorm[:, np.newaxis]

    return weights


def A_amplitude_weighting(frequencies):

    f_sq = np.asanyarray(frequencies)**2.0

    const = np.array([12200, 20.6, 107.7, 737.9])**2.0
    weights = const[0] * f_sq**2 / ((const[1] + f_sq) * np.sqrt(
        (const[2] + f_sq) * (const[3] + f_sq)) * (const[0] + f_sq))

    return weights


def spectrogram_rms(S):

    frame_length = 2 * (S.shape[0] - 1)

    # power spectrogram
    x = np.abs(S)**2

    # Adjust the DC and sr/2 component
    x[0] *= 0.5
    if frame_length % 2 == 0:
        x[-1] *= 0.5

    # Calculate power
    power = 2 * np.sum(x, axis=0, keepdims=True) / frame_length**2

    return np.sqrt(power)
