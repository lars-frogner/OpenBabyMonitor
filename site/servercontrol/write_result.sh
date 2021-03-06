#!/bin/bash

ERROR_CODE="$1"
OUTPUT="$2"

TEMPFILE=/tmp/result

echo -e "$ERROR_CODE\n$OUTPUT" > $TEMPFILE
sudo chown $BM_USER:$BM_WEB_GROUP $TEMPFILE
chmod $BM_READ_PERMISSIONS $TEMPFILE
mv $TEMPFILE $BM_SERVER_ACTION_RESULT_FILE
