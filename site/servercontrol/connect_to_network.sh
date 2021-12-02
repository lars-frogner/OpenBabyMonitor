#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

args=( )
SAVE_NETWORK=0
while (( $# )); do
  case $1 in
    -s|--save)  SAVE_NETWORK=1 ;;
    -*)         printf 'Unknown option: %q\n\n' "$1" 1>&4
                exit 1 ;;
    *)          args+=( "$1" ) ;;
  esac
  shift
done
set -- "${args[@]}"

SSID="$1"
PASSWORD="$2"

if [[ -z "$SSID" ]]; then
    echo 'No SSID provided' 1>&4
    $BM_SERVERCONTROL_DIR/write_result.sh 1
    exit 1
fi

BM_SERVERCONTROL_DIR=$BM_SERVERCONTROL_DIR

IS_ACCESS_POINT=$($BM_SERVERCONTROL_DIR/access_point_active.sh)

# Make sure we are in client mode
if [[ "$IS_ACCESS_POINT" = "1" ]]; then
    $BM_SERVERCONTROL_DIR/activate_client_mode.sh
else
    CONNECTED_SSID="$($BM_SERVERCONTROL_DIR/get_connected_network_ssid.sh)"
fi

# Nothing to do if we are already connected to the network
if [[ "$SSID" = "$CONNECTED_SSID" ]]; then
    $BM_SERVERCONTROL_DIR/write_result.sh 0
    exit
fi

ALL_NETWORK_IDS="$(sudo wpa_cli -i $BM_NW_INTERFACE list_networks | tail -n +2 | cut -c-1)"

NETWORK_ID=$($BM_SERVERCONTROL_DIR/get_network_id.sh "$SSID")

# Add a new network entry if the SSID is not known
if [[ -z "$NETWORK_ID" ]]; then
    NETWORK_ID=$(sudo wpa_cli -i $BM_NW_INTERFACE add_network)
    sudo wpa_cli -i $BM_NW_INTERFACE set_network $NETWORK_ID ssid "\"$SSID\"" 1>&3 2>&4
    if [[ -z "$PASSWORD" ]]; then
        sudo wpa_cli -i $BM_NW_INTERFACE set_network $NETWORK_ID key_mgmt NONE 1>&3 2>&4
    else
        PSK=$($BM_SERVERCONTROL_DIR/get_network_psk.sh "$SSID" "$PASSWORD")
        sudo wpa_cli -i $BM_NW_INTERFACE set_network $NETWORK_ID key_mgmt WPA-PSK 1>&3 2>&4
        sudo wpa_cli -i $BM_NW_INTERFACE set_network $NETWORK_ID psk $PSK 1>&3 2>&4
    fi
    ADDED_NETWORK=1
fi

# Enable new network and disable all others to make sure that wpa_supplicant tries
# to connect to the new network
sudo wpa_cli -i $BM_NW_INTERFACE select_network $NETWORK_ID 1>&3 2>&4

# Check if we obtain a successful connection within a certain time limit
RETRY_INTERVAL=0.2
MAX_TIME=10
for i in $(seq 0 $RETRY_INTERVAL $MAX_TIME); do
    STATUS="$(sudo wpa_cli -i $BM_NW_INTERFACE status)"
    if [[ ! -z "$(echo "$STATUS" | grep 'wpa_state=COMPLETED')" ]]; then
        CONNECTED_SSID=$(echo "$STATUS" | sed -n "s/^ssid=\(.*\)$/\1/p")
        if [[ "$CONNECTED_SSID" = "$SSID" ]]; then
            CONNECTION_SUCCEEDED=1
        fi
        break
    fi
    sleep $RETRY_INTERVAL
done

if [[ "$CONNECTION_SUCCEEDED" = "1" ]]; then
    # Re-enable the other networks so that they are available if the connection to the new network is lost
    for ID in "$ALL_NETWORK_IDS"; do
        sudo wpa_cli -i $BM_NW_INTERFACE enable_network $ID 1>&3 2>&4
    done

    # If instructed, write the configuration with the new network to the config file
    if [[ "$SAVE_NETWORK" = "1" ]]; then
        sudo wpa_cli -i $BM_NW_INTERFACE save_config 1>&3 2>&4
    fi

    $BM_SERVERCONTROL_DIR/write_result.sh 0
else
    # Remove the added network if we could not connect
    if [[ "$ADDED_NETWORK" = "1" ]]; then
        sudo wpa_cli -i $BM_NW_INTERFACE remove_network $NETWORK_ID 1>&3 2>&4
    fi

    # Try first to re-establish the previous connection
    if [[ ! -z "$CONNECTED_SSID" ]]; then
        CONNECTED_ID=$($BM_SERVERCONTROL_DIR/get_network_id.sh "$CONNECTED_SSID")
        sudo wpa_cli -i $BM_NW_INTERFACE select_network $CONNECTED_ID 1>&3 2>&4
        sleep 2
    fi

    # Re-enable all networks so that they are available if the connection to the present network is lost
    for ID in "$ALL_NETWORK_IDS"; do
        sudo wpa_cli -i $BM_NW_INTERFACE enable_network $ID 1>&3 2>&4
    done

    # Return to the original mode
    if [[ "$IS_ACCESS_POINT" = "1" ]]; then
        $BM_SERVERCONTROL_DIR/activate_ap_mode.sh
    fi

    $BM_SERVERCONTROL_DIR/write_result.sh 1
    exit 1
fi
