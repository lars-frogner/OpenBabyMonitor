#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

if [[ -z "$(ip r | grep default)" ]]; then
    echo 0
    exit
fi
ROUTER_IP=$(ip r | grep default | sed -n "s/^default via \([0-9\.]*\).*$/\1/p")
ping -c 20 "$ROUTER_IP" 1>&3 2>&4
if [[ "$?" = "0" ]]; then
    echo 1
else
    echo 0
fi
