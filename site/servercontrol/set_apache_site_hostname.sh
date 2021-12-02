#!/bin/bash

HOSTNAME=${1:-$BM_HOSTNAME}

CONF_FILE=/etc/apache2/sites-available/babymonitor.conf
sudo sed -i "s/^    ServerName .*\.local$/    ServerName $HOSTNAME\.local/g" $CONF_FILE
sudo sed -i "s/^    ServerAlias .*\.\*$/    ServerAlias $HOSTNAME\.\*/g" $CONF_FILE
