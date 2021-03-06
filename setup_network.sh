#!/bin/bash
set -e

# Generates network configurations for using the unit as a wireless
# access point and for connecting to an existing wireless network as a client (works
# for Raspbian Buster). The two modes of operation can be switched between using the
# activate_ap_mode.sh and activate_client_mode.sh scripts.

BM_DIR=$(dirname $(readlink -f $0))
source $BM_DIR/config/setup_config.env

BM_ENV_EXPORTS_PATH=$BM_DIR/env/envvar_exports
BM_ENV_PATH=$BM_DIR/env/envvars

if [[ ! -f "$BM_ENV_EXPORTS_PATH" ]]; then
    echo 'Error: setup_server.sh must be run before this script'
    exit 1
fi

if [[ "$(whoami)" != "$BM_USER" ]]; then
    echo "Error: this script must be run by user $BM_USER"
    exit 1
fi

source $BM_ENV_EXPORTS_PATH

BM_NW_AP_DIR=/home/$BM_USER/.netconf_ap # Directory for configurations files set up for wireless access point mode
BM_NW_CLIENT_DIR=/home/$BM_USER/.netconf_client # Directory for configurations files set up for wireless client mode
BM_NW_ORIG_DIR=/home/$BM_USER/.netconf_orig # For backup of original configuration files
BM_NW_AP_IP_ROOT=192.168.4
BM_NW_INTERFACE=wlan0
BM_NW_AP_SSID=$BM_HOSTNAME
BM_NW_AP_CHANNEL=$BM_AP_CHANNEL
BM_NW_COUNTRY_CODE=$BM_COUNTRY_CODE

SETUP_ENV=true
if [[ "$SETUP_ENV" = true ]]; then
    echo "export BM_HOSTNAME=$BM_HOSTNAME" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_AP_DIR=$BM_NW_AP_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_CLIENT_DIR=$BM_NW_CLIENT_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_AP_SSID=$BM_NW_AP_SSID" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_AP_CHANNEL=$BM_NW_AP_CHANNEL" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_COUNTRY_CODE=$BM_NW_COUNTRY_CODE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_NW_INTERFACE=$BM_NW_INTERFACE" >> $BM_ENV_EXPORTS_PATH

    # Copy environment variables (without 'export') into environment file for services and PHP
    ENV_VAR_EXPORTS=$(cat $BM_ENV_EXPORTS_PATH)
    echo "${ENV_VAR_EXPORTS//'export '/}" > $BM_ENV_PATH
fi

if [[ -z "$BM_AP_PW" ]]; then
    while : ; do
        stty -echo
        printf "New wireless access point password (8-63 characters): "
        read PASSWORD
        stty echo
        printf '\n'

        stty -echo
        printf "Repeat new password: "
        read PASSWORD_REPEAT
        stty echo
        printf '\n'

        if [[ "$PASSWORD" != "$PASSWORD_REPEAT" ]]; then
            echo 'The passwords did not match'
        else
            break
        fi
    done
else
    PASSWORD="$BM_AP_PW"
fi

# Install access point software
sudo apt -y install hostapd

# Unmask hostapd service so it can be enabled
sudo systemctl unmask hostapd

# Install network management software
sudo apt -y install dnsmasq

# Create directory for modified configuration files
mkdir -v $BM_NW_AP_DIR $BM_NW_CLIENT_DIR $BM_NW_ORIG_DIR

# Copy and backup dhcpcd.conf and hosts
mkdir -v {$BM_NW_AP_DIR,$BM_NW_CLIENT_DIR,$BM_NW_ORIG_DIR}/etc
cp -v {,$BM_NW_AP_DIR}/etc/dhcpcd.conf
cp -v {,$BM_NW_CLIENT_DIR}/etc/dhcpcd.conf
cp -v {,$BM_NW_ORIG_DIR}/etc/dhcpcd.conf
cp -v {,$BM_NW_AP_DIR}/etc/hosts
cp -v {,$BM_NW_CLIENT_DIR}/etc/hosts
cp -v {,$BM_NW_ORIG_DIR}/etc/hosts

# Set static IP for client mode (only relevant for accessing device over internet)
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

# Set static IP for access point mode
echo "
interface $BM_NW_INTERFACE
static ip_address=$BM_NW_AP_IP_ROOT.1/24
nohook wpa_supplicant
" >> $BM_NW_AP_DIR/etc/dhcpcd.conf

# Add entry for device hostname with the access point IP address in hosts file
echo "$BM_NW_AP_IP_ROOT.1     $BM_HOSTNAME" >> $BM_NW_AP_DIR/etc/hosts

# Backup dnsmasq.conf
sudo mv -v {,$BM_NW_ORIG_DIR}/etc/dnsmasq.conf

# Set listening interface, served IP range and lease time for dnsmasq
IP_START=2
IP_END=20
LEASE_TIME=24h
echo "interface=$BM_NW_INTERFACE
dhcp-range=$BM_NW_AP_IP_ROOT.$IP_START,$BM_NW_AP_IP_ROOT.$IP_END,255.255.255.0,$LEASE_TIME
" > $BM_NW_AP_DIR/etc/dnsmasq.conf

# dnsmasq is only used in access point mode, so this symlink can remain also in client mode
sudo ln -sv {$BM_NW_AP_DIR,}/etc/dnsmasq.conf

# Make sure the wireless device is not blocked
sudo rfkill unblock wlan

mkdir $BM_NW_AP_DIR/etc/hostapd

# Configure hostapd

# Obtain password hash
PSK=$($BM_SERVERCONTROL_DIR/get_network_psk.sh "$BM_NW_AP_SSID" "$PASSWORD")

echo "country_code=$BM_NW_COUNTRY_CODE
interface=$BM_NW_INTERFACE
ssid=$BM_NW_AP_SSID
hw_mode=g
channel=$BM_NW_AP_CHANNEL
macaddr_acl=0
auth_algs=1
ignore_broadcast_ssid=0
wpa=2
wpa_psk=$PSK
wpa_key_mgmt=WPA-PSK
wpa_pairwise=TKIP
rsn_pairwise=CCMP
" > $BM_NW_AP_DIR/etc/hostapd/hostapd.conf
sudo chown $BM_USER:$BM_WEB_GROUP $BM_NW_AP_DIR/etc/hostapd/hostapd.conf
chmod $BM_WRITE_PERMISSIONS $BM_NW_AP_DIR/etc/hostapd/hostapd.conf

# hostapd is only used in access point mode, so this symlink can remain also in client mode
sudo ln -sv {$BM_NW_AP_DIR,}/etc/hostapd/hostapd.conf

# Disable IPv6 hostname resolution
echo "
net.ipv6.conf.all.disable_ipv6=1
" | sudo tee /etc/sysctl.conf

# Check connection every 10 minutes
echo "*/10 * * * * $BM_SERVERCONTROL_DIR/ensure_connection.sh" >> $BM_CRONTAB_FILE

BM_SERVERCONTROL_DIR=$BM_SERVERCONTROL_DIR BM_NW_CLIENT_DIR=$BM_NW_CLIENT_DIR $BM_SERVERCONTROL_DIR/activate_client_mode.sh
