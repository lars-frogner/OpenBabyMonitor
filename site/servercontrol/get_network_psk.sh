#!/bin/bash
SSID="$1"
PASSWORD="$2"

if [[ -z "$SSID" || -z "$PASSWORD" ]]; then
    echo 'Missing SSID or password'
    exit 1
fi

wpa_passphrase "$SSID" "$PASSWORD" | sed -n "s/^\s*psk=\([a-z0-9]*\)$/\1/p"
