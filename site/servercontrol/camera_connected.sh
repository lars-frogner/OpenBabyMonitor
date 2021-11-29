#!/bin/bash

CONNECTED_RESULT='supported=1 detected=1'
RESULT=$(sudo /usr/bin/vcgencmd get_camera)

case $CONNECTED_RESULT in

    $RESULT)
        echo 1
    ;;

    *)
        echo 0
    ;;
esac
