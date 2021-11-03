#!/bin/bash
set -x
set -e

BM_DIR=$(dirname $(readlink -f $0))
source $BM_DIR/config/setup_config.env

BM_LOCALE=en_GB.UTF-8

source <(sed "s/INTERACTIVE=True/INTERACTIVE=False/g" /usr/bin/raspi-config)

do_change_pass

do_hostname $BM_HOSTNAME

do_wifi_country $BM_COUNTRY_CODE

do_change_locale $BM_LOCALE
echo -e "\nexport LC_ALL=$BM_LOCALE\nexport LANGUAGE=$BM_LOCALE\n" >> /home/pi/.bashrc

do_change_timezone $BM_TIMEZONE

do_camera 1

reboot
