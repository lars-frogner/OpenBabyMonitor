#!/bin/bash
set -e

BM_DIR=$(dirname $(readlink -f $0))
source $BM_DIR/config/setup_config.env

if [[ "$(whoami)" != "$BM_USER" ]]; then
    echo "Error: this script must be run by user $BM_USER"
    exit 1
fi

WEB_USER=www-data
BM_WEB_GROUP=www-data
BM_READ_PERMISSIONS=750
BM_WRITE_PERMISSIONS=770

SWAP_SIZE=1024

APACHE_LOG_DIR=/var/log/apache2
BM_APACHE_LOG_PATH=$APACHE_LOG_DIR/error.log
SERVER_LOG_DIR=/var/log/babymonitor
BM_SERVER_LOG_PATH=$SERVER_LOG_DIR/error.log

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
BM_PHPSYSINFO_CONFIG_FILE=$BM_LINKED_SITE_DIR/library/phpsysinfo/phpsysinfo.ini
BM_SERVERCONTROL_DIR=$BM_DIR/site/servercontrol
BM_SERVER_ACTION_DIR=$BM_SERVERCONTROL_DIR/.hook
BM_SERVER_ACTION_FILE=$BM_SERVER_ACTION_DIR/flag
BM_SERVER_ACTION_RESULT_FILE=$BM_SERVER_ACTION_DIR/result
BM_MODE_LOCK_DIR=$BM_DIR/control/.lock
BM_MODE_LOCK_FILE=$BM_MODE_LOCK_DIR/free
BM_LISTEN_COMM_DIR=$BM_DIR/control/.comm
BM_CONTROL_MIC_DIR=$BM_DIR/control/.mic
BM_CONTROL_MIC_ID_FILE=$BM_CONTROL_MIC_DIR/id
BM_CONTROL_CAM_DIR=$BM_DIR/control/.cam
BM_CONTROL_CAM_CONNECTED_FILE=$BM_CONTROL_CAM_DIR/connected

SETUP_AUDIO=true
if [[ "$SETUP_AUDIO" = true ]]; then
    $BM_DIR/control/mic.py --select-mic
    sudo adduser $BM_USER audio
    sudo ln -sfn $BM_SHAREDMEM_DIR $BM_LINKED_STREAM_DIR
fi

UPDATE=true
if [[ "$UPDATE" = true ]]; then
    sudo apt -y update
    sudo apt -y dist-upgrade
fi

SETUP_SWAPSPACE=true
if [[ "$SETUP_SWAPSPACE" = true ]]; then
    sudo dphys-swapfile swapoff
    sudo sed -i "s/CONF_SWAPSIZE=.*/CONF_SWAPSIZE=$SWAP_SIZE/g" /etc/dphys-swapfile
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

    sudo apt -y install python3 python3-pip

    # Install Apache, PHP and MySQL (MariaDB)
    sudo apt -y install apache2 mariadb-server php php-dev php-pear php-mysql libapache2-mod-php
    sudo apt -y install libzip-dev # Requirement for zip

    # Install PHP extensions
    sudo pecl channel-update pecl.php.net
    sudo pecl install inotify zip

    # Install dependencies for picam
    sudo apt -y install libharfbuzz0b libfontconfig1

    # Install packages for audio recording and streaming
    sudo apt -y install alsa-utils ffmpeg lame

    # Install required Python packages
    sudo apt -y install libatlas-base-dev # Requirement for numpy
    sudo apt -y install libopenexr-dev # Requirement for OpenCV
    pip3 install --no-cache-dir -r $BM_DIR/requirements.txt

    sudo apt -y autoremove
fi

SETUP_BASH_CONFIG=true
if [[ "$SETUP_BASH_CONFIG" = true ]]; then
    # Enable convenient ls aliases
    sed -i "s/#*alias ll=.*$/alias ll='ls -lh'/g" /home/$BM_USER/.bashrc
    sed -i "s/#*alias la=.*$/alias la='ls -A'/g" /home/$BM_USER/.bashrc
    sed -i "s/#*alias l=.*$/alias l='ls -Alh'/g" /home/$BM_USER/.bashrc

    # Enable arrow up/down history search
    cp /etc/inputrc /home/$BM_USER/.inputrc
    sed -i 's/#*[[:space:]]*"\\e\[B":.*$/"\\e[B": history-search-forward/g' /home/$BM_USER/.inputrc
    sed -i 's/#*[[:space:]]*"\\e\[A":.*$/"\\e[A": history-search-backward/g' /home/$BM_USER/.inputrc

    # Set default editor
    echo -e "EDITOR=nano\n" >> /home/$BM_USER/.bashrc

    echo -e "source $BM_ENV_EXPORTS_PATH\n" >> /home/$BM_USER/.bashrc

    if [[ "$BM_USER" == "pi" ]]; then
        sudo sed -i 's/^pi .*$/pi ALL=(ALL) PASSWD: ALL/g' /etc/sudoers.d/010_pi-nopasswd
    fi
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

