#!/bin/bash

. _vars.sh

echo Pass: $HOST_PASSWORD

ssh -1 "$HOST_USER@$HOST_NAME"
