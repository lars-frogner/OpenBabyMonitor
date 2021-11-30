#!/bin/bash

if [[ -f "$BM_CONTROL_MIC_ID_FILE" ]]; then
    echo 1
else
    echo 0
fi
