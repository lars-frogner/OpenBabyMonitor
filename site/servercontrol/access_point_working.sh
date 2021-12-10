#!/bin/bash
if iwconfig $BM_NW_INTERFACE | grep -q 'Mode:Master'; then
    echo 1
else
    echo 0
fi
