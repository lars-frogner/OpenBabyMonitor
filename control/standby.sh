#!/bin/bash

SCRIPT_DIR=$(dirname $(readlink -f $0))

$SCRIPT_DIR/standby.py
