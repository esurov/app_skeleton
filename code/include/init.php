<?php

set_time_limit(0);  // turned off

error_reporting(E_ALL);

// phplib:
require_once("include/lib/config.php");
require_once("include/lib/template.php");
require_once("include/lib/logger.php");
require_once("include/lib/stdlib.php");
require_once("include/lib/sql.php");
require_once("include/lib/dbobject.php");
require_once("include/lib/pager.php");

require_once("include/lib/app.php");
require_once("include/lib/sql_app.php");
require_once("include/lib/page_script.php");

require_once("include/lib/session.php");
require_once("include/lib/session_login_state.php");
require_once("include/lib/StatusMsg.php");

require_once("include/custom_dbobject.php");
require_once("include/custom_page_script.php");
require_once('include/custom_app.php');

require_once("include/functions.php");

// tables:
$tables = array(
    "article" => "Article",
);

// include tables
foreach (array_keys($tables) as $table) {
    require_once("tables/{$table}.php");
}

session_start();

$app = new CustomApp($tables);

?>