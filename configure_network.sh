#!/bin/bash
set -x
set -e

BM_DIR=$(dirname $(readlink -f $0))
SERVER_CONTROL_DIR=$BM_DIR/site/servercontrol

AP_DIR=~/.netconf_ap # Directory for configurations files set up for wireless access point mode
CLIENT_DIR=~/.netconf_client # Directory for configurations files set up for wireless client mode
ORIG_DIR=~/.netconf_orig # For backup of original configuration files
AP_IP_ROOT=192.168.4
INTERFACE=wlan0
CHANNEL=7
SSID=babymonitor

stty -echo
printf "New access point password (8-64 characters): "
read PASSWORD
stty echo
printf '\n'

# Install access point software
sudo apt -y install hostapd

# Unmask hostapd service so it can be enabled
sudo systemctl unmask hostapd

# Install network management software
sudo apt -y install dnsmasq

# Create directory for modified configuration files
mkdir -v $AP_DIR $CLIENT_DIR $ORIG_DIR

# Copy and backup dhcpcd.conf
mkdir -v {$AP_DIR,$CLIENT_DIR,$ORIG_DIR}/etc
cp -v {,$AP_DIR}/etc/dhcpcd.conf
cp -v {,$CLIENT_DIR}/etc/dhcpcd.conf
cp -v {,$ORIG_DIR}/etc/dhcpcd.conf

# Set static IP for access point mode
echo "
interface $INTERFACE
static ip_address=$AP_IP_ROOT.1/24
nohook wpa_supplicant
" >> $AP_DIR/etc/dhcpcd.conf

# Set static IP for client mode
CLIENT_IP=$(ip -o -4 addr list $INTERFACE | awk '{print $4}' | cut -d/ -f1)
ROUTER_IP=$(ip r | grep default | sed -n "s/^default via \([0-9\.]*\).*$/\1/p")
DNS_IP=$(sudo sed -n "s/^nameserver \([0-9\.]*\).*$/\1/p" /etc/resolv.conf | head -n 1)
echo "
interface $INTERFACE
static ip_address=$CLIENT_IP/24
static routers=$ROUTER_IP
static domain_name_servers=$DNS_IP
" >> $CLIENT_DIR/etc/dhcpcd.conf

# Backup dnsmasq.conf
sudo mv -v {,$ORIG_DIR}/etc/dnsmasq.conf

# Set listening interface, served IP range, lease time, domain and address for dnsmasq
IP_START=2
IP_END=20
LEASE_TIME=24h
ALIAS=babymonitor.local
echo "interface=$INTERFACE
dhcp-range=$AP_IP_ROOT.$IP_START,$AP_IP_ROOT.$IP_END,255.255.255.0,$LEASE_TIME
domain=wlan
address=/$ALIAS/$AP_IP_ROOT.1
" > $AP_DIR/etc/dnsmasq.conf

# dnsmasq is only used in access point mode, so this symlink can remain also in client mode
sudo ln -sv {$AP_DIR,}/etc/dnsmasq.conf

# Make sure the wireless device is not blocked
sudo rfkill unblock wlan

mkdir $AP_DIR/etc/hostapd

# Configure hostapd
set +x # Hide password
echo "country_code=NO
interface=$INTERFACE
ssid=$SSID
hw_mode=g
channel=$CHANNEL
macaddr_acl=0
auth_algs=1
ignore_broadcast_ssid=0
wpa=2
wpa_passphrase=$PASSWORD
wpa_key_mgmt=WPA-PSK
wpa_pairwise=TKIP
rsn_pairwise=CCMP
" > $AP_DIR/etc/hostapd/hostapd.conf
set -x

# hostapd is only used in access point mode, so this symlink can remain also in client mode
sudo ln -sv {$AP_DIR,}/etc/hostapd/hostapd.conf

# Disable IPv6 hostname resolution
echo "
net.ipv6.conf.all.disable_ipv6=1
" | sudo tee /etc/sysctl.conf

# Generate script for activating access point mode
echo "#!/bin/bash
set -e

# If called manually over SSH, make sure the script is allowed
# to finish even if the terminal disconnects by calling as
# nohup $SERVER_CONTROL_DIR/activate_ap_mode.sh &

AP_DIR=$AP_DIR

sudo rm -f /etc/dhcpcd.conf
sudo ln -s {\$AP_DIR,}/etc/dhcpcd.conf
sudo systemctl daemon-reload
sudo service dhcpcd restart
sudo systemctl enable dnsmasq
sudo systemctl enable hostapd
sudo systemctl start dnsmasq
sudo systemctl start hostapd
" > $SERVER_CONTROL_DIR/activate_ap_mode.sh
chmod +x $SERVER_CONTROL_DIR/activate_ap_mode.sh

# Generate script for activating client mode
echo "#!/bin/bash
set -e

# If called manually over SSH, make sure the script is allowed
# to finish even if the terminal disconnects by calling as
# nohup $SERVER_CONTROL_DIR/activate_client_mode.sh &

CLIENT_DIR=$CLIENT_DIR

sudo systemctl stop dnsmasq
sudo systemctl stop hostapd
sudo systemctl disable dnsmasq
sudo systemctl disable hostapd
sudo rm -f /etc/dhcpcd.conf
sudo ln -s {\$CLIENT_DIR,}/etc/dhcpcd.conf
sudo systemctl daemon-reload
sudo service dhcpcd restart
" > $SERVER_CONTROL_DIR/activate_client_mode.sh
chmod +x $SERVER_CONTROL_DIR/activate_client_mode.sh

echo "#!/bin/bash

if [[ -z \"$(ip r | grep default)\" ]]; then
    echo 0
    exit
fi
ROUTER_IP=\$(ip r | grep default | sed -n \"s/^default via \([0-9\.]*\).*$/\1/p\")
ping -c 1 \"\$ROUTER_IP\" > /dev/null 2>&1
if [[ \"\$?\" = \"0\" ]]; then
    echo 1
else
    echo 0
fi
" > $SERVER_CONTROL_DIR/connected_to_external_network.sh
chmod +x $SERVER_CONTROL_DIR/connected_to_external_network.sh

echo "#!/bin/bash

systemctl is-active --quiet hostapd
HOSTAPD_STATE=\"\$?\"
systemctl is-active --quiet dnsmasq
DNSMASQ_STATE=\"\$?\"

if [[ \"\$HOSTAPD_STATE\" = \"0\" && \"\$DNSMASQ_STATE\" = \"0\" ]]; then
    echo 1
else
    echo 0
fi
" > $SERVER_CONTROL_DIR/access_point_active.sh
chmod +x $SERVER_CONTROL_DIR/access_point_active.sh

# Start in access point mode
echo "Start access point mode by running the following command:
nohup $SERVER_CONTROL_DIR/activate_ap_mode.sh &

Start client mode by running the following command:
nohup $SERVER_CONTROL_DIR/activate_client_mode.sh &"
