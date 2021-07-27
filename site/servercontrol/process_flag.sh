#!/bin/bash
set -e

SCRIPT_DIR=$(dirname $(readlink -f $0))

AP_MODE_CMD=$SCRIPT_DIR/activate_ap_mode.sh
CLIENT_MODE_CMD=$SCRIPT_DIR/activate_client_mode.sh
REBOOT_CMD='systemctl reboot'
SHUTDOWN_CMD='shutdown -h now'

AP_MODE_FLAG=activate_ap_mode
CLIENT_MODE_FLAG=activate_client_mode
REBOOT_FLAG=reboot
SHUTDOWN_FLAG=shutdown

if [[ ! -f "$BM_SERVER_ACTION_FILE" ]]; then
    exit 0
fi

FLAG=$(cat $BM_SERVER_ACTION_FILE)
rm $BM_SERVER_ACTION_FILE

case $FLAG in

  $AP_MODE_FLAG)
    eval $AP_MODE_CMD
    ;;

  $CLIENT_MODE_FLAG)
    eval $CLIENT_MODE_CMD
    ;;

  $REBOOT_FLAG)
    eval $REBOOT_CMD
    ;;

  $SHUTDOWN_FLAG)
    eval $SHUTDOWN_CMD
    ;;

  *)
    echo "Invalid flag $FLAG" 1>&2
    ;;
esac
