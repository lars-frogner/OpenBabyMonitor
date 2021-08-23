#!/usr/bin/env python3

import os
import subprocess
import control
import json

MODE = 'audiostream'


def stream_audio():
    control.register_shutdown_handler()
    control.enter_mode(
        MODE, lambda mode, config, database: stream_audio_with_settings(
            **control.read_settings(mode, config, database)))


def stream_audio_with_settings(sample_rate=44100, mp3_bitrate=128, **kwargs):
    mic_id = os.environ['BM_MIC_ID']
    log_path = os.environ['BM_SERVER_LOG_PATH']
    micstream_dir = os.environ['BM_MICSTREAM_DIR']
    micstream_endpoint = os.environ['BM_MICSTREAM_ENDPOINT']
    micstream_port = os.environ['BM_MICSTREAM_PORT']
    micstream_header_filepath = os.environ['BM_MICSTREAM_HEADERS_FILE']

    update_headers(micstream_header_filepath)

    input_args = ['--device', 'plug{}'.format(mic_id)]
    output_args = [
        '--endpoint', micstream_endpoint, '--headers',
        micstream_header_filepath, '--port', micstream_port
    ]
    quality_args = [
        '--sample-rate', '{}'.format(sample_rate), '--bitrate',
        '{}'.format(int(round(mp3_bitrate)))
    ]

    with open(log_path, 'a') as log_file:
        subprocess.check_call([os.path.join(micstream_dir, 'micstream')] +
                              input_args + output_args + quality_args,
                              stdout=subprocess.DEVNULL,
                              stderr=log_file)


def update_headers(header_filepath):
    # 'Cache-Control: no-cache' prevents browsers from caching the streamed file
    # 'Access-Control-Allow-Origin: <origin>' allows the Apache server to request the stream endpoint
    ip = subprocess.check_output(['hostname', '-I']).decode().strip()
    origins = [ip, 'babymonitor.local', 'babymonitor.home']
    headers = {
        'Cache-Control':
        'no-cache',
        'Access-Control-Allow-Origin':
        ' '.join(['http://{}'.format(origin) for origin in origins])
    }
    with open(header_filepath, 'w') as f:
        json.dump(headers, f)


if __name__ == '__main__':
    stream_audio()
