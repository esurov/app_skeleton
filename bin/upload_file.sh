#!/bin/bash

BEFORE_INSTALL_DIR=`pwd`
cd `dirname $0`

. _vars.sh

echo Pass: $HOST_PASSWORD

scp $1 "$HOST_USER@$HOST_NAME:$HOST_HOME_DIR"

cd $BEFORE_INSTALL_DIR
