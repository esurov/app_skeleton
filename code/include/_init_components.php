<?php

$components["class_paths"] = array_merge(
    array(
        _APP_DIR,
        _APP_DIR . "/components",
    ),
    $components["class_paths"]
);

$components["classes"] += array(
    "SampleComponent" => array(
        "file_name" => "__sample_component.php",
    ),
    "SampleComponent2" => array(
        "file_name" => "__sample_component.php",
        "required_classes" => array("SampleComponent"),
    ),
);

?>