#!/bin/bash

$BM_SERVERCONTROL_DIR/inform_camera_connected.sh
$BM_SERVERCONTROL_DIR/process_flag.sh
$BM_SERVERCONTROL_DIR/monitor_flag.sh &
