#!/bin/bash
set -e
source $BM_SERVERCONTROL_DIR/redirection.sh

NEW_SSID="$1"
NEW_PASSWORD="$2"

if [[ -z "$NEW_SSID" ]]; then
    echo 'No SSID provided' 1>&4
    exit 1
fi

if [[ -z "$NEW_PASSWORD" ]]; then
    echo 'No password provided' 1>&4
    exit 1
fi

CONF_FILE=$BM_NW_AP_DIR/etc/hostapd/hostapd.conf

NEW_PSK=$($BM_SERVERCONTROL_DIR/get_network_psk.sh "$NEW_SSID" "$NEW_PASSWORD")
sed -i "s/wpa_psk=.*$/wpa_psk=$NEW_PSK/g" $CONF_FILE

ESCAPED_SSID=$(printf '%s\n' "$NEW_SSID" | sed -e 's/[\/&]/\\&/g')
sed -i "s/^ssid=.*$/ssid=$ESCAPED_SSID/g" $CONF_FILE

$BM_SERVERCONTROL_DIR/set_env_variable.sh BM_NW_AP_SSID $NEW_SSID
