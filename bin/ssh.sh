#!/bin/bash

BEFORE_INSTALL_DIR=`pwd`
cd `dirname $0`

. _vars.sh

echo Pass: $HOST_PASSWORD

ssh -1 "$HOST_USER@$HOST_NAME"

cd $BEFORE_INSTALL_DIR
