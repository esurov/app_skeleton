<?php

$__tmp_saved_lib_dir = getcwd();

chdir(dirname(__FILE__));

require_once("global.php");

require_once("session.php");
require_once("config.php");
require_once("template.php");
require_once("logger.php");

require_once("db.php");
require_once("select_query.php");
require_once("db_result.php");
require_once("db_object.php");

require_once("http_response.php");
require_once("pager.php");
require_once("status_msg.php");

require_once("phpmailer.php");
require_once("image_magick.php");

require_once("app.php");

chdir($__tmp_saved_lib_dir);

?>