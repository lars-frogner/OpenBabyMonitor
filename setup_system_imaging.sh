#!/bin/bash
set -e

sudo apt -y install gdisk

cd
git clone https://github.com/seamusdemora/RonR-RPi-image-utils.git
chmod +x RonR-RPi-image-utils/image-backup
