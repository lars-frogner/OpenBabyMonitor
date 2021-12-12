#!/bin/bash
set -e

SCRIPT_DIR=$(dirname $(readlink -f $0))

if [[ -z "$BM_SITE_PW" ]]; then
    while : ; do
        stty -echo
        printf "New website password: "
        read PASSWORD
        stty echo
        printf '\n'

        stty -echo
        printf "Repeat new password: "
        read PASSWORD_REPEAT
        stty echo
        printf '\n'

        if [[ "$PASSWORD" != "$PASSWORD_REPEAT" ]]; then
            echo 'The passwords did not match'
        else
            break
        fi
    done
else
    PASSWORD="$BM_SITE_PW"
fi

sudo php $SCRIPT_DIR/init_database.php "$PASSWORD"
if [[ "$?" == '0' ]]; then
    echo 'Database initialized successfully'
else
    echo "Could not initialize database" >&2
fi
