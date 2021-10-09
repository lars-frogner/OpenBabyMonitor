#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

# Make sure required directories exist in /run/shm
$BM_PICAM_DIR/create_sharedmem_dirs.sh

$SCRIPT_DIR/videostream.py
