<?php

define("_APP_START_MICROTIME", microtime());
define("_APP_LIB_DIR", dirname(__FILE__));

require_once(_APP_LIB_DIR . "/global.php");

// Core classes
require_once(_APP_LIB_DIR . "/config.php");
require_once(_APP_LIB_DIR . "/select_query.php");
require_once(_APP_LIB_DIR . "/http_response.php");
require_once(_APP_LIB_DIR . "/status_msg.php");

require_once(_APP_LIB_DIR . "/app_object.php");
require_once(_APP_LIB_DIR . "/app.php");

require_once(_APP_LIB_DIR . "/_init_app_classes.php");
require_once(_APP_LIB_DIR . "/_init_db_classes.php");

?>