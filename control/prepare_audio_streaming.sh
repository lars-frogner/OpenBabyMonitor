#/bin/bash
rm -rf $BM_AUDIO_STREAM_DIR
install -d -o $BM_USER -g $BM_WEB_GROUP -m $BM_READ_PERMISSIONS $BM_AUDIO_STREAM_DIR
openssl rand 16 > $BM_AUDIO_STREAM_DIR/stream.key
echo stream.key > $BM_AUDIO_STREAM_DIR/stream.keyinfo
echo stream.key >> $BM_AUDIO_STREAM_DIR/stream.keyinfo
echo $(openssl rand -hex 16) >> $BM_AUDIO_STREAM_DIR/stream.keyinfo
chown $BM_USER:$BM_WEB_GROUP $BM_AUDIO_STREAM_DIR/stream.key
chmod $BM_READ_PERMISSIONS $BM_AUDIO_STREAM_DIR/stream.key
