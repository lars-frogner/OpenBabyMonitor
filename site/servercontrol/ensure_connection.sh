#!/bin/bash

ACCESS_POINT_ACTIVE=$($BM_SERVERCONTROL_DIR/access_point_active.sh)

if [[ "$ACCESS_POINT_ACTIVE" = "0" ]]; then

    CONNECTED_TO_NETWORK_FIRST=$($BM_SERVERCONTROL_DIR/connected_to_external_network.sh)
    sleep 1m
    CONNECTED_TO_NETWORK_SECOND=$($BM_SERVERCONTROL_DIR/connected_to_external_network.sh)

    if [[ "$CONNECTED_TO_NETWORK_FIRST" = "0" && "$CONNECTED_TO_NETWORK_SECOND" = "0" ]]; then
        $BM_SERVERCONTROL_DIR/perform_action.sh activate_ap_mode
    fi
fi
