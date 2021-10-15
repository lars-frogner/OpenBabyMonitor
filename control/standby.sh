#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))
rm -rf $BM_AUDIO_STREAM_DIR $BM_PICAM_STREAM_DIR

$SCRIPT_DIR/standby.py
