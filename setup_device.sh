#!/bin/bash
set -e

if [[ "$(whoami)" != "root" ]]; then
    echo "Error: this script must be run as root"
    exit 1
fi

BM_DIR=$(dirname $(readlink -f $0))
source $BM_DIR/config/setup_config.env

if [[ "$BM_USER" != "pi" ]]; then
    echo 'Warning: Only the pi user (and root) can use the ALSA sound card driver (arecord etc.) and the VideoCore GPU interface'
fi

BM_LOCALE=en_GB.UTF-8

source <(sed "s/INTERACTIVE=True/INTERACTIVE=False/g" /usr/bin/raspi-config)

if [[ -z "$BM_DEVICE_PW" ]]; then
    passwd $USER
else
    echo "$USER:$BM_DEVICE_PW" | chpasswd
fi

do_hostname $BM_HOSTNAME

do_wifi_country $BM_COUNTRY_CODE

do_change_locale $BM_LOCALE
echo -e "LC_ALL=\"$BM_LOCALE\"\nLANGUAGE=\"$BM_LOCALE\"" >> /etc/default/locale

do_change_timezone $BM_TIMEZONE

do_camera 0
