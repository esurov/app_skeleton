<?php

$db_classes["class_paths"] = array_merge(
    array(
        _APP_DIR . "/tables",
    ),
    $db_classes["class_paths"]
);

$db_classes["classes"] = array(
    // DbObject-derived parent classes

    // DbObject-derived classes defined in lib
    "FileTable" => array(
        "filename" => "file_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "file", "create" => true),
    ),
    "ImageTable" => array(
       "filename" => "image_table.php",
       "required_classes" => array("FileTable"),
       "params" => array("table_name" => "image", "create" => true),
    ),

    // DbObject-derived classes defined in app
    "UserTable" => array(
        "filename" => "user_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "user", "create" => true),
    ),

    "NewsArticleTable" => array(
        "filename" => "news_article_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "news_article", "create" => true),
    ),

    // These are fake tables (no '_table' suffix in filename),
    // for now are just used here because of their print_values/print_form_values feature
    "ContactInfoTable" => array(
        "filename" => "contact_info.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "contact_info", "create" => false),
    ),

    // Note: Declarations below are just for sample purposes and
    // should be removed in real app
    "SampleTable" => array(
        "filename" => "__sample_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "__sample", "create" => false),
    ),
    "Sample2Table" => array(
        "filename" => "__sample2_table.php",
        "required_classes" => array("SampleTable"),
        "params" => array("table_name" => "__sample2", "create" => true),
    ),

) + $db_classes["classes"];

?>