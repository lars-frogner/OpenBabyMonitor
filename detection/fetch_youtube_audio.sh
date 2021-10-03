#!/bin/bash

ID="$1"
START="$2"
END="$3"
DIRECTORY="$4"
ffmpeg -hide_banner -loglevel error -ss $START -to $END -i "$(youtube-dl -f best --get-url https://youtu.be/$ID)" -vn -acodec pcm_s16le -ac 1 -ar 8000 $DIRECTORY/$ID.wav
