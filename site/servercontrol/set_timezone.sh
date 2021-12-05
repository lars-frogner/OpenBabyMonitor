#!/bin/bash
set -e
source $BM_SERVERCONTROL_DIR/redirection.sh

NEW_TIMEZONE="$1"

if [[ -z "$NEW_TIMEZONE" ]]; then
    echo 'No time zone provided' 1>&4
    exit 1
fi

OLD_TIMEZONE=$BM_TIMEZONE

if [[ "$NEW_TIMEZONE" = "$OLD_TIMEZONE" ]]; then
    exit
fi

$BM_SERVERCONTROL_DIR/set_php_timezone.sh "$NEW_TIMEZONE"

sudo raspi-config nonint do_change_timezone "$NEW_TIMEZONE"

$BM_SERVERCONTROL_DIR/set_env_variable.sh BM_TIMEZONE $NEW_TIMEZONE
