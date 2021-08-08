#!/usr/bin/env python3

import os
import subprocess
import control

MODE = 'audiostream'


def stream_audio():
    control.register_shutdown_handler()
    control.enter_mode(MODE, stream_audio_with_settings)


def stream_audio_with_settings(sample_rate=44100, mp3_bitrate=128000):
    mic_id = os.environ['BM_MIC_ID']
    log_path = os.environ['BM_SERVER_LOG_PATH']
    micstream_dir = os.environ['BM_MICSTREAM_DIR']
    micstream_endpoint = os.environ['BM_MICSTREAM_ENDPOINT']
    micstream_port = os.environ['BM_MICSTREAM_PORT']

    input_args = ['-v', '--device', 'plug{}'.format(mic_id)]
    output_args = ['--endpoint', micstream_endpoint, '--port', micstream_port]
    quality_args = [
        '--sample-rate', '{}'.format(sample_rate), '--bitrate',
        '{}'.format(int(round(mp3_bitrate * 1e-3)))
    ]

    with open(log_path, 'a') as log_file:
        subprocess.check_call([os.path.join(micstream_dir, 'micstream')] +
                              input_args + output_args + quality_args,
                              stdout=subprocess.DEVNULL,
                              stderr=log_file)


if __name__ == '__main__':
    stream_audio()
