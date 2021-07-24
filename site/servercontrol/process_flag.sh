#!/bin/bash
set -e

FLAG_PATH=.flag

AP_MODE_CMD=./activate_ap_mode.sh
CLIENT_MODE_CMD=./activate_client_mode.sh
REBOOT_CMD=systemctl reboot

AP_MODE_FLAG=activate_ap_mode
CLIENT_MODE_FLAG=activate_client_mode
REBOOT_FLAG=reboot

if [[ ! -f "$FLAG_PATH" ]]; then
    exit 0
fi

FLAG=$(cat $FLAG_PATH)
rm $FLAG_PATH

case $FLAG in

  $AP_MODE_FLAG)
    $AP_MODE_CMD
    ;;

  $CLIENT_MODE_FLAG)
    $CLIENT_MODE_CMD
    ;;

  $REBOOT_FLAG)
    $REBOOT_CMD
    ;;

  *)
    echo "Invalid flag $FLAG" 1>&2
    ;;
esac
