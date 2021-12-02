#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

iwgetid $BM_NW_INTERFACE --raw 2>&4
