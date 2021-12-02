#!/bin/bash

NAME="$1"
VALUE="$2"

if [[ -z "$NAME" ]]; then
    echo 'No environment variable name provided' 1>&2
    exit 1
fi

if [[ -z "$VALUE" ]]; then
    echo 'No environment variable value provided' 1>&2
    exit 1
fi

BM_ENV_EXPORTS_PATH=$BM_DIR/env/envvar_exports
BM_ENV_PATH=$BM_DIR/env/envvars

ESCAPED_VALUE=$(printf '%s\n' "$VALUE" | sed -e 's/[\/&]/\\&/g')
sed -i "s/^export $NAME=.*$/export $NAME=$ESCAPED_VALUE/g" $BM_ENV_EXPORTS_PATH
sed -i "s/^$NAME=.*$/$NAME=$ESCAPED_VALUE/g" $BM_ENV_PATH
