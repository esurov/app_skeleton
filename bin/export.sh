#!/bin/bash
# Export specified version of the script.

. vars.sh

rm -r -f $TMP_DIR
mkdir $TMP_DIR
cd $TMP_DIR

cvs export -kv -r $PROJ_RELEASE -d $PROJ_RELEASE $PROJ_NAME/code >> export.log

cd $PROJ_RELEASE
tar zcf ../$PROJ_RELEASE.tgz .
cd ../..
