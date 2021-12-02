#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

NEW_PASSWORD="$1"

if [[ -z "$NEW_PASSWORD" ]]; then
    echo 'No password provided' 1>&4
    exit 1
fi

NEW_PSK=$($BM_SERVERCONTROL_DIR/get_network_psk.sh "$BM_NW_AP_SSID" "$NEW_PASSWORD")
sed -i "s/wpa_psk=.*/wpa_psk=$NEW_PSK/g" $BM_NW_AP_DIR/etc/hostapd/hostapd.conf
