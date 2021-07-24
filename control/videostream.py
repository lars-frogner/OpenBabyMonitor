#!/usr/bin/env python

import os
import subprocess
import control

MODE = 'videostream'


def stream_video():
    control.register_shutdown_handler()
    control.enter_mode(MODE, stream_video_with_settings)


def stream_video_with_settings(full_hd=False, audio=True):
    mic_id = os.environ['BM_MIC_ID']
    picam_path = os.environ['BM_PICAM_PATH']
    picam_log_path = os.environ['BM_PICAM_LOG_PATH']
    picam_output_path = os.environ['BM_PICAM_OUTPUT_PATH']

    resolution_args = ['-w', '1920', '-h', '1080'] if full_hd else []
    audio_args = ['--noaudio'] if not audio else []

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
