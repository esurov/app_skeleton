#!/bin/bash

. vars.sh

echo Pass: $HOST_PASSWORD

/usr/bin/ssh -1 "$HOST_USER@$HOST_NAME"
