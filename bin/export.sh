#!/bin/bash

. _vars.sh

. _cvs_export.sh

cd $TMP_DIR/$PROJ_RELEASE
rm -rf $EXCLUDE_FROM_CVS_EXPORT
cd ../..

. _compress.sh
