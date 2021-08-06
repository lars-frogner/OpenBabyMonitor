#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

AP_MODE_FLAG=activate_ap_mode
CLIENT_MODE_FLAG=activate_client_mode
REBOOT_FLAG=reboot
SHUTDOWN_FLAG=shutdown
TIMESTAMP_FLAG=set_timestamp
WIRELESS_SCAN_FLAG=scan_wireless_networks
CONNECT_FLAG=connect_to_network
REMOVE_NETWORK_FLAG=remove_network

if [[ ! -f "$BM_SERVER_ACTION_FILE" ]]; then
    exit
fi

declare -a 'ARGUMENTS=('"$(cat $BM_SERVER_ACTION_FILE)"')'

rm -f "$BM_SERVER_ACTION_FILE"

FLAG=${ARGUMENTS[0]}

case $FLAG in

  $AP_MODE_FLAG)
    $BM_SERVERCONTROL_DIR/activate_ap_mode.sh 1>&3 2>&4
    ;;

  $CLIENT_MODE_FLAG)
    $BM_SERVERCONTROL_DIR/activate_client_mode.sh 1>&3 2>&4
    ;;

  $REBOOT_FLAG)
    systemctl reboot 1>&3 2>&4
    ;;

  $SHUTDOWN_FLAG)
    shutdown -h now 1>&3 2>&4
    ;;

  $TIMESTAMP_FLAG)
    TIMESTAMP="${ARGUMENTS[1]}"
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
    date +%s -s @${TIMESTAMP} 1>&3 2>&4
    ;;

  $WIRELESS_SCAN_FLAG)
    $BM_SERVERCONTROL_DIR/scan_wireless_networks.sh 1>&3 2>&4
    ;;

  $CONNECT_FLAG)
    $BM_SERVERCONTROL_DIR/connect_to_network.sh "${ARGUMENTS[@]:1}" 1>&3 2>&4
    ;;

  $REMOVE_NETWORK_FLAG)
    $BM_SERVERCONTROL_DIR/remove_network.sh "${ARGUMENTS[@]:1}" 1>&3 2>&4
    ;;

  *)
    echo "Invalid flag $FLAG" 1>&2
    exit 1
    ;;
esac
