#!/bin/bash

BEFORE_INSTALL_DIR=`pwd`
cd `dirname $0`

. _vars.sh

. export.sh

echo Pass: $HOST_PASSWORD

. _install.sh

cd $BEFORE_INSTALL_DIR
