#!/bin/bash

NEW_TIMEZONE="$1"

if [[ -z "$NEW_TIMEZONE" ]]; then
    echo 'No time zone provided' 1>&2
    exit 1
fi

PHP_INI_CLI_PATH=$(php -i | grep /.+/php.ini -oE)
PHP_DIR=$(dirname $(dirname $PHP_INI_CLI_PATH))
PHP_INI_APACHE_PATH=$PHP_DIR/apache2/php.ini

ESCAPED_TIMEZONE=${NEW_TIMEZONE/'/'/'\/'}
sudo sed -i "s/;*date.timezone =.*$/date.timezone = $ESCAPED_TIMEZONE/g" $PHP_INI_CLI_PATH
sudo sed -i "s/;*date.timezone =.*$/date.timezone = $ESCAPED_TIMEZONE/g" $PHP_INI_APACHE_PATH
