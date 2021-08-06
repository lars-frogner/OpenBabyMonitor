#!/bin/bash
set -x
set -e

source $BM_ENV_EXPORTS_PATH

BM_NW_AP_DIR=~/.netconf_ap # Directory for configurations files set up for wireless access point mode
BM_NW_CLIENT_DIR=~/.netconf_client # Directory for configurations files set up for wireless client mode
BM_NW_ORIG_DIR=~/.netconf_orig # For backup of original configuration files
BM_NW_AP_IP_ROOT=192.168.4
BM_NW_INTERFACE=wlan0
BM_NW_CHANNEL=7
BM_NW_SSID=babymonitor
BM_NW_COUNTRY_CODE=NO

SETUP_ENV=true
if [[ "$SETUP_ENV" = true ]]; then
    echo "export BM_NW_AP_DIR=$BM_NW_AP_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_CLIENT_DIR=$BM_NW_CLIENT_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_INTERFACE=$BM_NW_INTERFACE" >> $BM_ENV_EXPORTS_PATH

    # Copy environment variables (without 'export') into environment file for services and PHP
    ENV_VAR_EXPORTS=$(cat $BM_ENV_EXPORTS_PATH)
    echo "${ENV_VAR_EXPORTS//'export '/}" > $BM_ENV_PATH
fi

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
mkdir -v $BM_NW_AP_DIR $BM_NW_CLIENT_DIR $BM_NW_ORIG_DIR

# Copy and backup dhcpcd.conf
mkdir -v {$BM_NW_AP_DIR,$BM_NW_CLIENT_DIR,$BM_NW_ORIG_DIR}/etc
cp -v {,$BM_NW_AP_DIR}/etc/dhcpcd.conf
cp -v {,$BM_NW_CLIENT_DIR}/etc/dhcpcd.conf
cp -v {,$BM_NW_ORIG_DIR}/etc/dhcpcd.conf

# Set static IP for access point mode
echo "
interface $BM_NW_INTERFACE
static ip_address=$BM_NW_AP_IP_ROOT.1/24
nohook wpa_supplicant
" >> $BM_NW_AP_DIR/etc/dhcpcd.conf

# Set static IP for client mode
STATIC_IP=false
if [[ "$STATIC_IP" = true ]]; then
    CLIENT_IP=$(ip -o -4 addr list $BM_NW_INTERFACE | awk '{print $4}' | cut -d/ -f1)
    ROUTER_IP=$(ip r | grep default | sed -n "s/^default via \([0-9\.]*\).*$/\1/p")
    DNS_IP=$(sudo sed -n "s/^nameserver \([0-9\.]*\).*$/\1/p" /etc/resolv.conf | head -n 1)
    echo "
    interface $BM_NW_INTERFACE
    static ip_address=$CLIENT_IP/24
    static routers=$ROUTER_IP
    static domain_name_servers=$DNS_IP
    " >> $BM_NW_CLIENT_DIR/etc/dhcpcd.conf
fi

# Backup dnsmasq.conf
sudo mv -v {,$BM_NW_ORIG_DIR}/etc/dnsmasq.conf

# Set listening interface, served IP range, lease time, domain and address for dnsmasq
IP_START=2
IP_END=20
LEASE_TIME=24h
ALIAS=babymonitor.local
echo "interface=$BM_NW_INTERFACE
dhcp-range=$BM_NW_AP_IP_ROOT.$IP_START,$BM_NW_AP_IP_ROOT.$IP_END,255.255.255.0,$LEASE_TIME
domain=wlan
address=/$ALIAS/$BM_NW_AP_IP_ROOT.1
" > $BM_NW_AP_DIR/etc/dnsmasq.conf

# dnsmasq is only used in access point mode, so this symlink can remain also in client mode
sudo ln -sv {$BM_NW_AP_DIR,}/etc/dnsmasq.conf

# Make sure the wireless device is not blocked
sudo rfkill unblock wlan

mkdir $BM_NW_AP_DIR/etc/hostapd

# Configure hostapd
set +x # Hide password
echo "country_code=$BM_NW_COUNTRY_CODE
interface=$BM_NW_INTERFACE
ssid=$BM_NW_SSID
hw_mode=g
channel=$BM_NW_CHANNEL
macaddr_acl=0
auth_algs=1
ignore_broadcast_ssid=0
wpa=2
wpa_passphrase=$PASSWORD
wpa_key_mgmt=WPA-PSK
wpa_pairwise=TKIP
rsn_pairwise=CCMP
" > $BM_NW_AP_DIR/etc/hostapd/hostapd.conf
set -x

# hostapd is only used in access point mode, so this symlink can remain also in client mode
sudo ln -sv {$BM_NW_AP_DIR,}/etc/hostapd/hostapd.conf

# Disable IPv6 hostname resolution
echo "
net.ipv6.conf.all.disable_ipv6=1
" | sudo tee /etc/sysctl.conf

# Check connection every 10 minutes
(crontab -l; echo "*/10 * * * * $BM_SERVERCONTROL_DIR/ensure_connection.sh") | crontab -

echo "Start access point mode by running the following command:
nohup $BM_SERVERCONTROL_DIR/activate_ap_mode.sh &

Start client mode by running the following command:
nohup $BM_SERVERCONTROL_DIR/activate_client_mode.sh &"