SETUP_ENV=true
if [[ "$SETUP_ENV" = true ]]; then
    mkdir -p $BM_ENV_DIR
    touch $BM_ENV_EXPORTS_PATH

    echo "export BM_TIMEZONE=$BM_TIMEZONE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_USER=$BM_USER" >> $BM_ENV_EXPORTS_PATH
    echo "export WEB_USER=$WEB_USER" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_WEB_GROUP=$BM_WEB_GROUP" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_READ_PERMISSIONS=$BM_READ_PERMISSIONS" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_WRITE_PERMISSIONS=$BM_WRITE_PERMISSIONS" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_DIR=$BM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_LOG_PATH=$BM_SERVER_LOG_PATH" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_APACHE_LOG_PATH=$BM_APACHE_LOG_PATH" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SHAREDMEM_DIR=$BM_SHAREDMEM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_LINKED_STREAM_DIR=$BM_LINKED_STREAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_AUDIO_STREAM_DIR=$BM_AUDIO_STREAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_AUDIO_STREAM_FILE=$BM_AUDIO_STREAM_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_PICAM_DIR=$BM_PICAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_PICAM_STREAM_DIR=$BM_PICAM_STREAM_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_PICAM_STREAM_FILE=$BM_PICAM_STREAM_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_PHPSYSINFO_CONFIG_FILE=$BM_PHPSYSINFO_CONFIG_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVERCONTROL_DIR=$BM_SERVERCONTROL_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_ACTION_DIR=$BM_SERVER_ACTION_DIR" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_ACTION_FILE=$BM_SERVER_ACTION_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_SERVER_ACTION_RESULT_FILE=$BM_SERVER_ACTION_RESULT_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_MODE_LOCK_FILE=$BM_MODE_LOCK_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_CONTROL_MIC_ID_FILE=$BM_CONTROL_MIC_ID_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_CONTROL_CAM_CONNECTED_FILE=$BM_CONTROL_CAM_CONNECTED_FILE" >> $BM_ENV_EXPORTS_PATH
    echo "export BM_DEBUG=$BM_DEBUG" >> $BM_ENV_EXPORTS_PATH

    # Copy environment variables (without 'export') into environment file for services and PHP
    ENV_VAR_EXPORTS=$(cat $BM_ENV_EXPORTS_PATH)
    echo "${ENV_VAR_EXPORTS//'export '/}" > $BM_ENV_PATH
fi

