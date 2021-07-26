#!/usr/bin/env python3

import os
import subprocess
import time
import control

MODE = 'listen'


def listen():
    control.register_shutdown_handler()
    control.enter_mode(MODE, listen_with_settings)


def listen_with_settings():
    while True:
        time.sleep(0.1)


if __name__ == '__main__':
    listen()
