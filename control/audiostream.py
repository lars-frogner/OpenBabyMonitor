#!/usr/bin/env python

import os
import subprocess
import control

MODE = 'audiostream'


def stream_audio():
    control.register_shutdown_handler()
    control.enter_mode(MODE, stream_audio_with_settings)


def stream_audio_with_settings():
    pass


if __name__ == '__main__':
    stream_audio()
