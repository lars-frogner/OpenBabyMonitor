#!/bin/bash

NEW_PASSWORD="$1"
>&2 echo 'New password:'
>&2 echo $NEW_PASSWORD
>&2 echo 'SSID:'
>&2 echo $BM_NW_SSID

NEW_PSK=$($BM_SERVERCONTROL_DIR/get_network_psk.sh "$BM_NW_SSID" "$NEW_PASSWORD")
>&2 echo $NEW_PSK
sed -i "s/wpa_psk=.*/wpa_psk=$NEW_PSK/g" $BM_NW_AP_DIR/etc/hostapd/hostapd.conf
