#!/usr/bin/env python3

import control

MODE = 'standby'


def set_standby():
    control.update_mode_in_database(MODE)


if __name__ == '__main__':
    set_standby()
