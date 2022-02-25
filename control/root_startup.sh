#!/bin/bash

rm -f $BM_CONTROL_TIME_SYNCED_FILE
$BM_DIR/control/mic.py --select-mic --auto-choice
$BM_SERVERCONTROL_DIR/inform_camera_connected.sh
$BM_SERVERCONTROL_DIR/process_flag.sh
$BM_SERVERCONTROL_DIR/monitor_flag.sh &
$BM_SERVERCONTROL_DIR/monitor_mic_connection_events.sh &
