#!/bin/bash

cd $TMP_DIR

scp $PROJ_RELEASE.tgz $HOST_USER@$HOST_NAME:$HOST_HOME_DIR

ssh -1 "$HOST_USER@$HOST_NAME"  \
"cd $HOST_HOME_DIR && \
tar xzf $PROJ_RELEASE.tgz && \
rm -f $PROJ_RELEASE.tgz"

cd ..
