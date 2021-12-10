#!/bin/bash
set -e
source $BM_SERVERCONTROL_DIR/redirection.sh

# If called manually over SSH, make sure the script is allowed
# to finish even if the terminal disconnects by calling as
# nohup $BM_SERVERCONTROL_DIR/activate_client_mode.sh &

sudo systemctl stop dnsmasq 1>&3 2>&4
sudo systemctl stop hostapd 1>&3 2>&4
sudo systemctl disable dnsmasq 1>&3 2>&4
sudo systemctl disable hostapd 1>&3 2>&4
sudo rm -f /etc/dhcpcd.conf /etc/hosts
sudo ln -s {$BM_NW_CLIENT_DIR,}/etc/dhcpcd.conf
sudo ln -s {$BM_NW_CLIENT_DIR,}/etc/hosts
sudo systemctl daemon-reload 1>&3 2>&4
sudo service dhcpcd restart 1>&3 2>&4
