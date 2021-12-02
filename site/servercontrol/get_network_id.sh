#!/bin/bash
SSID="$1"

if [[ -z "$SSID" ]]; then
    echo 'No SSID provided' 1>&2
    exit 1
fi

NETWORK_INFO="$(sudo wpa_cli -i $BM_NW_INTERFACE list_networks | grep "\s$SSID\s")"
echo "${NETWORK_INFO:0:1}"
