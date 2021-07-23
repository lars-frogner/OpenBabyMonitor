#!/bin/bash
set -e

stty -echo
printf "New site password: "
read PASSWORD
stty echo
printf '\n'

sudo php init_database.php $PASSWORD
if [[ "$?" == '0' ]]; then
    echo 'Database initialized successfully'
else
    echo "Could not initialize database" >&2
fi
