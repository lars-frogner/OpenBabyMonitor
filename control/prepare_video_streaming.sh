#!/bin/bash
rm -rf $BM_PICAM_STREAM_DIR
install -d -o $BM_USER -g $BM_WEB_GROUP -m $BM_READ_PERMISSIONS $BM_PICAM_STREAM_DIR/{,rec,hooks,state}
ENCRYPTION_KEY_HEX=$(openssl rand -hex 16)
echo $ENCRYPTION_KEY_HEX > $BM_PICAM_STREAM_DIR/stream.hexkey
echo -ne "$(echo $ENCRYPTION_KEY_HEX | sed -e 's/../\\x&/g')" > $BM_PICAM_STREAM_DIR/stream.key
chown $BM_USER:$BM_WEB_GROUP $BM_PICAM_STREAM_DIR/stream.{hex,}key
chmod $BM_READ_PERMISSIONS $BM_PICAM_STREAM_DIR/stream.{hex,}key
