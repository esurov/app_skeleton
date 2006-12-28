<?php

$components["dirs"] = array_merge(
    array(
        _APP_DIR,
        _APP_DIR . "/components",
    ),
    $components["dirs"]
);

$components_info = array(
    "sample_component" => array(
        "class_name" => "SampleComponent",
        "file_name" => "__sample_component.php",
    ),
    "sample_component2" => array(
        "class_name" => "SampleComponent2",
        "file_name" => "__sample_component.php",
        "required_components" => array("sample_component"),
    ),
);

$components["components_info"] += $components_info;

?>