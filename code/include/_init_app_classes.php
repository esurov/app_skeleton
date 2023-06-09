<?php

$app_classes["class_paths"] = array_merge(
    array(
        _APP_DIR . "/components",
    ),
    $app_classes["class_paths"]
);

$app_classes["classes"] += array(
    // App object and component classes 
    "CategoryBrowser" => array(
        "filename" => "category_browser.php",
        "required_classes" => array("TemplateComponent"),
    ),
    "SecurityImageGenerator" => array(
        "filename" => "security_image_generator.php",
        "required_classes" => array("AppComponent"),
    ),
    "HtmlToPdfConverter" => array(
        "filename" => "pdf/html_to_pdf_converter.php",
        "required_classes" => array("AppComponent"),
    ),

    // Note: Declarations below are just for sample purposes and
    // should be removed in real app
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