<?php

$components["dirs"] = array_merge(
    array(
        _APP_DIR,
        _APP_DIR . "/components",
    ),
    $components["dirs"]
);

$components_info = array(
    "component_example" => array(
        "class_name" => "ComponentExample",
        "file_name" => "__component_example.php",
    ),
    "component_example2" => array(
        "class_name" => "ComponentExample2",
        "file_name" => "__component_example.php",
        "required_components" => array("component_example"),
    ),
);

$components["components_info"] += $components_info;

?>