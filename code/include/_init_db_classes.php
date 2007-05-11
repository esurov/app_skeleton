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
    "File" => array(
        "filename" => "file_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "file", "create" => true),
    ),
    "Image" => array(
       "filename" => "image_table.php",
       "required_classes" => array("File"),
       "params" => array("table_name" => "image", "create" => true),
    ),

    // DbObject-derived classes defined in app
    "User" => array(
        "filename" => "user_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "user", "create" => true),
    ),
    "NewsArticle" => array(
        "filename" => "news_article_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "news_article", "create" => true),
    ),
    "Category1" => array(
        "filename" => "category1_table.php",
        "required_classes" => array("OrderedDbObject"),
        "params" => array("table_name" => "category1", "create" => true),
    ),
    "Category2" => array(
        "filename" => "category2_table.php",
        "required_classes" => array("OrderedDbObject"),
        "params" => array("table_name" => "category2", "create" => true),
    ),
    "Category3" => array(
        "filename" => "category3_table.php",
        "required_classes" => array("OrderedDbObject"),
        "params" => array("table_name" => "category3", "create" => true),
    ),
    "Product" => array(
        "filename" => "product_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "product", "create" => true),
    ),

    // These are fake tables (no '_table' suffix in filename),
    // for now are just used here because of their print_values/print_form_values feature
    "ContactInfo" => array(
        "filename" => "contact_info.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "contact_info", "create" => false),
    ),

    // Note: Declarations below are just for sample purposes and
    // should be removed in real app
    "Sample" => array(
        "filename" => "__sample_table.php",
        "required_classes" => array("CustomDbObject"),
        "params" => array("table_name" => "__sample", "create" => false),
    ),
    "Sample2" => array(
        "filename" => "__sample2_table.php",
        "required_classes" => array("Sample"),
        "params" => array("table_name" => "__sample2", "create" => true),
    ),

) + $db_classes["classes"];

?>