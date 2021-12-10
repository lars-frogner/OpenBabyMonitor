#!/bin/bash
source /home/pi/babymonitor/env/envvar_exports

ACCESS_POINT_ACTIVE=$($BM_SERVERCONTROL_DIR/access_point_active.sh)

if [[ "$ACCESS_POINT_ACTIVE" = "0" ]]; then

    CONNECTED_TO_NETWORK_FIRST=$($BM_SERVERCONTROL_DIR/connected_to_external_network.sh)
    sleep 1m
    CONNECTED_TO_NETWORK_SECOND=$($BM_SERVERCONTROL_DIR/connected_to_external_network.sh)

    if [[ "$CONNECTED_TO_NETWORK_FIRST" = "0" && "$CONNECTED_TO_NETWORK_SECOND" = "0" ]]; then
        NOW=$(date)
        echo "[$NOW] Connection lost, activating access point mode" >> $BM_SERVER_LOG_PATH
        $BM_SERVERCONTROL_DIR/perform_action.sh activate_ap_mode
    fi

elif [[ "$($BM_SERVERCONTROL_DIR/access_point_working.sh)" = "0" ]]; then

    NOW=$(date)
    echo "[$NOW] Access point not functional, activating client mode" >> $BM_SERVER_LOG_PATH
    $BM_SERVERCONTROL_DIR/perform_action.sh activate_client_mode
fi
