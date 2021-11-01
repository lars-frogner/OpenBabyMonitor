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
# Change password for pi user and set hostname to "babymonitor", set time zone to Oslo and enable camera module. Say yes to reboot.
# ssh pi@babymonitor # Log in with new password
# Note: mini USB microphone does not appear to useable without sudo for other users than pi.
# Clone the babymonitor repository:
# git clone https://github.com/lars-frogner/babymonitor.git
# Run this setup script (as pi user).
# Run configure_network.sh to generate network configurations for using the unit as a wireless
# access point and for connecting to an existing wireless network as a client (the script works
# for Raspbian Buster). The two modes of operation can be switched between using the
# activate_ap_mode.sh and activate_client_mode.sh scripts.

BM_SERVER_USER=pi
WEB_USER=www-data
BM_WEB_GROUP=www-data
BM_READ_PERMISSIONS=750
BM_WRITE_PERMISSIONS=770

SWAP_SIZE=1024

PHP_TIMEZONE=Europe/Oslo

APACHE_LOG_DIR=/var/log/apache2
BM_APACHE_LOG_PATH=$APACHE_LOG_DIR/error.log
SERVER_LOG_DIR=/var/log/babymonitor
BM_SERVER_LOG_PATH=$SERVER_LOG_DIR/error.log

BM_DIR=$(dirname $(readlink -f $0))
BM_ENV_DIR=$BM_DIR/env
BM_ENV_EXPORTS_PATH=$BM_ENV_DIR/envvar_exports
BM_ENV_PATH=$BM_ENV_DIR/envvars
BM_SITE_DIR=/var/www/babymonitor
BM_LINKED_SITE_DIR=$BM_DIR/site/public
BM_SHAREDMEM_DIR=/run/shm
BM_LINKED_STREAM_DIR=$BM_LINKED_SITE_DIR/streaming
BM_AUDIO_STREAM_DIR=$BM_SHAREDMEM_DIR/audiostream
BM_AUDIO_STREAM_FILE=$BM_AUDIO_STREAM_DIR/index.m3u8
BM_PICAM_DIR=$BM_DIR/picam
BM_PICAM_STREAM_DIR=$BM_SHAREDMEM_DIR/picam
BM_PICAM_STREAM_FILE=$BM_PICAM_STREAM_DIR/index.m3u8
BM_SERVERCONTROL_DIR=$BM_DIR/site/servercontrol
BM_SERVER_ACTION_DIR=$BM_SERVERCONTROL_DIR/.hook
BM_SERVER_ACTION_FILE=$BM_SERVER_ACTION_DIR/flag
BM_SERVER_ACTION_RESULT_FILE=$BM_SERVER_ACTION_DIR/result
BM_MODE_LOCK_DIR=$BM_DIR/control/.lock
BM_MODE_LOCK_FILE=$BM_MODE_LOCK_DIR/free
BM_MODE_COMM_DIR=$BM_DIR/control/.comm

UPDATE=true
if [[ "$UPDATE" = true ]]; then
    sudo apt -y update
    sudo apt -y dist-upgrade
fi

SETUP_SWAPSPACE=true
if [[ "$SETUP_SWAPSPACE" = true ]]; then
    sudo dphys-swapfile swapoff
    sudo sed -i "s/CONF_SWAPSIZE=100/CONF_SWAPSIZE=$SWAP_SIZE/g" /etc/dphys-swapfile
    sudo dphys-swapfile setup
    sudo dphys-swapfile swapon
fi

