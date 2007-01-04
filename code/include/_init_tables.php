<?php

$tables = array(
    "class_paths" => array(
        _APP_DIR . "/tables",
    ),
    "classes" => array(
        "_sample_table" => array(
            "class_name" => "SampleTable",
            "file_name" => "_sample_table.php",
            "create" => false,
        ),
        "_sample_table2" => array(
            "class_name" => "SampleTable2",
            "file_name" => "_sample_table2.php",
            "required_classes" => array("_sample_table"),
        ),
        "image" => array(
            "class_name" => "Image",
            "file_name" => "image.php",
        ),
        "file" => array(
            "class_name" => "File",
            "file_name" => "file.php",
        ),
        "news_article" => array(
            "class_name" => "NewsArticle",
            "file_name" => "news_article.php",
        ),
        "contact_info" => array(
            "class_name" => "ContactInfo",
            "file_name" => "contact_info.php",
            "create" => false,
        ),
    ),
);

?>