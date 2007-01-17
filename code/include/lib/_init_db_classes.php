<?php

$db_classes = array(
    "class_paths" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/tables",
    ),
    "classes" => array(
        // Core DbObject classes
        "DbObject" => array(
            "filename" => "db_object.php",
            "params" => array("create" => false),
        ),
        "CustomDbObject" => array(
            "filename" => "_custom_db_object.php",
            "required_classes" => array("DbObject"),
            "params" => array("create" => false),
        ),

        // Common DbObject classes
        "FileTable" => array(
            "filename" => "file_table.php",
            "required_classes" => array("CustomDbObject"),
            "params" => array("table_name" => "file", "create" => false),
        ),
        "ImageTable" => array(
            "filename" => "image_table.php",
            "required_classes" => array("FileTable"),
            "params" => array("table_name" => "image", "create" => false),
        ),
    ),
);

?>