INSTALL_PACKAGES=true
if [[ "$INSTALL_PACKAGES" = true ]]; then
    sudo apt -y install unzip

    # Install time server
    sudo apt -y install ntp

    # Install inotify for flag monitoring
    sudo apt -y install inotify-tools

    sudo apt -y install git

    sudo apt -y install python3 python3-pip

    # Install Apache, PHP and MySQL (MariaDB)
    sudo apt -y install apache2 mariadb-server php php-dev php-pear php-mysql libapache2-mod-php
    sudo pecl install inotify

    # Install dependencies for picam
    sudo apt -y install libharfbuzz0b libfontconfig1

    # Install packages for audio recording and streaming
    sudo apt -y install alsa-utils ffmpeg lame

    # Install required Python packages
    sudo apt -y install libatlas-base-dev # Requirement for numpy
    sudo apt -y install libopenexr-dev libgtk-3-dev # Requirements for OpenCV
    pip3 install --no-cache-dir -r requirements.txt

    sudo apt -y autoremove
fi

SETUP_BASH_CONFIG=true
if [[ "$SETUP_BASH_CONFIG" = true ]]; then
    # Enable convenient ls aliases
    sed -i "s/#alias ll='ls -l'/alias ll='ls -lh'/g" ~/.bashrc
    sed -i "s/#alias la='ls -A'/alias la='ls -A'/g" ~/.bashrc
    sed -i "s/#alias l='ls -CF'/alias l='ls -Alh'/g" ~/.bashrc

    # Add alias for showing CPU temperature
    echo -e "alias temp='sudo vcgencmd measure_temp'\n" >> ~/.bashrc

    # Enable arrow up/down history search
    cp /etc/inputrc ~/.inputrc
    sed -i 's/# "\\e\[B": history-search-forward/"\\e[B": history-search-forward/g' ~/.inputrc
    sed -i 's/# "\\e\[A": history-search-backward/"\\e[A": history-search-backward/g' ~/.inputrc

    echo -e "source $BM_ENV_EXPORTS_PATH\n" >> ~/.bashrc

    sudo sed -i 's/pi ALL=(ALL) NOPASSWD: ALL/pi ALL=(ALL) PASSWD: ALL/g' /etc/sudoers.d/010_pi-nopasswd

    source ~/.bashrc
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

SETUP_AUDIO=true
if [[ "$SETUP_AUDIO" = true ]]; then
    sudo adduser $BM_SERVER_USER audio

    BM_MIC_ID=$(arecord -l | perl -n -e'/^card (\d+):.+, device (\d):.+$/ && print "hw:$1,$2"')
    BM_SOUND_CARD_NUMBER=$(echo $BM_MIC_ID | sed -n 's/^hw:\([0-9]*\),[0.9]*$/\1/p')
    echo "export BM_MIC_ID='$BM_MIC_ID'" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SOUND_CARD_NUMBER='$BM_SOUND_CARD_NUMBER'" >> $BM_ENV_EXPORTS_PATH

    sudo ln -sfn $BM_SHAREDMEM_DIR $BM_LINKED_STREAM_DIR

    echo "#!/bin/bash
rm -rf \$BM_AUDIO_STREAM_DIR
install -d -o $BM_SERVER_USER -g $BM_WEB_GROUP -m $BM_READ_PERMISSIONS \$BM_AUDIO_STREAM_DIR
openssl rand 16 > \$BM_AUDIO_STREAM_DIR/stream.key
chown $BM_SERVER_USER:$BM_WEB_GROUP \$BM_AUDIO_STREAM_DIR/stream.key
chmod $BM_READ_PERMISSIONS \$BM_AUDIO_STREAM_DIR/stream.key
" > $BM_DIR/control/prepare_audio_streaming.sh
    chmod $BM_READ_PERMISSIONS $BM_DIR/control/prepare_audio_streaming.sh
fi

