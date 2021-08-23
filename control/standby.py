#!/usr/bin/env python3

import control

MODE = 'standby'


def set_standby():
    config = control.get_config()
    with control.get_database(config) as open_database:
        control.update_mode_in_database(MODE, config, open_database)


if __name__ == '__main__':
    set_standby()
