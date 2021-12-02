#!/bin/bash

HOSTNAME=${1:-$BM_HOSTNAME}

SSL_KEY_PATH=/etc/ssl/private/$HOSTNAME.key
SSL_CERT_PATH=/etc/ssl/certs/$HOSTNAME.crt
sudo rm $SSL_KEY_PATH $SSL_CERT_PATH