SETUP_ENV=true
if [[ "$SETUP_ENV" = true ]]; then
    mkdir -p $BM_ENV_DIR

    touch $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_USER=$BM_SERVER_USER" >> $BM_ENV_EXPORTS_PATH
    echo "export WEB_USER=$WEB_USER" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_WEB_GROUP=$BM_WEB_GROUP" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_READ_PERMISSIONS=$BM_READ_PERMISSIONS" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_WRITE_PERMISSIONS=$BM_WRITE_PERMISSIONS" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_DIR=$BM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_LOG_PATH=$BM_SERVER_LOG_PATH" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SHAREDMEM_DIR=$BM_SHAREDMEM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_LINKED_STREAM_DIR=$BM_LINKED_STREAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_AUDIO_STREAM_DIR=$BM_AUDIO_STREAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_AUDIO_STREAM_FILE=$BM_AUDIO_STREAM_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_MICSTREAM_HEADERS_FILE=$BM_MICSTREAM_HEADERS_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_PICAM_DIR=$BM_PICAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_PICAM_STREAM_DIR=$BM_PICAM_STREAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_PICAM_STREAM_FILE=$BM_PICAM_STREAM_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVERCONTROL_DIR=$BM_SERVERCONTROL_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_ACTION_DIR=$BM_SERVER_ACTION_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_ACTION_FILE=$BM_SERVER_ACTION_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_ACTION_RESULT_FILE=$BM_SERVER_ACTION_RESULT_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_MODE_LOCK_FILE=$BM_MODE_LOCK_FILE" >> $BM_ENV_EXPORTS_PATH

    # Copy environment variables (without 'export') into environment file for services and PHP
    ENV_VAR_EXPORTS=$(cat $BM_ENV_EXPORTS_PATH)
    echo "${ENV_VAR_EXPORTS//'export '/}" > $BM_ENV_PATH
fi

