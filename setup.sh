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
# ssh pi@raspberrypi.home # Password is raspberry (try .local if .home doesn't work)
# echo -e "\nexport LC_ALL=en_GB.UTF-8\nexport LANGUAGE=en_GB.UTF-8\n" >> ~/.bashrc
# sudo raspi-config
# Change password for pi user and set hostname to "babymonitor", set time zone to Oslo and enable camera module.
# ssh pi@babymonitor # Log in with new password
# Note: mini USB microphone does not appear to useable without sudo for other users than pi.
# Clone the babymonitor repository:
# git clone https://github.com/lars-frogner/babymonitor.git
# Run this setup script (as pi user).
# Run configure_network.sh to generate network configurations for using the unit as a wireless
# access point and for connecting to an existing wireless network as a client (the script works
# for Raspbian Buster). The two modes of operation can be switched between using the
# activate_ap_mode.sh and activate_client_mode.sh scripts.

SERVER_USER=pi
WEB_USER=www-data
WEB_GROUP=www-data
PERMISSIONS=750

PHP_TIMEZONE=Europe/Oslo

APACHE_LOG_DIR=/var/log/apache2
APACHE_ERROR_LOG_PATH=$APACHE_LOG_DIR/error.log

BM_PATH=$(dirname $(readlink -f $0))
BM_ENV_PATH=$BM_PATH/babymonitor_env.sh
BM_SITE_ROOT=/var/www/babymonitor
BM_LINKED_SITE_ROOT=$BM_PATH/site/public
BM_PICAM_PATH=$BM_PATH/picam
BM_PICAM_LOG_PATH=/var/log/picam.log
BM_SHAREDMEM_PATH=/run/shm
BM_PICAM_OUTPUT_PATH=$BM_SHAREDMEM_PATH/hls
BM_PICAM_LINKED_OUTPUT_PATH=$BM_LINKED_SITE_ROOT/hls

SETUP_ENV=true
if [[ "$SETUP_ENV" = true ]]; then
    touch $BM_ENV_PATH
    echo "export BM_PATH=$BM_PATH" >> $BM_ENV_PATH
    echo "export BM_SITE_ROOT=$BM_SITE_ROOT" >> $BM_ENV_PATH
    echo "export BM_LINKED_SITE_ROOT=$BM_LINKED_SITE_ROOT" >> $BM_ENV_PATH
    echo "export BM_PICAM_PATH=$BM_PICAM_PATH" >> $BM_ENV_PATH
    echo "export BM_PICAM_LOG_PATH=$BM_PICAM_LOG_PATH" >> $BM_ENV_PATH
    echo "export BM_SHAREDMEM_PATH=$BM_SHAREDMEM_PATH" >> $BM_ENV_PATH
    echo "export BM_PICAM_OUTPUT_PATH=$BM_PICAM_OUTPUT_PATH" >> $BM_ENV_PATH
    echo "export BM_PICAM_LINKED_OUTPUT_PATH=$BM_PICAM_LINKED_OUTPUT_PATH" >> $BM_ENV_PATH
fi

UPDATE=true
if [[ "$UPDATE" = true ]]; then
    sudo apt -y update
    sudo apt -y dist-upgrade
    sudo apt -y install ntp # Time server
fi

SETUP_BASH_CONFIG=true
if [[ "$SETUP_BASH_CONFIG" = true ]]; then
    # Enable convenient ls aliases
    sed -i "s/#alias ll='ls -l'/alias ll='ls -lh'/g" ~/.bashrc
    sed -i "s/#alias la='ls -A'/alias la='ls -A'/g" ~/.bashrc
    sed -i "s/#alias l='ls -CF'/alias l='ls -Alh'/g" ~/.bashrc

    # Add alias for showing CPU temperature
    echo -e "\nalias temp='sudo vcgencmd measure_temp'\n" >> ~/.bashrc

    # Enable arrow up/down history search
    cp /etc/inputrc ~/.inputrc
    sed -i 's/# "\\e\[B": history-search-forward/"\\e[B": history-search-forward/g' ~/.inputrc
    sed -i 's/# "\\e\[A": history-search-backward/"\\e[A": history-search-backward/g' ~/.inputrc

    echo -e "\nsource $BM_ENV_PATH" >> ~/.bashrc
fi

DISABLE_BLUETOOTH=true
if [[ "$DISABLE_BLUETOOTH" = true ]]; then
    # Disable bluetooth
    echo "
