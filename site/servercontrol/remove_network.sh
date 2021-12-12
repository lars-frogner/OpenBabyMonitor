#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

SSID="$1"

if [[ -z "$SSID" ]]; then
    echo 'No SSID provided' 1>&4
    $BM_SERVERCONTROL_DIR/write_result.sh 1
    exit 1
fi

IS_AP=$($BM_SERVERCONTROL_DIR/access_point_active.sh)

if [[ "$IS_AP" = "1" ]]; then
    $BM_SERVERCONTROL_DIR/activate_client_mode.sh
fi

NETWORK_ID=$($BM_SERVERCONTROL_DIR/get_network_id.sh "$SSID")
if [[ ! -z "$NETWORK_ID" ]]; then
    sudo wpa_cli -i $BM_NW_INTERFACE remove_network $NETWORK_ID 1>&3 2>&4
    sudo wpa_cli -i $BM_NW_INTERFACE save_config 1>&3 2>&4
    sudo wpa_cli -i $BM_NW_INTERFACE reconfigure 1>&3 2>&4
fi

ALL_NETWORK_IDS="$(sudo wpa_cli -i $BM_NW_INTERFACE list_networks | tail -n +2 | cut -c-1)"

$BM_SERVERCONTROL_DIR/write_result.sh 0

if [[ "$IS_AP" = "1" || -z "$ALL_NETWORK_IDS" ]]; then
    $BM_SERVERCONTROL_DIR/activate_ap_mode.sh
fi
