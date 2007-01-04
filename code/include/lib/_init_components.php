<?php

$components = array(
    "class_paths" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/components",
    ),
    "classes" => array(
        "ObjectsList" => array(
            "file_name" => "objects_list.php",
        ),
        "QueryObjectsList" => array(
            "file_name" => "objects_list.php",
        ),
        "PagedQueryObjectsList" => array(
            "file_name" => "objects_list.php",
        ),
        "Pager" => array(
            "file_name" => "pager.php",
        ),
        "Xml" => array(
            "file_name" => "xml.php",
            "need_app" => false,
        ),
        "Menu" => array(
            "file_name" => "menu.php",
            "required_classes" => array("Xml"),
        ),
        "LangMenu" => array(
            "file_name" => "lang_menu.php",
        ),
        "PHPMailer" => array(
            "file_name" => "phpmailer.php",
            "need_app" => false,
        ),
        "ImageMagick" => array(
            "file_name" => "image_magick.php",
            "need_app" => false,
        ),
    ),
);

?>