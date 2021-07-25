#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

# Delete any existing HLS files to avoid confusing the video player
rm -f $BM_PICAM_STREAM_DIR/*

$SCRIPT_DIR/videostream.py
