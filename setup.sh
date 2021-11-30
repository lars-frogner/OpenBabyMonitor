#!/bin/bash
SCRIPT_DIR=$(dirname $(readlink -f $0))

$SCRIPT_DIR/setup_server.sh
$SCRIPT_DIR/setup_network.sh

sudo reboot