# Disable Bluetooth
dtoverlay=disable-bt" | sudo tee -a /boot/config.txt

    sudo systemctl disable hciuart
    sudo systemctl disable bluetooth
fi

SETUP_MIC=true
if [[ "$SETUP_MIC" = true ]]; then
    sudo adduser $SERVER_USER audio
    BM_MIC_ID=$(arecord -l | perl -n -e'/^card (\d+):.+, device (\d):.+$/ && print "hw:$1,$2"')
    echo "export BM_MIC_ID='$BM_MIC_ID'" >> $BM_ENV_PATH
fi

INSTALL_BOOTSTRAP=true
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
    FILENAME_ROOT="${FILENAME%%.zip}"
    cd /tmp
    wget $DOWNLOAD_URL
    unzip $FILENAME
    mkdir -p $BM_LINKED_SITE_ROOT/library/bootstrap
    mv $FILENAME_ROOT/* $BM_LINKED_SITE_ROOT/library/bootstrap/
    rm  -r $FILENAME $FILENAME_ROOT
    cd -
fi

INSTALL_VIDEOJS=true
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
    cd /tmp
    wget $DOWNLOAD_URL
    mkdir -p $BM_LINKED_SITE_ROOT/library/video-js
    unzip $FILENAME -d $BM_LINKED_SITE_ROOT/library/video-js
    rm $FILENAME
    cd -
fi

INSTALL_PICAM=true
if [[ "$INSTALL_PICAM" = true ]]; then
    # Install dependencies
    sudo apt -y install libharfbuzz0b libfontconfig1

    # Create directories and symbolic links
    sudo install -d -o $SERVER_USER -g $WEB_GROUP -m $PERMISSIONS $BM_PICAM_PATH{,/archive} $BM_SHAREDMEM_PATH/{rec,hooks,state}

    ln -sfn {$BM_PICAM_PATH,$BM_SHAREDMEM_PATH/rec}/archive
    ln -sfn {$BM_SHAREDMEM_PATH,$BM_PICAM_PATH}/rec
    ln -sfn {$BM_SHAREDMEM_PATH,$BM_PICAM_PATH}/hooks
    ln -sfn {$BM_SHAREDMEM_PATH,$BM_PICAM_PATH}/state

    sudo ln -s $BM_PICAM_OUTPUT_PATH $BM_PICAM_LINKED_OUTPUT_PATH

    sudo touch $BM_PICAM_LOG_PATH
    sudo chown $SERVER_USER:$WEB_GROUP $BM_PICAM_LOG_PATH

    # Install picam binary
    PICAM_VERSION=1.4.9
    if [[ "$PICAM_VERSION" = "latest" ]]; then
        DOWNLOAD_URL=$(curl https://api.github.com/repos/iizukanao/picam/releases/latest | grep browser_download_url | grep tar.xz | cut -d '"' -f 4)
        FILENAME=$(echo $DOWNLOAD_URL | cut -d "/" -f 9)
    else
        FILENAME="picam-${PICAM_VERSION}-binary.tar.xz"
        DOWNLOAD_URL="https://github.com/iizukanao/picam/releases/download/v${PICAM_VERSION}/$FILENAME"
    fi
    FILENAME_ROOT="${FILENAME%%.tar.xz}"
    cd /tmp
    wget $DOWNLOAD_URL
    tar -xvf $FILENAME
    mv $FILENAME_ROOT/picam $BM_PICAM_PATH/
    rm -r $FILENAME $FILENAME_ROOT
    cd -
fi

SETUP_SERVICES=true
if [[ "$SETUP_SERVICES" = true ]]; then
    UNIT_DIR=/lib/systemd/system
    LINKED_UNIT_DIR=$BM_PATH/control/services
    UNIT_ENV_FILE=$LINKED_UNIT_DIR/envvars
    SYSTEMCTL=/usr/bin/systemctl

    mkdir -p $LINKED_UNIT_DIR

    STARTUP_SERVICE_FILENAME=bm_startup.service
    echo "[Unit]
Description=Babymonitor startup script
After=mysqld.service

[Service]
Type=forking
EnvironmentFile=$UNIT_ENV_FILE
ExecStart=$BM_PATH/control/startup.sh
StandardError=append:$APACHE_ERROR_LOG_PATH

[Install]
WantedBy=multi-user.target" > $LINKED_UNIT_DIR/$STARTUP_SERVICE_FILENAME

    sudo ln -sfn {$LINKED_UNIT_DIR,$UNIT_DIR}/$STARTUP_SERVICE_FILENAME

    sudo systemctl enable $STARTUP_SERVICE_FILENAME

    CMD_ALIAS='Cmnd_Alias BM_MODES ='

    for SERVICE in standby listen audiostream videostream
    do
        SERVICE_ROOT_NAME=bm_$SERVICE
        SERVICE_FILENAME=$SERVICE_ROOT_NAME.service
        echo "[Unit]
Description=Babymonitor $SERVICE service

[Service]
Type=simple
User=$SERVER_USER
Group=$WEB_GROUP
EnvironmentFile=$UNIT_ENV_FILE
ExecStart=$BM_PATH/control/$SERVICE.py
StandardError=append:$APACHE_ERROR_LOG_PATH" > $LINKED_UNIT_DIR/$SERVICE_FILENAME

        sudo ln -sfn {$LINKED_UNIT_DIR,$UNIT_DIR}/$SERVICE_FILENAME

        CMD_ALIAS+=" $SYSTEMCTL stop $SERVICE_ROOT_NAME, $SYSTEMCTL start $SERVICE_ROOT_NAME, $SYSTEMCTL restart $SERVICE_ROOT_NAME,"
    done

    # Allow users in web group to manage the mode services without providing a password
    echo -e "${CMD_ALIAS%,}\n%$WEB_GROUP ALL = NOPASSWD: BM_MODES" | sudo tee /etc/sudoers.d/$WEB_GROUP

    # Copy environment variables (without 'export') into environment file for services
    ENV_VAR_EXPORTS=$(cat $BM_ENV_PATH)
    echo "${ENV_VAR_EXPORTS//'export '/}" > $UNIT_ENV_FILE
fi

INSTALL_SERVER=true
if [[ "$INSTALL_SERVER" = true ]]; then
    # Install Apache, PHP and MySQL (MariaDB)
    sudo apt -y install apache2 mariadb-server php php-mysql libapache2-mod-php

    # Install required Python packages
    sudo apt -y install python-pip
    pip install -r requirements.txt

    # Install inotify tools
    sudo apt -y install inotify-tools

    # Configure MySQL
    echo 'NOTE: Setup with root password according to root_account entry in config/config.json'
    sudo mysql_secure_installation

    # Set time zone
    PHP_INI_CLI_PATH=$(php -i | grep /.+/php.ini -oE)
    PHP_INI_APACHE_PATH=$(dirname $(dirname $PHP_INI_CLI_PATH))/apache2/php.ini
    sudo sed -i "s/;date.timezone =/date.timezone = ${PHP_TIMEZONE/'/'/'\/'}/g" $PHP_INI_CLI_PATH
    sudo sed -i "s/;date.timezone =/date.timezone = ${PHP_TIMEZONE/'/'/'\/'}/g" $PHP_INI_APACHE_PATH

    # Use index.php as index file
    APACHE_CONF_PATH=/etc/apache2/apache2.conf
    echo -e "\nDirectoryIndex index.php" | sudo tee -a $APACHE_CONF_PATH

    APACHE_ENV_PATH=/etc/apache2/envvars
    echo -e "\nsource $BM_ENV_PATH" | sudo tee -a $APACHE_ENV_PATH

    # Add main user to www-data group
    sudo adduser $SERVER_USER $WEB_GROUP

    # Ensure permissions are correct in project folder
    sudo chmod -R $PERMISSIONS $BM_PATH
    sudo chown -R $SERVER_USER:$WEB_GROUP $BM_PATH

    # Link site folder to default Apache site root
    sudo ln -s $BM_LINKED_SITE_ROOT $BM_SITE_ROOT

    # Remove default site
    sudo a2dissite 000-default
    sudo rm -r /var/www/html
    sudo rm -f /etc/apache2/sites-available/000-default.conf
    sudo rm -f /etc/apache2/sites-available/default-ssl.conf

    # Setup new site
    SITE_NAME=$(basename $BM_SITE_ROOT)
    echo "<VirtualHost *:80>
	DocumentRoot $BM_SITE_ROOT
	ErrorLog $APACHE_ERROR_LOG_PATH
	CustomLog $APACHE_LOG_DIR/access.log combined
</VirtualHost>" | sudo tee /etc/apache2/sites-available/$SITE_NAME.conf
    sudo a2ensite $SITE_NAME
fi

INITIALIZE_DATABASE=true
if [[ "$INITIALIZE_DATABASE" = true ]]; then
    $BM_PATH/site/config/init/init_database.sh
fi
