#!/bin/bash

# Common configuration variables for cvs export scripts.
PROJ_NAME='cvs_project_module_name'
PROJ_RELEASE='HEAD'

TMP_DIR='tmp'

EXCLUDE_FROM_CVS_EXPORT_ALWAYS='include/_build_browse_info.bat include/.tags-autoload config/.cvsignore include/.cvsignore log/.cvsignore'
EXCLUDE_FROM_CVS_EXPORT='images swf'

# Hosting configuration variables for install script.
HOST_NAME='domain_name'
HOST_USER='domain_user'
HOST_PASSWORD='domain_password'
HOST_HOME_DIR='httpdocs'
