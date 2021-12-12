#!/bin/bash
set -e

AP_ACTIVE=$($BM_SERVERCONTROL_DIR/access_point_active.sh)
if [[ "$AP_ACTIVE" = "0" ]]; then
    echo "Error: access point must be active before creating system image"
    exit 1
fi

TAG="${1:-}"
IMAGE_STEM="/media/babymonitor$TAG"
IMAGE_PATH="$IMAGE_STEM.img"
ARCHIVE_PATH="$IMAGE_STEM.zip"

$BM_DIR/site/config/init/init_database.sh

sudo ~/RonR-RPi-image-utils/image-backup --initial "$IMAGE_PATH"

zip -Z bzip2 -9 "$ARCHIVE_PATH" "$IMAGE_PATH"
