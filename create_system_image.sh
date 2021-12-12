#!/bin/bash
set -e

AP_ACTIVE=$($BM_SERVERCONTROL_DIR/access_point_active.sh)
if [[ "$AP_ACTIVE" = "0" ]]; then
    echo "Error: access point must be active before creating system image"
    exit 1
fi

TAG=${1:-}

$BM_SERVERCONTROL_DIR/clear_wpa_supplicant_conf.sh

sudo ~/RonR-RPi-image-utils/image-backup --initial "/media/babymonitor$TAG.img"
