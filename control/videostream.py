#!/usr/bin/env python3

import os
import subprocess
import control

MODE = 'videostream'
HORIZONTAL_RESOLUTIONS = {480: 640, 720: 1280, 1080: 1920}


def stream_video():
    control.register_shutdown_handler()
    control.enter_mode(
        MODE, lambda mode, config, database: stream_video_with_settings(
            **control.read_settings(mode, config, database)))


def stream_video_with_settings(vertical_resolution=720,
                               use_variable_framerate=True,
                               framerate=30,
                               rotation=0,
                               flip_horizontally=False,
                               flip_vertically=False,
                               exposure_mode='auto',
                               metering='average',
                               exposure_value_compensation=0,
                               exposure_time=100,
                               iso=400,
                               white_balance_mode='auto',
                               red_gain=0.0,
                               blue_gain=0.0,
                               capture_audio=True,
                               show_time=True,
                               **kwargs):
    mic_id = os.environ['BM_MIC_ID']
    log_path = os.environ['BM_SERVER_LOG_PATH']
    picam_dir = os.environ['BM_PICAM_DIR']
    picam_output_dir = os.environ['BM_PICAM_STREAM_DIR']

    assert vertical_resolution in HORIZONTAL_RESOLUTIONS, \
        'Vertical resolution ({}) is not one of {}'.format(
        vertical_resolution, ', '.join(list(HORIZONTAL_RESOLUTIONS.keys())))
    resolution_args = [
        '--width',
        str(HORIZONTAL_RESOLUTIONS[vertical_resolution]), '--height',
        str(vertical_resolution)
    ]

    fps_args = ['--vfr'] if use_variable_framerate else [
        '--fps', str(framerate)
    ]

    orientation_args = ['--rotation', str(rotation)]
    if flip_horizontally:
        orientation_args += ['--hflip']
    if flip_vertically:
        orientation_args += ['--vflip']

    brightness_args = ['--metering', metering, '--ex', exposure_mode]
    if exposure_mode == 'off':
        brightness_args += [
            '--evcomp',
            str(exposure_value_compensation), '--shutter',
            str(exposure_time), '--iso',
            str(iso)
        ]

    color_args = ['--wb', white_balance_mode]
    if white_balance_mode == 'off':
        color_args += ['--wbred', str(red_gain), '--wbblue', str(blue_gain)]

    audio_args = ['--alsadev', mic_id] if capture_audio else ['--noaudio']

    time_args = ['--time', '--timeformat', r'%a %d.%m.%Y %T'
                 ] if show_time else []

    output_args = ['--hlsdir', picam_output_dir]

    with open(log_path, 'a') as log_file:
        subprocess.check_call([os.path.join(picam_dir, 'picam')] +
                              output_args + resolution_args + fps_args +
                              orientation_args + brightness_args + color_args +
                              audio_args + time_args,
                              stdout=subprocess.DEVNULL,
                              stderr=log_file,
                              cwd=picam_output_dir)


if __name__ == '__main__':
    stream_video()
