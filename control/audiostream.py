#!/usr/bin/env python3

import os
import subprocess
import control

MODE = 'audiostream'


def stream_audio():
    control.register_shutdown_handler()
    control.enter_mode(
        MODE, lambda mode, config, database: stream_audio_with_settings(
            **control.read_settings(mode, config, database)))


def stream_audio_with_settings(encrypted=True,
                               gain=100,
                               sampling_rate=8000,
                               mp3_bitrate=128,
                               hls_segment_time=1,
                               hls_list_size=3,
                               **kwargs):
    output_dir = os.environ['BM_AUDIO_STREAM_DIR']
    output_file = os.environ['BM_AUDIO_STREAM_FILE']
    log_path = os.environ['BM_SERVER_LOG_PATH']
    mic_id = os.environ['BM_MIC_ID']
    sound_card_number = os.environ['BM_SOUND_CARD_NUMBER']

    update_gain(sound_card_number, gain, log_path)

    input_args = [
        '-f', 'alsa', '-channels', '1', '-sample_rate',
        '{:d}'.format(sampling_rate), '-i', 'plug{}'.format(mic_id)
    ]
    codec_args = [
        '-vn',
        '-acodec',
        'libmp3lame',
        '-b:a',
        '{:d}k'.format(mp3_bitrate),
    ]
    stream_args = [
        '-f', 'hls', '-hls_time', '{}'.format(hls_segment_time),
        '-hls_list_size', '{}'.format(hls_list_size), '-hls_flags',
        'delete_segments', '-hls_allow_cache', '0'
    ]
    encryption_args = ['-hls_key_info_file', 'stream.keyinfo'
                       ] if encrypted else []

    with open(log_path, 'a') as log_file:
        subprocess.check_call(
            ['ffmpeg', '-hide_banner', '-loglevel', 'error'] + input_args +
            codec_args + stream_args + encryption_args + [output_file],
            stdout=subprocess.DEVNULL,
            stderr=log_file,
            cwd=output_dir)


def update_gain(sound_card_number, gain, log_path):
    with open(log_path, 'a') as log_file:
        subprocess.check_call([
            'amixer', '-c', '{}'.format(sound_card_number), 'sset', 'Mic',
            '{}%'.format(gain)
        ],
                              stdout=subprocess.DEVNULL,
                              stderr=log_file)


if __name__ == '__main__':
    stream_audio()
