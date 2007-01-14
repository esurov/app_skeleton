<?php

$objects["class_paths"] = array_merge(
    array(
        _APP_DIR,
        _APP_DIR . "/components",
    ),
    $objects["class_paths"]
);

$objects["classes"] += array(
    "SampleComponent" => array(
        "filename" => "__sample_component.php",
        "required_classes" => array("AppComponent"),
    ),
    "SampleComponent2" => array(
        "filename" => "__sample_component.php",
        "required_classes" => array("SampleComponent"),
    ),
);

?>