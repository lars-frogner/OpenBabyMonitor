#!/bin/bash

HOSTNAME=${1:-$BM_HOSTNAME}

echo "[req]
default_bits  = 2048
distinguished_name = req_distinguished_name
req_extensions = req_ext
x509_extensions = v3_req
prompt = no

[req_distinguished_name]
commonName = $HOSTNAME.local: Self-signed certificate

[req_ext]
subjectAltName = @alt_names

[v3_req]
subjectAltName = @alt_names

[alt_names]
DNS.1 = $HOSTNAME.local
DNS.2 = $HOSTNAME.home
DNS.3 = $HOSTNAME.lan
" > /tmp/$HOSTNAME.cnf

SSL_KEY_PATH=/etc/ssl/private/$HOSTNAME.key
SSL_CERT_PATH=/etc/ssl/certs/$HOSTNAME.crt
sudo openssl req -x509 -nodes -days 36524 -newkey rsa:2048 -keyout $SSL_KEY_PATH -out $SSL_CERT_PATH -config $HOSTNAME.cnf
rm /tmp/$HOSTNAME.cnf
