#!/bin/bash
set -e
source $BM_SERVERCONTROL_DIR/redirection.sh

NEW_COUNTRY_CODE=$1

if [[ -z "$NEW_COUNTRY_CODE" ]]; then
    echo 'No country code provided' 1>&4
    exit 1
fi

OLD_COUNTRY_CODE=$BM_NW_COUNTRY_CODE

if [[ "$NEW_COUNTRY_CODE" = "$OLD_COUNTRY_CODE" ]]; then
    exit
fi

sudo sed -i "s/^country=.*$/country=$NEW_COUNTRY_CODE/g" /etc/wpa_supplicant/wpa_supplicant.conf

sed -i "s/country_code=.*$/country_code=$NEW_COUNTRY_CODE/g" $BM_NW_AP_DIR/etc/hostapd/hostapd.conf

sudo raspi-config nonint do_wifi_country $NEW_COUNTRY_CODE

$BM_SERVERCONTROL_DIR/set_env_variable BM_NW_COUNTRY_CODE $NEW_COUNTRY_CODE
