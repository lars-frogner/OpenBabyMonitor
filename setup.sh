#!/bin/bash
set -x
set -e

# Install Raspbian Lite and flash to SD card.
# Create empty file called "ssh" and a text file called "wpa_supplicant.conf" containing
# """
# ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
# update_config=1
# country=NO
#
# network={
#  ssid="<network name>"
#  psk="<network password>"
# }
# """
# in boot directory of flashed SD card.
# ssh pi@raspberrypi.home # Password is raspberry
# echo -e "\nexport LC_ALL=en_GB.UTF-8\nexport LANGUAGE=en_GB.UTF-8\n" >> ~/.bashrc
# sudo raspi-config
# Change password for pi user and set hostname to "babymonitor", set time zone to Oslo and enable camera module.
# ssh pi@babymonitor # Log in with new password
# Note: mini USB microphone does not appear to useable without sudo for other users than pi.

SITE_ROOT=~/babymonitor/site/public

UPDATE=false
if [[ "$UPDATE" = true ]]; then
    sudo apt -y update
    sudo apt -y dist-upgrade
fi

SETUP_BASH_CONFIG=false
if [[ "$SETUP_BASH_CONFIG" = true ]]; then
    # Enable convenient ls aliases
    sudo -u admin sed -i "s/#alias ll='ls -l'/alias ll='ls -lh'/g" ~/.bashrc
    sudo -u admin sed -i "s/#alias la='ls -A'/alias la='ls -A'/g" ~/.bashrc
    sudo -u admin sed -i "s/#alias l='ls -CF'/alias l='ls -Alh'/g" ~/.bashrc

    # Add alias for showing CPU temperature
    echo -e "\nalias temp='sudo vcgencmd measure_temp'\n" >> ~/.bashrc

    # Enable arrow up/down history search
    cp /etc/inputrc ~/.inputrc
    sed -i 's/# "\\e\[B": history-search-forward/"\\e[B": history-search-forward/g' ~/.inputrc
    sed -i 's/# "\\e\[A": history-search-backward/"\\e[A": history-search-backward/g' ~/.inputrc

    touch ~/monitor_env.sh
    echo -e "\nsource ~/monitor_env.sh\n" >> ~/.bashrc
fi

SETUP_STATIC_IP=false
if [[ "$SETUP_STATIC_IP" = true ]]; then
    HOST_IP=$(ip -o -4 addr list wlan0 | awk '{print $4}' | cut -d/ -f1)

    ROUTER_IP=$(ip r | grep default | sed -n "s/^default via \([0-9\.]*\).*$/\1/p")
    DNS_IP=$(sudo sed -n "s/^nameserver \([0-9\.]*\).*$/\1/p" /etc/resolv.conf | head -n 1)

    # Check if dhcpcd service is running
    if [[ -z $(systemctl | grep dhcpcd.service) ]]
    then
        # If not, start it
        sudo service dhcpcd start
        sudo systemctl enable dhcpcd
    fi

    # Set static IP to current IP in dhcpcd.conf
    echo "
    interface wlan0
    static ip_address=$HOST_IP/24
    static routers=$ROUTER_IP
    static domain_name_servers=$DNS_IP" | sudo tee -a /etc/dhcpcd.conf
fi

DISABLE_BLUETOOTH=false
if [[ "$DISABLE_BLUETOOTH" = true ]]; then
    # Disable bluetooth
    echo "

    # Disable Bluetooth
    dtoverlay=disable-bt" | sudo tee -a /boot/config.txt

    set +e
    sudo systemctl disable hciuart.service
    sudo systemctl disable bluetooth.service
fi

DISABLE_IPV6=false
if [[ "$DISABLE_IPV6" = true ]]; then
    # Disable IPv6 hostname resolution
    sudo sed -i 's/use-ipv6=yes/use-ipv6=no/g' /etc/avahi/avahi-daemon.conf
fi

INSTALL_SERVER=false
if [[ "$INSTALL_SERVER" = true ]]; then
    # Install Apache and PHP
    sudo apt -y install apache2 libapache2-mod-php

    sudo adduser pi www-data
    sudo adduser pi audio
    touch /var/log/picam.log
    chmod -R 755 $SITE_ROOT
    sudo ln -s $SITE_ROOT /var/www/babymonitor