INSTALL_BOOTSTRAP=true
if [[ "$INSTALL_BOOTSTRAP" = true ]]; then
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
    wget https://cdn.jsdelivr.net/npm/bootstrap-dark-5@1.1.2/dist/css/bootstrap-dark.min.css
    wget https://raw.githubusercontent.com/twbs/icons/main/bootstrap-icons.svg
    unzip $FILENAME
    mkdir -p $BM_LINKED_SITE_DIR/library/bootstrap
    mv $FILENAME_ROOT/* $BM_LINKED_SITE_DIR/library/bootstrap/
    mv bootstrap-dark.min.css $BM_LINKED_SITE_DIR/library/bootstrap/css/
    mv bootstrap-icons.svg $BM_LINKED_SITE_DIR/media/
    rm -r $FILENAME $FILENAME_ROOT
    cd -
fi

INSTALL_HLS_JS=true
if [[ "$INSTALL_HLS_JS" = true ]]; then
    HLS_JS_VERSION=1.0.11
    if [[ "$HLS_JS_VERSION" = "latest" ]]; then
        DOWNLOAD_URL=$(curl https://api.github.com/repos/video-dev/hls.js/releases/latest | grep browser_download_url | grep release.zip | cut -d '"' -f 4)
        FILENAME=$(echo $DOWNLOAD_URL | cut -d "/" -f 9)
    else
        FILENAME="release.zip"
        DOWNLOAD_URL="https://github.com/video-dev/hls.js/releases/download/v${HLS_JS_VERSION}/$FILENAME"
    fi
    cd /tmp
    wget $DOWNLOAD_URL
    unzip $FILENAME
    mkdir -p $BM_LINKED_SITE_DIR/library/hls-js
    mv dist/hls.min.js* $BM_LINKED_SITE_DIR/library/hls-js/
    rm -r $FILENAME dist
    cd -
fi

INSTALL_ANIME=true
if [[ "$INSTALL_ANIME" = true ]]; then
    ANIME_VERSION=3.2.1
    if [[ "$ANIME_VERSION" = "latest" ]]; then
        FILENAME_ROOT="master"
        FILENAME="$FILENAME_ROOT.zip"
        DOWNLOAD_URL=https://github.com/juliangarnier/anime/archive/$FILENAME
    else
        FILENAME_ROOT=$ANIME_VERSION
        FILENAME=v$ANIME_VERSION.zip
        DOWNLOAD_URL=https://github.com/juliangarnier/anime/archive/refs/tags/$FILENAME
    fi
    cd /tmp
    wget $DOWNLOAD_URL
    unzip $FILENAME
    mkdir -p $BM_LINKED_SITE_DIR/library/anime
    mv anime-$FILENAME_ROOT/lib/anime.min.js $BM_LINKED_SITE_DIR/library/anime/
    rm -r $FILENAME anime-$FILENAME_ROOT
    cd -
fi

INSTALL_VIDEOJS=true
if [[ "$INSTALL_VIDEOJS" = true ]]; then
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
    mkdir -p $BM_LINKED_SITE_DIR/library/video-js
    unzip $FILENAME -d $BM_LINKED_SITE_DIR/library/video-js
    rm $FILENAME
    cd -
fi

INSTALL_JQUERY=true
if [[ "$INSTALL_JQUERY" = true ]]; then
    JQUERY_VERSION=3.6.0
    FILENAME=jquery-$JQUERY_VERSION.min.js
    DOWNLOAD_URL=https://code.jquery.com/$FILENAME
    cd /tmp
    wget $DOWNLOAD_URL
    mkdir -p $BM_LINKED_SITE_DIR/library
    mv $FILENAME $BM_LINKED_SITE_DIR/library/jquery.min.js
    cd -
fi

INSTALL_PICAM=true
if [[ "$INSTALL_PICAM" = true ]]; then
    # Create directories and symbolic links
    sudo install -d -o $BM_SERVER_USER -g $BM_WEB_GROUP -m $BM_READ_PERMISSIONS $BM_PICAM_DIR{,/archive}

    ln -sfn {$BM_PICAM_STREAM_DIR,$BM_PICAM_DIR}/rec
    ln -sfn {$BM_PICAM_STREAM_DIR,$BM_PICAM_DIR}/hooks
    ln -sfn {$BM_PICAM_STREAM_DIR,$BM_PICAM_DIR}/state

    sudo ln -sfn $BM_SHAREDMEM_DIR $BM_LINKED_STREAM_DIR

    echo "#!/bin/bash
rm -rf \$BM_PICAM_STREAM_DIR
install -d -o $BM_SERVER_USER -g $BM_WEB_GROUP -m $BM_READ_PERMISSIONS \$BM_PICAM_STREAM_DIR/{,rec,hooks,state}
ENCRYPTION_KEY_HEX=\$(openssl rand -hex 16)
echo \$ENCRYPTION_KEY_HEX > \$BM_PICAM_STREAM_DIR/stream.hexkey
echo -ne \"\$(echo \$ENCRYPTION_KEY_HEX | sed -e 's/../\\\\x&/g')\" > \$BM_PICAM_STREAM_DIR/stream.key
chown $BM_SERVER_USER:$BM_WEB_GROUP \$BM_PICAM_STREAM_DIR/stream.{hex,}key
chmod $BM_READ_PERMISSIONS \$BM_PICAM_STREAM_DIR/stream.{hex,}key
" > $BM_DIR/control/prepare_video_streaming.sh
    chmod $BM_READ_PERMISSIONS $BM_DIR/control/prepare_video_streaming.sh

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
    mv $FILENAME_ROOT/picam $BM_PICAM_DIR/
    rm -r $FILENAME $FILENAME_ROOT
    cd -
fi

INSTALL_FLAG_ICONS=true
if [[ "$INSTALL_FLAG_ICONS" = true ]]; then
    cd /tmp
    wget https://github.com/lipis/flag-icons/archive/main.zip
    unzip main.zip
    mkdir -p $BM_LINKED_SITE_DIR/library/flag-icons/css
    mv flag-icons-main/css/flag-icons.min.css $BM_LINKED_SITE_DIR/library/flag-icons/css/
    mv flag-icons-main/flags $BM_LINKED_SITE_DIR/library/flag-icons/
    rm -r flag-icons-main main.zip
    cd -
fi

SETUP_SERVICES=true
if [[ "$SETUP_SERVICES" = true ]]; then
    UNIT_DIR=/lib/systemd/system
    LINKED_UNIT_DIR=$BM_DIR/control/services
    SYSTEMCTL=/usr/bin/systemctl

    mkdir -p $LINKED_UNIT_DIR

    ROOT_STARTUP_SERVICE_FILENAME=bm_root_startup.service
    echo "[Unit]
Description=Babymonitor root startup script

[Service]
Type=forking
EnvironmentFile=$BM_ENV_PATH
ExecStart=$BM_DIR/control/root_startup.sh
StandardError=append:$BM_SERVER_LOG_PATH

[Install]
WantedBy=multi-user.target" > $LINKED_UNIT_DIR/$ROOT_STARTUP_SERVICE_FILENAME

    sudo ln -sfn {$LINKED_UNIT_DIR,$UNIT_DIR}/$ROOT_STARTUP_SERVICE_FILENAME

    sudo systemctl enable $ROOT_STARTUP_SERVICE_FILENAME

    STARTUP_SERVICE_FILENAME=bm_startup.service
    echo "[Unit]
Description=Babymonitor startup script
After=mysqld.service

[Service]
Type=oneshot
User=$BM_SERVER_USER
Group=$BM_WEB_GROUP
EnvironmentFile=$BM_ENV_PATH
ExecStart=$BM_DIR/control/startup.sh
StandardError=append:$BM_SERVER_LOG_PATH

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
User=$BM_SERVER_USER
Group=$BM_WEB_GROUP
EnvironmentFile=$BM_ENV_PATH
ExecStart=$BM_DIR/control/$SERVICE.sh
StandardError=append:$BM_SERVER_LOG_PATH" > $LINKED_UNIT_DIR/$SERVICE_FILENAME

        sudo ln -sfn {$LINKED_UNIT_DIR,$UNIT_DIR}/$SERVICE_FILENAME

        CMD_ALIAS+=" $SYSTEMCTL stop $SERVICE_ROOT_NAME, $SYSTEMCTL start $SERVICE_ROOT_NAME, $SYSTEMCTL restart $SERVICE_ROOT_NAME,"
    done

    # Allow users in web group to manage the mode services without providing a password
    echo -e "${CMD_ALIAS%,}\n%$BM_WEB_GROUP ALL = NOPASSWD: BM_MODES" | (sudo su -c "EDITOR=\"tee\" visudo -f /etc/sudoers.d/$BM_WEB_GROUP")
fi

INSTALL_SERVER=true
if [[ "$INSTALL_SERVER" = true ]]; then
    # Configure MySQL
    echo 'NOTE: Setup with root password according to root_account entry in config/config.json'
    sudo mysql_secure_installation

    # Configure PHP
    PHP_INI_CLI_PATH=$(php -i | grep /.+/php.ini -oE)
    PHP_DIR=$(dirname $(dirname $PHP_INI_CLI_PATH))
    PHP_INI_APACHE_PATH=$PHP_DIR/apache2/php.ini

    # Make sure inotify PHP extension is loaded
    echo 'extension=inotify.so' | sudo tee $PHP_DIR/mods-available/inotify.ini
    sudo phpenmod inotify
    sudo sed -i "/;extension=xsl/aextension=inotify" $PHP_INI_CLI_PATH
    sudo sed -i "/;extension=xsl/aextension=inotify" $PHP_INI_APACHE_PATH

    # Set time zone
    sudo sed -i "s/;date.timezone =/date.timezone = ${PHP_TIMEZONE/'/'/'\/'}/g" $PHP_INI_CLI_PATH
    sudo sed -i "s/;date.timezone =/date.timezone = ${PHP_TIMEZONE/'/'/'\/'}/g" $PHP_INI_APACHE_PATH

    # Use index.php as index file
    APACHE_CONF_PATH=/etc/apache2/apache2.conf
    echo -e "\nDirectoryIndex index.php" | sudo tee -a $APACHE_CONF_PATH

    # Add main user to www-data group
    sudo adduser $BM_SERVER_USER $BM_WEB_GROUP

    # Create folders where the group has write permissions
    mkdir -p $BM_SERVER_ACTION_DIR $BM_MODE_LOCK_DIR $BM_MODE_COMM_DIR

    # Make sure files to be watched in the comm directory exist
    touch $BM_MODE_COMM_DIR/{probabilities.json, notification.txt}

    # Ensure permissions are correct in project folder
    sudo chmod -R $BM_READ_PERMISSIONS $BM_DIR
    sudo chown -R $BM_SERVER_USER:$BM_WEB_GROUP $BM_DIR

    # Set write permissions
    sudo chmod $BM_WRITE_PERMISSIONS $BM_SERVER_ACTION_DIR
    sudo chmod $BM_WRITE_PERMISSIONS $BM_MODE_LOCK_DIR
    sudo chmod $BM_WRITE_PERMISSIONS $BM_MODE_COMM_DIR

    sudo mkdir -p $SERVER_LOG_DIR
    sudo touch $BM_SERVER_LOG_PATH
    sudo chown $BM_SERVER_USER:$BM_WEB_GROUP $BM_SERVER_LOG_PATH
    sudo chmod $BM_WRITE_PERMISSIONS $BM_SERVER_LOG_PATH

    # Link site folder to default Apache site root
    sudo ln -s $BM_LINKED_SITE_DIR $BM_SITE_DIR

    # Remove default site
    sudo a2dissite 000-default
    sudo rm -r /var/www/html
    sudo rm -f /etc/apache2/sites-available/000-default.conf
    sudo rm -f /etc/apache2/sites-available/default-ssl.conf

    # Enable SSL module
    sudo a2enmod ssl

    echo "[req]
default_bits  = 2048
distinguished_name = req_distinguished_name
req_extensions = req_ext
x509_extensions = v3_req
prompt = no

[req_distinguished_name]
commonName = babymonitor.local: Self-signed certificate

[req_ext]
subjectAltName = @alt_names

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = babymonitor.local
DNS.2 = babymonitor.home
DNS.3 = babymonitor.lan
" > babymonitor.cnf

    SSL_KEY_PATH=/etc/ssl/private/babymonitor.key
    SSL_CERT_PATH=/etc/ssl/certs/babymonitor.crt
    sudo openssl req -x509 -nodes -days 36524 -newkey rsa:2048 -keyout $SSL_KEY_PATH -out $SSL_CERT_PATH -config babymonitor.cnf
    rm babymonitor.cnf

    # Setup new site
    SITE_NAME=$(basename $BM_SITE_DIR)
    echo "<VirtualHost *:80>
    DocumentRoot $BM_SITE_DIR
    ErrorLog $BM_APACHE_LOG_PATH
    CustomLog $APACHE_LOG_DIR/access.log combined
    ServerName babymonitor.local
    ServerAlias babymonitor.*

    <Directory \"$BM_SITE_DIR\">
        AllowOverride All
    </Directory>

    <Directory \"$BM_SITE_DIR/audiostream\">
        AllowOverride All
    </Directory>
</VirtualHost>

<VirtualHost *:443>
    DocumentRoot $BM_SITE_DIR
    ErrorLog $BM_APACHE_LOG_PATH
    CustomLog $APACHE_LOG_DIR/access.log combined
    ServerName babymonitor.local
    ServerAlias babymonitor.*

    SSLEngine On
    SSLProtocol all -SSLv2
    SSLCipherSuite HIGH:MEDIUM:!aNULL:!MD5
    SSLCertificateFile \"$SSL_CERT_PATH\"
    SSLCertificateKeyFile \"$SSL_KEY_PATH\"

    <Directory \"$BM_SITE_DIR\">
        AllowOverride All
    </Directory>

    <Directory \"$BM_SITE_DIR/audiostream\">
        AllowOverride All
    </Directory>
</VirtualHost>" | sudo tee /etc/apache2/sites-available/$SITE_NAME.conf
    sudo a2ensite $SITE_NAME
fi

INITIALIZE_DATABASE=true
if [[ "$INITIALIZE_DATABASE" = true ]]; then
    $BM_DIR/site/config/init/init_database.sh
fi
