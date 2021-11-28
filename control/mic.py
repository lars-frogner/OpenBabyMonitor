#!/usr/bin/env python3

import sys
import os
import subprocess
import re
import pathlib


def get_mic_dir_path():
    return pathlib.Path(os.path.dirname(__file__)) / '.mic'


def get_mic_id_file_path():
    return get_mic_dir_path() / 'id'


def get_mic_volume_control_file_path():
    return get_mic_dir_path() / 'volume_control'


def select_mic():
    select_mic_id()
    select_mic_volume_control(get_mic_sound_card_number())


def select_mic_id():
    output = subprocess.check_output(['arecord', '-l'], text=True)
    matches = re.findall('^card (\d+): (.*), device (\d): (.*)$',
                         output,
                         flags=re.MULTILINE)

    if len(matches) == 1:
        match = matches[0]
    elif len(matches) > 1:
        n_available_sound_cards = len(matches)
        print('Found multiple available sound cards:')
        for i, m in enumerate(matches):
            print(f'[{i}]: card {m[0]}: {m[1]}, device {m[2]}: {m[3]}')
        while True:
            answer = input(
                f'Specify which sound card to use [0-{n_available_sound_cards-1}]: '
            )
            try:
                selected_card_idx = int(answer)
                if selected_card_idx >= 0 and selected_card_idx < n_available_sound_cards:
                    break
            except ValueError:
                pass
        match = matches[selected_card_idx]
    else:
        raise OSError('No sound card found')

    sound_card_number = match[0]
    device_number = match[2]
    mic_id = f'hw:{sound_card_number},{device_number}'

    get_mic_dir_path().mkdir(exist_ok=True)

    with open(get_mic_id_file_path(), 'w') as f:
        f.write(mic_id)


def select_mic_volume_control(sound_card_number):
    output = subprocess.check_output(['amixer', '-c', sound_card_number],
                                     text=True)
    matches = re.findall('^.+ \'(.+)\',\d+$\n^  Capabilities: .*c?volume.*$',
                         output,
                         flags=re.MULTILINE)
    if len(matches) == 1:
        volume_control_name = matches[0]
    elif len(matches) > 1:
        n_available_controls = len(matches)
        print(
            f'Found multiple available volume controls for sound card {sound_card_number}:'
        )
        for i, m in enumerate(matches):
            print(f'[{i}]: {m}')
        while True:
            answer = input(
                f'Specify which volume control to use [0-{n_available_controls-1}]: '
            )
            try:
                selected_control_idx = int(answer)
                if selected_control_idx >= 0 and selected_control_idx < n_available_controls:
                    break
            except ValueError:
                pass
        volume_control_name = matches[selected_control_idx]
    else:
        volume_control_name = ''
        print(
            f'Warning: No volume control found for sound card {sound_card_number}',
            file=sys.stderr)

    get_mic_dir_path().mkdir(exist_ok=True)

    with open(get_mic_volume_control_file_path(), 'w') as f:
        f.write(volume_control_name)


def get_mic_id():
    with open(get_mic_id_file_path(), 'r') as f:
        mic_id = f.read()
    return mic_id


def get_mic_sound_card_number():
    mic_id = get_mic_id()
    return mic_id[3:].split(',')[0]


def get_mic_volume_control():
    with open(get_mic_volume_control_file_path(), 'r') as f:
        volume_control = f.read()
    return volume_control


def set_mixer_control_value(sound_card_number, control_name, value, log_path):
    with open(log_path, 'a') as log_file:
        subprocess.check_call([
            'amixer', '-c', '{}'.format(sound_card_number), 'sset',
            control_name, value
        ],
                              stdout=subprocess.DEVNULL,
                              stderr=log_file)


def update_current_mic_volume(volume_percentage):
    log_path = os.environ['BM_SERVER_LOG_PATH']
    sound_card_number = get_mic_sound_card_number()
    volume_control_name = get_mic_volume_control()
    set_mixer_control_value(sound_card_number, volume_control_name,
                            f'{volume_percentage}%', log_path)


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('--select-mic', action='store_true')
    parser.add_argument('--get-mic-id', action='store_true')
    parser.add_argument('--get-mic-card', action='store_true')
    parser.add_argument('--set-mic-volume', type=float, default=None)
    args = parser.parse_args()

    if args.select_mic:
        select_mic()
    if args.get_mic_id:
        print(get_mic_id())
    if args.get_mic_card:
        print(get_mic_sound_card_number())
    if args.set_mic_volume is not None:
        update_current_mic_volume(max(0, min(100, args.set_mic_volume)))
