#!/bin/bash

systemctl is-active --quiet hostapd
HOSTAPD_STATE="$?"
systemctl is-active --quiet dnsmasq
DNSMASQ_STATE="$?"

if [[ "$HOSTAPD_STATE" = "0" && "$DNSMASQ_STATE" = "0" ]]; then
    echo 1
else
    echo 0
fi
