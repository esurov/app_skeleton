<?php

set_time_limit(0);  // turned off
error_reporting(E_ALL);

define("_APP_DIR", dirname(__FILE__));

require_once(_APP_DIR . "/lib/_init.php");

require_once(_APP_DIR . "/global.php");
require_once(_APP_DIR . "/custom_app.php");

require_once(_APP_DIR . "/_init_objects.php");

ini_set("session.gc_maxlifetime", 14400);
session_start();

?>