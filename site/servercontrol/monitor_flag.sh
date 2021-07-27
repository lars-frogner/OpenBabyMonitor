#!/bin/bash
set -e

SCRIPT_DIR=$(dirname $(readlink -f $0))
FLAG_FILENAME=$(basename $BM_SERVER_ACTION_FILE)

inotifywait -m -e create -e moved_to --format "%f" $BM_SERVER_ACTION_DIR \
    | while read FILENAME
        do
            if [[ "$FILENAME" = "$FLAG_FILENAME" ]]; then
                $SCRIPT_DIR/process_flag.sh
            fi
        done
