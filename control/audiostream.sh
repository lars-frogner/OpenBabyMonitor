#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

# Make sure required directories exist in /run/shm
$SCRIPT_DIR/prepare_audio_streaming.sh

$SCRIPT_DIR/audiostream.py
