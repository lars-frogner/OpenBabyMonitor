#!/bin/bash

if [[ -f "$BM_CONTROL_CAM_CONNECTED_FILE" ]]; then
    echo 1
else
    echo 0
fi
