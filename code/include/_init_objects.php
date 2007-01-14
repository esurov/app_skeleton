<?php

$objects["class_paths"] = array_merge(
    array(
        _APP_DIR,
        _APP_DIR . "/components",
        _APP_DIR . "/tables",
    ),
    $objects["class_paths"]
);

$objects["classes"] += array(
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
        "params" => array("table_name" => "custom_db_object", "create" => false),
    ),
    "SampleTable" => array(
        "filename" => "_sample_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "_sample_table", "create" => false),
    ),
    "ContactInfo" => array(
        "filename" => "contact_info.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "contact_info", "create" => false),
    ),

    // App DbObject-derived classes (real db tables)
    "SampleTable2" => array(
        "filename" => "_sample_table2.php",
        "required_classes" => array("SampleTable"),
        "params" => array("table_name" => "_sample_table2"),
    ),
    "Image" => array(
        "filename" => "image.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "image"),
    ),
    "File" => array(
        "filename" => "file.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "file"),
    ),
    "NewsArticle" => array(
        "filename" => "news_article.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "news_article"),
    ),
);

?>