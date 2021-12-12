#!/bin/bash
set -e

AP_ACTIVE=$($BM_SERVERCONTROL_DIR/access_point_active.sh)
if [[ "$AP_ACTIVE" = "1" ]]; then
    echo "Error: need internet access to prepare creation of system image"
    exit 1
fi

sudo apt -y install gdisk zip

cd
git clone https://github.com/seamusdemora/RonR-RPi-image-utils.git
chmod +x RonR-RPi-image-utils/image-backup

$BM_SERVERCONTROL_DIR/remove_all_known_networks.sh
