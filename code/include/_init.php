<?php

define("_APP_DIR", dirname(__FILE__));

set_time_limit(0);  // turned off
// Using value from global .htaccess
// error_reporting(E_ALL); 

require_once(_APP_DIR . "/lib/_init.php");

require_once(_APP_DIR . "/global.php");
require_once(_APP_DIR . "/custom_app.php");

require_once(_APP_DIR . "/_init_app_classes.php");
require_once(_APP_DIR . "/_init_db_classes.php");

?>