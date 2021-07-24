#!/bin/bash
set -e

SCRIPT_DIR=$(dirname $(readlink -f $0))

stty -echo
printf "New site password: "
read PASSWORD
stty echo
printf '\n'

sudo php $SCRIPT_DIR/init_database.php $PASSWORD
if [[ "$?" == '0' ]]; then
    echo 'Database initialized successfully'
else
    echo "Could not initialize database" >&2
fi