fi

INSTALL_PICAM=false
if [[ "$INSTALL_PICAM" = true ]]; then
    # Install dependencies
    sudo apt -y install libharfbuzz0b libfontconfig1

    # Create directories and symbolic links
    DEST_DIR=~/picam
    SHM_DIR=/run/shm

    sudo install -d -o pi -g pi -m 755 $DEST_DIR{,/archive} $SHM_DIR/{rec,hooks,state}

    ln -sfn {$DEST_DIR,$SHM_DIR/rec}/archive
    ln -sfn {$SHM_DIR,$DEST_DIR}/rec
    ln -sfn {$SHM_DIR,$DEST_DIR}/hooks
    ln -sfn {$SHM_DIR,$DEST_DIR}/state

    sudo ln -s $SHM_DIR/hls /var/www/babymonitor/hls

    # Install picam binary
    PICAM_VERSION=1.4.9
    if [[ "$PICAM_VERSION" = "latest" ]]; then
        DOWNLOAD_URL=$(curl https://api.github.com/repos/iizukanao/picam/releases/latest | grep browser_download_url | grep tar.xz | cut -d '"' -f 4)
        FILENAME=$(echo $DOWNLOAD_URL | cut -d "/" -f 9)
    else
        FILENAME="picam-${PICAM_VERSION}-binary.tar.xz"
        DOWNLOAD_URL="https://github.com/iizukanao/picam/releases/download/v${PICAM_VERSION}/$FILENAME"
    fi
    FILENAME_ROOT="${FILENAME%%.*}"
    wget $DOWNLOAD_URL
    tar xvf $FILENAME
    mv $FILENAME_ROOT/picam $DEST_DIR/
    rm -r $FILENAME $FILENAME_ROOT
fi

SETUP_MIC=false
if [[ "$SETUP_MIC" = true ]]; then
    # Setup environment variables
    echo -e "export MIC_ID=$(arecord -l | perl -n -e'/^card (\d+):.+, device (\d):.+$/ && print "hw:$1,$2"')\n" >> ~/monitor_env.sh
fi

INSTALL_BOOTSTRAP=false
if [[ "$INSTALL_BOOTSTRAP" = true ]]; then
    sudo apt -y install unzip

    BOOTSTRAP_VERSION=5.0.2
    if [[ "$BOOTSTRAP_VERSION" = "latest" ]]; then
        DOWNLOAD_URL=$(curl https://api.github.com/repos/twbs/bootstrap/releases/latest | grep browser_download_url | grep dist.zip | cut -d '"' -f 4)
        FILENAME=$(echo $DOWNLOAD_URL | cut -d "/" -f 9)
    else
        FILENAME="bootstrap-${BOOTSTRAP_VERSION}-dist.zip"
        DOWNLOAD_URL="https://github.com/twbs/bootstrap/releases/download/v${BOOTSTRAP_VERSION}/$FILENAME"
    fi
    wget $DOWNLOAD_URL
    unzip $FILENAME -d $SITE_ROOT/library/bootstrap
    rm $FILENAME
fi

INSTALL_VIDEOJS=false
if [[ "$INSTALL_VIDEOJS" = true ]]; then
    sudo apt -y install unzip

    VIDEOJS_VERSION=7.13.3
    if [[ "$VIDEOJS_VERSION" = "latest" ]]; then
        DOWNLOAD_URL=$(curl https://api.github.com/repos/videojs/video.js/releases/latest | grep browser_download_url | grep .zip | cut -d '"' -f 4)
        FILENAME=$(echo $DOWNLOAD_URL | cut -d "/" -f 9)
    else
        FILENAME="video-js-${VIDEOJS_VERSION}.zip"
        DOWNLOAD_URL="https://github.com/videojs/video.js/releases/download/v${VIDEOJS_VERSION}/$FILENAME"
    fi
    wget $DOWNLOAD_URL
    unzip $FILENAME -d $SITE_ROOT/library/video-js
    rm $FILENAME
fi
