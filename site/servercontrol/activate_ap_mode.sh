#!/bin/bash
set -e
source $BM_SERVERCONTROL_DIR/redirection.sh

# If called manually over SSH, make sure the script is allowed
# to finish even if the terminal disconnects by calling as
# nohup $BM_SERVERCONTROL_DIR/activate_ap_mode.sh &

#sudo systemctl stop wpa_supplicant 1>&3 2>&4
#sudo systemctl disable wpa_supplicant 1>&3 2>&4
sudo rm -f /etc/dhcpcd.conf /etc/hosts
sudo ln -s {$BM_NW_AP_DIR,}/etc/dhcpcd.conf
sudo ln -s {$BM_NW_AP_DIR,}/etc/hosts
sudo systemctl daemon-reload 1>&3 2>&4
sudo service dhcpcd restart 1>&3 2>&4
sudo systemctl enable hostapd 1>&3 2>&4
sudo systemctl enable dnsmasq 1>&3 2>&4
sudo systemctl start hostapd 1>&3 2>&4
sudo systemctl start dnsmasq 1>&3 2>&4
