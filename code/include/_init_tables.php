<?php

$tables = array(
    "class_paths" => array(
        _APP_DIR . "/tables",
    ),
    "classes" => array(
        "_sample_table" => array(
            "class" => "SampleTable",
            "filename" => "_sample_table.php",
            "create" => false,
        ),
        "_sample_table2" => array(
            "class" => "SampleTable2",
            "filename" => "_sample_table2.php",
            "required_classes" => array("_sample_table"),
        ),
        "image" => array(
            "class" => "Image",
            "filename" => "image.php",
        ),
        "file" => array(
            "class" => "File",
            "filename" => "file.php",
        ),
        "news_article" => array(
            "class" => "NewsArticle",
            "filename" => "news_article.php",
        ),
        "contact_info" => array(
            "class" => "ContactInfo",
            "filename" => "contact_info.php",
            "create" => false,
        ),
    ),
);

?>