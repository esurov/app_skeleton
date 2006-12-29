<?php

$components["dirs"] = array_merge(
    array(
        _APP_DIR,
        _APP_DIR . "/components",
    ),
    $components["dirs"]
);

$components_info = array(
    "SampleComponent" => array(
        "file_name" => "__sample_component.php",
    ),
    "SampleComponent2" => array(
        "file_name" => "__sample_component.php",
        "required_components" => array("SampleComponent"),
    ),
);

$components["components_info"] += $components_info;

?>