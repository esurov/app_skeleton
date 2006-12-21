<?php

$components = array(
    "dirs" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/components",
    ),
    "components_info" => array(
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