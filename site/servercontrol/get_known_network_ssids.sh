#!/bin/bash
sed -n 's/^[[:space:]]*ssid="\(.*\)"$/\1/p' /etc/wpa_supplicant/wpa_supplicant.conf
