<?php

$components = array(
    "dirs" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/components",
    ),
    "components_info" => array(
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