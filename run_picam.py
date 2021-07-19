import os
import argparse
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('--fullhd', action='store_true')
parser.add_argument('--noaudio', action='store_true')
args = parser.parse_args()

MIC_ID = os.environ['MIC_ID']

resolution_args = ['-w', '1920', '-h', '1080'] if args.fullhd else []
audio_args = ['--noaudio'] if args.noaudio else []

with open('/var/log/picam.log', 'a') as log_file:
    subprocess.check_call([
        '/home/pi/picam/picam', '-o', '/run/shm/hls', '--time', '--alsadev',
        MIC_ID
    ] + resolution_args + audio_args,
                          stdout=log_file,
                          stderr=log_file)
