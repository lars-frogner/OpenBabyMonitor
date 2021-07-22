#!/usr/bin/env python

import os
import subprocess
import control

MODE = 'listen'


def listen():
    control.register_shutdown_handler()
    control.enter_mode(MODE, listen_with_settings)


def listen_with_settings():
    pass


if __name__ == '__main__':
    listen()
