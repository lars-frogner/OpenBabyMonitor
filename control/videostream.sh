#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

# Make sure required directories exist in /run/shm
$BM_PICAM_DIR/create_sharedmem_dirs.sh

# Delete any existing HLS files to avoid confusing the video player
rm -f $BM_PICAM_STREAM_DIR/*

$SCRIPT_DIR/videostream.py
