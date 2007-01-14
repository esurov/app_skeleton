<?php

$objects = array(
    "class_paths" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/components",
    ),
    "classes" => array(
        // Core classes
        "Logger" => array(
            "filename" => "logger.php",
        ),
        "MySqlDb" => array(
            "filename" => "db_mysql.php",
        ),
        "MySqlDbResult" => array(
            "filename" => "db_mysql.php",
        ),
        "AppComponent" => array(
            "filename" => "app_component.php",
        ),
        "TemplateComponent" => array(
            "filename" => "app_component.php",
            "required_classes" => array("AppComponent"),
        ),
        "ObjectTemplateComponent" => array(
            "filename" => "app_component.php",
            "required_classes" => array("TemplateComponent"),
        ),
        
        // App objects and component classes
        "ObjectView" => array(
            "filename" => "object_view.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
//        "ObjectEdit" => array(
//            "filename" => "object_edit.php",
//            "required_classes" => array("ObjectTemplateComponent"),
//        ),
        "ObjectsList" => array(
            "filename" => "objects_list.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
        "QueryObjectsList" => array(
            "filename" => "objects_list.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
        "PagedQueryObjectsList" => array(
            "filename" => "objects_list.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
        "Pager" => array(
            "filename" => "pager.php",
            "required_classes" => array("AppComponent"),
        ),
        "Xml" => array(
            "filename" => "xml.php",
        ),
        "Menu" => array(
            "filename" => "menu.php",
            "required_classes" => array("TemplateComponent", "Xml"),
        ),
        "LangMenu" => array(
            "filename" => "lang_menu.php",
            "required_classes" => array("TemplateComponent"),
        ),
        "InMemoryImage" => array(
            "filename" => "image.php",
        ),
        "FilesystemImage" => array(
            "filename" => "image.php",
        ),
        "UploadedImage" => array(
            "filename" => "image.php",
        ),
        "ImageMagickWrapper" => array(
            "filename" => "image_processor.php",
            "required_classes" => array("AppComponent"),
        ),

        // Third-party classes
        "PHPMailer" => array(
            "filename" => "phpmailer.php",
        ),
    ),
);

?>