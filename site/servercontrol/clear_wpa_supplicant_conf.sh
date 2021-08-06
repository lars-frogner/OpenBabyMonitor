#!/bin/bash
echo "country=$BM_NW_COUNTRY_CODE
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
p2p_disabled=1
update_config=1
" > /etc/wpa_supplicant/wpa_supplicant.conf
