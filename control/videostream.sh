#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

# Make sure required directories exist in /run/shm
$SCRIPT_DIR/prepare_video_streaming.sh

$SCRIPT_DIR/videostream.py
