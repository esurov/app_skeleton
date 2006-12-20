<?php

set_time_limit(0);  // turned off
error_reporting(E_ALL);

$__tmp_saved_app_dir = getcwd();

define("_APP_DIR", dirname(__FILE__));
define("_APP_TABLES_DIR", _APP_DIR . "/tables");

chdir(_APP_DIR);

require_once("lib/_init.php");

require_once("global.php");
require_once("custom_db_object.php");
require_once("custom_app.php");

$tables = array(
    "_example" => "Example",
    "image" => "Image",
    "file" => "File",
    "news_article" => "NewsArticle",
    "contact_info" => "ContactInfo",
);

require_once("_init_components.php");

chdir($__tmp_saved_app_dir);

ini_set("session.gc_maxlifetime", 14400);
session_start();

?>