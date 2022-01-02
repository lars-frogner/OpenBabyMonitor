#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

cat $BM_CRONTAB_FILE | crontab -

$SCRIPT_DIR/startup.py