source /home/$BM_USER/.bashrc

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
    mv dist/* $BM_LINKED_SITE_DIR/library/hls-js/
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
    mv anime-$FILENAME_ROOT/lib/* $BM_LINKED_SITE_DIR/library/anime/
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

INSTALL_JS_COOKIE=true
if [[ "$INSTALL_JS_COOKIE" = true ]]; then
    JS_COOKIE_VERSION=3.0.1
    wget -P $BM_LINKED_SITE_DIR/library/js-cookie/js https://github.com/js-cookie/js-cookie/releases/download/v$JS_COOKIE_VERSION/js.cookie.min.js
fi

INSTALL_PICAM=true
if [[ "$INSTALL_PICAM" = true ]]; then
    # Create directories and symbolic links
    sudo install -d -o $BM_USER -g $BM_WEB_GROUP -m $BM_READ_PERMISSIONS $BM_PICAM_DIR{,/archive}

    ln -sfn {$BM_PICAM_STREAM_DIR,$BM_PICAM_DIR}/rec
    ln -sfn {$BM_PICAM_STREAM_DIR,$BM_PICAM_DIR}/hooks
    ln -sfn {$BM_PICAM_STREAM_DIR,$BM_PICAM_DIR}/state

    sudo ln -sfn $BM_SHAREDMEM_DIR $BM_LINKED_STREAM_DIR

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

INSTALL_PHPSYSINFO=true
if [[ "$INSTALL_PHPSYSINFO" = true ]]; then
    PHPSYSINFO_VERSION=3.3.4
    if [[ "$PHPSYSINFO_VERSION" = "latest" ]]; then
        FILENAME="main.zip"
        FOLDER_NAME="phpsysinfo-main"
        DOWNLOAD_URL="https://github.com/phpsysinfo/phpsysinfo/archive/refs/heads/$FILENAME"
    else
        FILENAME="v${PHPSYSINFO_VERSION}.zip"
        FOLDER_NAME="phpsysinfo-$PHPSYSINFO_VERSION"
        DOWNLOAD_URL="https://github.com/phpsysinfo/phpsysinfo/archive/refs/tags/$FILENAME"
    fi
    cd /tmp
    wget $DOWNLOAD_URL
    unzip $FILENAME
    mv $FOLDER_NAME $BM_LINKED_SITE_DIR/library/phpsysinfo
    rm $FILENAME
    cd -

    cp $BM_PHPSYSINFO_CONFIG_FILE{.new,}
    sed -i 's/DEFAULT_DISPLAY_MODE=.*/DEFAULT_DISPLAY_MODE="bootstrap"/g' $BM_PHPSYSINFO_CONFIG_FILE
    sed -i 's/SHOW_PICKLIST_LANG=.*/SHOW_PICKLIST_LANG=false/g' $BM_PHPSYSINFO_CONFIG_FILE
    sed -i 's/SHOW_PICKLIST_TEMPLATE=.*/SHOW_PICKLIST_TEMPLATE=false/g' $BM_PHPSYSINFO_CONFIG_FILE
    sed -i 's/REFRESH=.*/REFRESH=20000/g' $BM_PHPSYSINFO_CONFIG_FILE
    sed -i 's/SENSOR_PROGRAM=.*/SENSOR_PROGRAM="PiTemp"/g' $BM_PHPSYSINFO_CONFIG_FILE
    sed -i 's/SHOW_NETWORK_ACTIVE_SPEED=.*/SHOW_NETWORK_ACTIVE_SPEED="bps"/g' $BM_PHPSYSINFO_CONFIG_FILE
fi

SETUP_SERVICES=true
if [[ "$SETUP_SERVICES" = true ]]; then
    UNIT_DIR=/lib/systemd/system
    LINKED_UNIT_DIR=$BM_DIR/control/services
    SYSTEMCTL=$(which systemctl)

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
User=$BM_USER
Group=$BM_WEB_GROUP
EnvironmentFile=$BM_ENV_PATH
ExecStart=$BM_DIR/control/startup.sh
StandardError=append:$BM_SERVER_LOG_PATH

[Install]
WantedBy=multi-user.target" > $LINKED_UNIT_DIR/$STARTUP_SERVICE_FILENAME

    sudo ln -sfn {$LINKED_UNIT_DIR,$UNIT_DIR}/$STARTUP_SERVICE_FILENAME

    sudo systemctl enable $STARTUP_SERVICE_FILENAME

    CMD_ALIAS='Cmnd_Alias BM_MODES = /usr/bin/vcgencmd get_throttled, /usr/bin/vcgencmd measure_temp,'

    for SERVICE in standby listen audiostream videostream
    do
        SERVICE_ROOT_NAME=bm_$SERVICE
        SERVICE_FILENAME=$SERVICE_ROOT_NAME.service

        echo "[Unit]
Description=Babymonitor $SERVICE service

[Service]
Type=simple
User=$BM_USER
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

GENERATE_BANDWIDTH_TEST_DATA=true
if [[ "$GENERATE_BANDWIDTH_TEST_DATA" = true ]]; then
    $BM_DIR/site/bandwidth_test/generate_bandwidth_test_file.py 10000
fi

INSTALL_SERVER=true
if [[ "$INSTALL_SERVER" = true ]]; then
    # Configure MySQL
    MYSQL_ROOT_PASSWORD=$(python3 -c "import json; f = open('$BM_DIR/config/config.json', 'r'); print(json.load(f)['database']['root_account']['password']); f.close()")
    sudo mysql --user=root <<_EOF_
