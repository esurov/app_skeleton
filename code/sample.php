<?php

require_once("include/_init.php");
require_once("include/__sample_app.php");

$app =& new SampleApp();
$app->init();
$app->run();

?>