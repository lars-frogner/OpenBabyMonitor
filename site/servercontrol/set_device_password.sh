#!/bin/bash

NEW_PASSWORD="$1"

echo "$BM_USER:$NEW_PASSWORD" | chpasswd
