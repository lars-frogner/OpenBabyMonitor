#!/bin/bash

RESULT="$(sudo iwlist $BM_NW_INTERFACE scan | $BM_SERVERCONTROL_DIR/parse_iwlist_scan.py)"
$BM_SERVERCONTROL_DIR/write_result.sh "$?" "$RESULT"
