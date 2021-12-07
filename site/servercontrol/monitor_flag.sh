#!/bin/bash
source $BM_SERVERCONTROL_DIR/redirection.sh

FLAG_FILENAME="$(basename $BM_SERVER_ACTION_FILE)"

inotifywait -q -m -e create -e moved_to --format "%f" $BM_SERVER_ACTION_DIR 2>&4 \
    | while read FILENAME
        do
            if [[ "$FILENAME" = "$FLAG_FILENAME" ]]; then
                flock --exclusive --timeout 30 $BM_SERVER_ACTION_LOCK_FILE -c "$BM_SERVERCONTROL_DIR/process_flag.sh" 2>&4
            fi
        done
