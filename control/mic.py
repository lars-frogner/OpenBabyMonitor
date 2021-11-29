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


def get_manually_selected_mic_file_path():
    return get_mic_dir_path() / 'manually_selected'


def get_mic_volume_control_file_path():
    return get_mic_dir_path() / 'volume_control'


def select_mic(auto_choice=False):
    select_mic_id(auto_choice=auto_choice)
    select_mic_volume_control(get_mic_sound_card_number(),
                              auto_choice=auto_choice)


def select_mic_id(auto_choice=False):
    output = subprocess.check_output(['arecord', '-l'], text=True)
    matches = re.findall('^card (\d+): (.*), device (\d): (.*)$',
                         output,
                         flags=re.MULTILINE)

    if len(matches) == 1:
        match = matches[0]
    elif len(matches) > 1 and auto_choice:
        manually_selected_mic = get_manually_selected_mic()
        available_mics = [f'{m[1]},{m[3]}' for m in matches]
        selected_card_idx = available_mics.index(
            manually_selected_mic
        ) if manually_selected_mic in available_mics else 0
        match = matches[selected_card_idx]
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
        manually_selected_mic = f'{match[1]},{match[3]}'
        set_manually_selected_mic(manually_selected_mic)
    else:
        print('Warning: No sound card found, please connect a microphone',
              file=sys.stderr)
        match = None

    if match is None:
        mic_id = None
    else:
        sound_card_number = match[0]
        device_number = match[2]
        mic_id = f'hw:{sound_card_number},{device_number}'

    set_mic_id(mic_id)


def select_mic_volume_control(sound_card_number, auto_choice=False):
    if sound_card_number is None:
        set_mic_volume_control(None)
        return

    output = subprocess.check_output(['amixer', '-c', sound_card_number],
                                     text=True)

    matches = re.findall('^.+ \'(.+)\',\d+$\n^  Capabilities: .*c?volume.*$',
                         output,
                         flags=re.MULTILINE)

    if len(matches) == 1 or (len(matches) > 1 and auto_choice):
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
        volume_control_name = None
        print(
            f'Warning: No volume control found for sound card {sound_card_number}',
            file=sys.stderr)

    set_mic_volume_control(volume_control_name)


def set_mic_id(mic_id):
    file_path = get_mic_id_file_path()
    if mic_id is None:
        if file_path.exists():
            file_path.unlink()
    else:
        get_mic_dir_path().mkdir(exist_ok=True)
        with open(file_path, 'w') as f:
            f.write(mic_id)


def get_mic_id():
    file_path = get_mic_id_file_path()
    if not file_path.exists():
        return None
    with open(file_path, 'r') as f:
        mic_id = f.read()
    return mic_id


def get_mic_sound_card_number():
    mic_id = get_mic_id()
    return None if mic_id is None else mic_id[3:].split(',')[0]


def set_manually_selected_mic(manually_selected_mic):
    file_path = get_manually_selected_mic_file_path()
    if manually_selected_mic is None:
        if file_path.exists():
            file_path.unlink()
    else:
        get_mic_dir_path().mkdir(exist_ok=True)
        with open(file_path, 'w') as f:
            f.write(manually_selected_mic)


def get_manually_selected_mic():
    file_path = get_manually_selected_mic_file_path()
    if not file_path.exists():
        return None
    with open(file_path, 'r') as f:
        manually_selected_mic = f.read()
    return manually_selected_mic


def set_mic_volume_control(volume_control_name):
    file_path = get_mic_volume_control_file_path()
    if volume_control_name is None:
        if file_path.exists():
            file_path.unlink()
    else:
        get_mic_dir_path().mkdir(exist_ok=True)
        with open(file_path, 'w') as f:
            f.write(volume_control_name)


def get_mic_volume_control():
    file_path = get_mic_volume_control_file_path()
    if not file_path.exists():
        return None
    with open(file_path, 'r') as f:
        volume_control_name = f.read()
    return volume_control_name


def update_current_mic_volume(volume_percentage):
    sound_card_number = get_mic_sound_card_number()
    volume_control_name = get_mic_volume_control()
    if sound_card_number is not None and volume_control_name is not None:
        set_mixer_control_value(sound_card_number, volume_control_name,
                                f'{volume_percentage}%')


def set_mixer_control_value(sound_card_number, control_name, value):
    with open(os.environ['BM_SERVER_LOG_PATH'], 'a') as log_file:
        subprocess.check_call([
            'amixer', '-c', '{}'.format(sound_card_number), 'sset',
            control_name, value
        ],
                              stdout=subprocess.DEVNULL,
                              stderr=log_file)


if __name__ == '__main__':
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument('--select-mic', action='store_true')
    parser.add_argument('--auto-choice', action='store_true')
    parser.add_argument('--get-mic-id', action='store_true')
    parser.add_argument('--get-mic-card', action='store_true')
    parser.add_argument('--set-mic-volume', type=float, default=None)
    args = parser.parse_args()

    if args.select_mic:
        select_mic(auto_choice=args.auto_choice)
    if args.get_mic_id:
        print(get_mic_id())
    if args.get_mic_card:
        print(get_mic_sound_card_number())
    if args.set_mic_volume is not None:
        update_current_mic_volume(max(0, min(100, args.set_mic_volume)))
