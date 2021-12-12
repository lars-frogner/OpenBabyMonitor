#!/bin/bash
set -e

while IFS= read -r SSID; do
    $BM_SERVERCONTROL_DIR/remove_network.sh "$SSID"
done <<< "$($BM_SERVERCONTROL_DIR/get_known_network_ssids.sh)"
