#!/usr/bin/env python3

import os
import subprocess
import control

MODE = 'videostream'
HORIZONTAL_RESOLUTIONS = {480: 640, 720: 1280, 1080: 1920}


def stream_video():
    control.register_shutdown_handler()
    control.enter_mode(MODE, stream_video_with_settings)


def stream_video_with_settings(vertical_resolution=720,
                               framerate=30,
                               use_variable_framerate=False,
                               rotation=0,
                               flip_horizontally=False,
                               flip_vertically=False,
                               exposure_mode='off',
                               metering='average',
                               exposure_value_compensation=0,
                               exposure_time=None,
                               iso=None,
                               white_balance_mode='auto',
                               red_gain=None,
                               blue_gain=None,
                               capture_audio=True,
                               volume=1.0,
                               show_time=True):
    mic_id = os.environ['BM_MIC_ID']
    picam_path = os.environ['BM_PICAM_DIR']
    picam_log_path = os.environ['BM_PICAM_LOG_PATH']
    picam_output_path = os.environ['BM_PICAM_STREAM_DIR']

    assert vertical_resolution in HORIZONTAL_RESOLUTIONS, \
        'Vertical resolution ({}) is not one of {}'.format(
        vertical_resolution, ', '.join(list(HORIZONTAL_RESOLUTIONS.keys())))
    resolution_args = [
        '-w',
        str(HORIZONTAL_RESOLUTIONS[vertical_resolution]), '-h',
        str(vertical_resolution)
    ]

    audio_args = ['--noaudio'] if not capture_audio else []

    with open(picam_log_path, 'a') as log_file:
        subprocess.check_call([
            os.path.join(picam_path, 'picam'), '-o', picam_output_path,
            '--time', '--alsadev', mic_id
        ] + resolution_args + audio_args,
                              stdout=log_file,
                              stderr=log_file,
                              cwd=picam_path)


if __name__ == '__main__':
    stream_video()
