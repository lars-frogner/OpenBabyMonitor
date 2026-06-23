#!/bin/bash

# Detect whether a camera is connected and reachable.
#
# We must tolerate variations in the output of the detection commands across
# firmware/OS versions. Notably, newer Raspberry Pi OS (Bullseye/Bookworm)
# firmware reports an extra field, e.g.
#   supported=1 detected=1, libcamera interfaces=0
# so an exact string comparison against 'supported=1 detected=1' fails even
# though a camera is present. We therefore match on the 'detected=1' substring.
#
# As a fallback for the libcamera stack (where the legacy 'detected' field can
# be 0 while libcamera still sees the camera), we also accept a non-empty
# libcamera camera list.

CONNECTED=0

if command -v vcgencmd >/dev/null 2>&1; then
    RESULT=$(vcgencmd get_camera 2>/dev/null)
    if [[ "$RESULT" == *detected=1* ]]; then
        CONNECTED=1
    fi
fi

# Fallback: libcamera-based detection (Bullseye/Bookworm without legacy stack)
if [[ "$CONNECTED" -eq 0 ]]; then
    for cmd in libcamera-hello rpicam-hello; do
        if command -v "$cmd" >/dev/null 2>&1; then
            if "$cmd" --list-cameras 2>/dev/null | grep -qiE 'Available cameras|imx|ov[0-9]'; then
                CONNECTED=1
            fi
            break
        fi
    done
fi

if [[ "$CONNECTED" -eq 1 ]]; then
    touch $BM_CONTROL_CAM_CONNECTED_FILE
else
    rm -f $BM_CONTROL_CAM_CONNECTED_FILE
fi
