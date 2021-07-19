#!/bin/bash
set -e

stty -echo
printf "Password: "
read PASSWORD
stty echo
printf '\n'

php store_password.php $PASSWORD
if [[ "$?" == '0' ]]; then
    echo 'Passord was set successfully'
else
    echo "Could not store password" >&2
fi
