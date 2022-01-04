#!/bin/bash
set -e
source $BM_SERVERCONTROL_DIR/redirection.sh

NEW_HOSTNAME=$1

if [[ -z "$NEW_HOSTNAME" ]]; then
    echo 'No hostname provided' 1>&4
    exit 1
fi

OLD_HOSTNAME=$BM_HOSTNAME

$BM_SERVERCONTROL_DIR/create_ssl_key.sh $NEW_HOSTNAME 1>&3 2>&4

$BM_SERVERCONTROL_DIR/set_apache_site_hostname.sh $NEW_HOSTNAME 1>&3 2>&4

echo $NEW_HOSTNAME | sudo tee /etc/hostname

for DIR in $BM_NW_AP_DIR $BM_NW_CLIENT_DIR
do
    sed -i "s/[[:space:]][[:space:]]*$OLD_HOSTNAME$/     $NEW_HOSTNAME/g" $DIR/etc/hosts
done

$BM_SERVERCONTROL_DIR/set_env_variable.sh BM_HOSTNAME $NEW_HOSTNAME

sudo reboot
