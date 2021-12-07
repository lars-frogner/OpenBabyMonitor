#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

udevadm monitor --udev --subsystem-match=sound/pcm 2>&4 \
    | while read LINE
        do
            EVENT=$(echo $LINE | sed -n 's/^UDEV \[[0-9\.]*\.[0-9\.]*\] \([a-z]*\) .*$/\1/p'  2>&4)
            if [[ ! -z "$EVENT" ]]; then
                flock --exclusive --timeout 30 $BM_SERVER_ACTION_LOCK_FILE -c "$BM_SERVERCONTROL_DIR/auto_select_mic.sh" 2>&4
            fi
        done
