#!/bin/bash

rm -r -f $TMP_DIR
mkdir $TMP_DIR

cd $TMP_DIR

cvs export -kv -r $PROJ_RELEASE -d $PROJ_RELEASE $PROJ_NAME/code >> export.log

cd $PROJ_RELEASE

rm -rf $EXCLUDE_FROM_CVS_EXPORT_ALWAYS

cd ../..
