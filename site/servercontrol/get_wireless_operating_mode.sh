#!/bin/bash
/sbin/iwconfig $BM_NW_INTERFACE | grep Mode | sed -n "s/.*Mode:\([A-Za-z-]*\).*/\1/p"
