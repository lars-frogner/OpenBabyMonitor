#!/bin/bash

udevadm monitor --udev --subsystem-match=sound/pcm \
    | while read LINE
        do
            EVENT=$(echo $LINE | sed -n 's/^UDEV \[[0-9\.]*\.[0-9\.]*\] \([a-z]*\) .*$/\1/p')
            if [[ ! -z "$EVENT" ]]; then
                echo '!'
                $BM_SERVERCONTROL_DIR/auto_select_mic.sh
            fi
        done