UPDATE mysql.user SET Password=PASSWORD('$MYSQL_ROOT_PASSWORD') WHERE User='root';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
_EOF_

    # Configure PHP
    PHP_INI_CLI_PATH=$(php -i | grep /.+/php.ini -oE)
    PHP_DIR=$(dirname $(dirname $PHP_INI_CLI_PATH))

    # Make sure inotify PHP extension is loaded
    echo 'extension=inotify.so' | sudo tee $PHP_DIR/mods-available/inotify.ini
    sudo phpenmod inotify

    # Make sure zip PHP extension is loaded
    echo 'extension=zip.so' | sudo tee $PHP_DIR/mods-available/zip.ini
    sudo phpenmod zip

    # Set time zone
    $BM_SERVERCONTROL_DIR/set_php_timezone.sh "$BM_TIMEZONE"

    # Use index.php as index file
    APACHE_CONF_PATH=/etc/apache2/apache2.conf
    echo -e "\nDirectoryIndex index.php" | sudo tee -a $APACHE_CONF_PATH

    # Set Apache log level
    sed -i 's/^LogLevel .*$/LogLevel error/g' $APACHE_CONF_PATH

    # Add main user to www-data group
    sudo adduser $BM_USER $BM_WEB_GROUP

    # Create folders where the group has write permissions
    mkdir -p $BM_SERVER_ACTION_DIR $BM_MODE_LOCK_DIR $BM_LISTEN_COMM_DIR $BM_CONTROL_MIC_DIR $BM_CONTROL_CAM_DIR

    # Make sure mode lock is initially released
    touch $BM_MODE_LOCK_FILE

    # Make sure files to be watched in the comm directory exist
    touch $BM_LISTEN_COMM_DIR/{sound_level.dat,probabilities.json,notification.txt}

    # Ensure permissions are correct in project folder
    sudo chmod -R $BM_READ_PERMISSIONS $BM_DIR
    sudo chown -R $BM_USER:$BM_WEB_GROUP $BM_DIR

    # Set write permissions
    sudo chmod $BM_WRITE_PERMISSIONS $BM_SERVER_ACTION_DIR
    sudo chmod $BM_WRITE_PERMISSIONS $BM_MODE_LOCK_DIR
    sudo chmod $BM_WRITE_PERMISSIONS $BM_LISTEN_COMM_DIR
    sudo chmod $BM_WRITE_PERMISSIONS $BM_PHPSYSINFO_CONFIG_FILE $(dirname $BM_PHPSYSINFO_CONFIG_FILE)
    sudo chmod $BM_WRITE_PERMISSIONS $BM_CONTROL_MIC_DIR
    sudo chmod $BM_WRITE_PERMISSIONS $BM_CONTROL_CAM_DIR

    sudo mkdir -p $SERVER_LOG_DIR
    sudo touch $BM_SERVER_LOG_PATH
    sudo chown $BM_USER:$BM_WEB_GROUP $BM_SERVER_LOG_PATH
    sudo chmod $BM_WRITE_PERMISSIONS $BM_SERVER_LOG_PATH

    sudo touch $BM_APACHE_LOG_PATH
    sudo chown $BM_USER:$BM_WEB_GROUP $BM_APACHE_LOG_DIR $BM_APACHE_LOG_PATH
    sudo chmod $BM_WRITE_PERMISSIONS $BM_APACHE_LOG_DIR $BM_APACHE_LOG_PATH

    # Link site folder to default Apache site root
    sudo ln -s $BM_LINKED_SITE_DIR $BM_SITE_DIR

    # Remove default site
    sudo a2dissite 000-default
    sudo rm -r /var/www/html
    sudo rm -f /etc/apache2/sites-available/000-default.conf
    sudo rm -f /etc/apache2/sites-available/default-ssl.conf

    # Enable SSL module
    sudo a2enmod ssl

    $BM_SERVERCONTROL_DIR/create_ssl_key.sh "$BM_HOSTNAME"

    # Setup new site
    echo "<VirtualHost *:80>
    DocumentRoot $BM_SITE_DIR
    ErrorLog $BM_APACHE_LOG_PATH
    CustomLog $APACHE_LOG_DIR/access.log combined
    ServerName $BM_HOSTNAME.local
    ServerAlias $BM_HOSTNAME.*

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
    ServerName $BM_HOSTNAME.local
    ServerAlias $BM_HOSTNAME.*

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
</VirtualHost>" | sudo tee /etc/apache2/sites-available/babymonitor.conf
    sudo a2ensite babymonitor

    # Configure rotation of Apache log
    sudo sed -i "s/create .*$/create $BM_WRITE_PERMISSIONS $BM_USER $BM_WEB_GROUP/g" /etc/logrotate.d/apache2

    # Configure rotation of babymonitor log
    echo "$BM_SERVER_LOG_PATH {
    daily
    missingok
    size 10M
    rotate 5
    compress
    delaycompress
    notifempty
    create $BM_WRITE_PERMISSIONS $BM_USER $BM_WEB_GROUP
}" | sudo tee /etc/logrotate.d/babymonitor

    # Restart Apache
    sudo systemctl restart apache2
fi

INITIALIZE_DATABASE=true
if [[ "$INITIALIZE_DATABASE" = true ]]; then
    $BM_DIR/site/config/init/init_database.sh
fi

