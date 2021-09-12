const PASS_FILTER_DAMPING_RATE = 12.0 // [dB/octave]
const FREQUENCY_ATTENUATION_LIMIT = 10.0 // [dB]

function setupBandpassFilters(highpassFilter, lowpassFilter) {
    highpassFilter.type = 'highpass';
    lowpassFilter.type = 'lowpass';
    const damping_range_factor = 2 ** (FREQUENCY_ATTENUATION_LIMIT / PASS_FILTER_DAMPING_RATE);
    highpassFilter.frequency.value = SETTING_MIN_FREQUENCY * damping_range_factor;
    lowpassFilter.frequency.value = SETTING_MAX_FREQUENCY / damping_range_factor;
}

function setupGain(gain) {
    gain.gain.value = SETTING_VOLUME;
}
