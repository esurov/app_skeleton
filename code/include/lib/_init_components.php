<?php

$components = array(
    "dirs" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/components",
    ),
    "components_info" => array(
        "objects_list" => array(
            "class_name" => "ObjectsList",
            "file_name" => "objects_list.php",
        ),
        "query_objects_list" => array(
            "class_name" => "QueryObjectsList",
            "file_name" => "objects_list.php",
        ),
//        "objects_paged_query_list" => array(
//            "class_name" => "ObjectsPagedQueryList",
//            "file_name" => "objects_list.php",
//        ),
        "xml" => array(
            "class_name" => "Xml",
            "file_name" => "xml.php",
            "need_app" => false,
        ),
        "menu" => array(
            "class_name" => "Menu",
            "file_name" => "menu.php",
            "required_components" => array("xml"),
        ),
        "lang_menu" => array(
            "class_name" => "LangMenu",
            "file_name" => "lang_menu.php",
        ),
        "email_sender" => array(
            "class_name" => "PHPMailer",
            "file_name" => "phpmailer.php",
            "need_app" => false,
        ),
        "image_magick" => array(
            "class_name" => "ImageMagick",
            "file_name" => "image_magick.php",
            "need_app" => false,
        ),
    ),
);

?>