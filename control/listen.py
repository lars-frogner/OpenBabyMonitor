#!/usr/bin/env python3

import os
import subprocess
import time
import control

MODE = 'listen'


def listen():
    control.register_shutdown_handler()
    control.enter_mode(
        MODE, lambda mode, config, database: listen_with_settings(
            **control.read_settings(mode, config, database)))


def listen_with_settings(**kwargs):
    while True:
        time.sleep(0.1)


if __name__ == '__main__':
    listen()
