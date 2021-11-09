#!/bin/bash

NEW_PASSWORD="$1"

NEW_PSK=$($BM_SERVERCONTROL_DIR/get_network_psk.sh "$BM_NW_SSID" "$NEW_PASSWORD")
sed -i "s/wpa_psk=.*/wpa_psk=$NEW_PSK/g" $BM_NW_AP_DIR/etc/hostapd/hostapd.conf
