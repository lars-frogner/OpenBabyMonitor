#!/bin/bash
set -e

AP_ACTIVE=$($BM_SERVERCONTROL_DIR/access_point_active.sh)
if [[ "$AP_ACTIVE" = "0" ]]; then
    echo "Error: access point must be active before creating system image"
    exit 1
fi

TAG="${1:-}"
TARGET_DIR=/media
ROOT_NAME="babymonitor$TAG"
IMAGE_FILE="$ROOT_NAME.img"
ARCHIVE_FILE="$ROOT_NAME.zip"

BM_SITE_PW="$BM_SITE_PW" $BM_DIR/site/config/init/init_database.sh

sudo ~/RonR-RPi-image-utils/image-backup --initial "$TARGET_DIR/$IMAGE_FILE"

cd $TARGET_DIR
sudo zip -9 "$ARCHIVE_FILE" "$IMAGE_FILE"
