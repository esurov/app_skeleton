#!/bin/bash

. vars.sh

. export.sh

cd $TMP_DIR

echo Pass: $HOST_PASSWORD

/usr/bin/ssh -1 "$HOST_USER@$HOST_NAME"  \
"tar xfz - --directory=$HOST_HOME && \
rm -f $HOST_HOME/include/_build_browse_info.bat && \
rm -f $HOST_HOME/include/.tags-autoload \
" < $PROJ_RELEASE.tgz

cd ..
