<?php

require_once("include/_init.php");
require_once("include/__test_app.php");

$app = new TestApp($tables);
$app->run();

?>