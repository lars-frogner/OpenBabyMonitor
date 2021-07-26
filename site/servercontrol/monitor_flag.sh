#!/bin/bash
set -e

SCRIPT_DIR=$(dirname $(readlink -f $0))
FLAG_FILENAME=.flag

inotifywait -m -e create -e moved_to --format "%f" $SCRIPT_DIR \
    | while read FILENAME
        do
            if [[ "$FILENAME" = "$FLAG_FILENAME" ]]; then
                $SCRIPT_DIR/process_flag.sh
            fi
        done
