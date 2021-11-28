#!/bin/bash

FLAG_FILENAME="$(basename $BM_SERVER_ACTION_FILE)"

inotifywait -q -m -e create -e moved_to --format "%f" $BM_SERVER_ACTION_DIR \
    | while read FILENAME
        do
            if [[ "$FILENAME" = "$FLAG_FILENAME" ]]; then
                $BM_SERVERCONTROL_DIR/process_flag.sh
            fi
        done
