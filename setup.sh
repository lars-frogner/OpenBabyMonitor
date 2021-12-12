#!/bin/bash
set -e

SCRIPT_DIR=$(dirname $(readlink -f $0))

sudo BM_DEVICE_PW="$BM_DEVICE_PW" $SCRIPT_DIR/setup_device.sh
BM_SITE_PW="$BM_SITE_PW" $SCRIPT_DIR/setup_server.sh
BM_AP_PW="$BM_AP_PW" $SCRIPT_DIR/setup_network.sh

sudo reboot
