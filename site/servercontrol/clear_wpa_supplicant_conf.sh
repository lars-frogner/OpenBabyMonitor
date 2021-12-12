#!/bin/bash
echo "country=$BM_NW_COUNTRY_CODE
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
" | sudo tee /etc/wpa_supplicant/wpa_supplicant.conf
