<?php

set_time_limit(0);  // turned off
error_reporting(E_ALL);

$__tmp_saved_app_dir = getcwd();

chdir(dirname(__FILE__));

require_once("lib/_init.php");

require_once("global.php");
require_once("custom_db_object.php");
require_once("custom_app.php");

$tables = array(
    "_example" => "Example",
    "image" => "Image",
    "news_article" => "NewsArticle",
);

foreach (array_keys($tables) as $table_name) {
    require_once("tables/{$table_name}.php");
}

chdir($__tmp_saved_app_dir);

ini_set("session.gc_maxlifetime", 14400);
session_start();

?>