#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

NEW_PASSWORD="$1"

if [[ -z "$NEW_PASSWORD" ]]; then
    echo 'No password provided' 1>&4
    exit 1
fi

echo "$BM_USER:$NEW_PASSWORD" | chpasswd
