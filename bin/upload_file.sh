#!/bin/bash

. _vars.sh

echo Pass: $HOST_PASSWORD

scp $1 "$HOST_USER@$HOST_NAME:$HOST_HOME_DIR"
