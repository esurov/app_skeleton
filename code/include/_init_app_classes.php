<?php

$app_classes["class_paths"] = array_merge(
    array(
        _APP_DIR,
        _APP_DIR . "/components",
    ),
    $app_classes["class_paths"]
);

$app_classes["classes"] += array(
    // App object and component classes 
    "SampleComponent" => array(
        "filename" => "__sample_component.php",
        "required_classes" => array("AppComponent"),
    ),
    "SampleComponent2" => array(
        "filename" => "__sample_component.php",
        "required_classes" => array("SampleComponent"),
    ),

    // App DbObject-derived classes (non-creatable tables)
    // Used as parent classes or for print_values/print_form_values feature
    "CustomDbObject" => array(
        "filename" => "custom_db_object.php",
        "required_classes" => array("DbObject"),
        "params" => array("table_name" => "", "create" => false),
    ),
    "SampleTable" => array(
        "filename" => "_sample_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "_sample", "create" => false),
    ),
    "ContactInfo" => array(
        "filename" => "contact_info.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "contact_info", "create" => false),
    ),

    // App DbObject-derived classes (real db tables)
    "Sample2Table" => array(
        "filename" => "_sample2_table.php",
        "required_classes" => array("SampleTable"),
        "params" => array("table_name" => "_sample2"),
    ),
    "ImageTable" => array(
        "filename" => "image_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "image"),
    ),
    "FileTable" => array(
        "filename" => "file_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "file"),
    ),
    "NewsArticleTable" => array(
        "filename" => "news_article_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "news_article"),
    ),
);

?>