#!/bin/bash
set -e
source $BM_SERVERCONTROL_DIR/redirection.sh

NEW_AP_CHANNEL=$1

if [[ -z "$NEW_AP_CHANNEL" ]]; then
    echo 'No channel provided' 1>&4
    exit 1
fi

sed -i "s/channel=.*$/channel=$NEW_AP_CHANNEL/g" $BM_NW_AP_DIR/etc/hostapd/hostapd.conf

$BM_SERVERCONTROL_DIR/set_env_variable.sh BM_NW_AP_CHANNEL $NEW_AP_CHANNEL
