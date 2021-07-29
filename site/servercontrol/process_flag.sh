#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

AP_MODE_CMD=$SCRIPT_DIR/activate_ap_mode.sh
CLIENT_MODE_CMD=$SCRIPT_DIR/activate_client_mode.sh
REBOOT_CMD='systemctl reboot'
SHUTDOWN_CMD='shutdown -h now'
TIMESTAMP_CMD='date +%s -s @'

AP_MODE_FLAG=activate_ap_mode
CLIENT_MODE_FLAG=activate_client_mode
REBOOT_FLAG=reboot
SHUTDOWN_FLAG=shutdown
TIMESTAMP_FLAG=set_timestamp

if [[ ! -f "$BM_SERVER_ACTION_FILE" ]]; then
    exit 0
fi

CONTENT=$(cat $BM_SERVER_ACTION_FILE)
rm -f $BM_SERVER_ACTION_FILE

read -ra SPLITTED_CONTENT -d '' <<<"$CONTENT"
FLAG="${SPLITTED_CONTENT[0]}"
TIMESTAMP="${SPLITTED_CONTENT[1]}"

if [[ ! -z "$TIMESTAMP" ]]; then
    case $TIMESTAMP in
      ''|*[!0-9]*)
        echo "Invalid timestamp $TIMESTAMP" 1>&2
        exit 1
        ;;
      *)
        ;;
    esac
fi

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

  $TIMESTAMP_FLAG)
    eval "${TIMESTAMP_CMD}${TIMESTAMP}"
    ;;

  *)
    echo "Invalid flag $FLAG" 1>&2
    exit 1
    ;;
esac
