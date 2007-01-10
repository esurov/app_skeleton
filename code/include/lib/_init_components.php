<?php

$components = array(
    "class_paths" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/components",
    ),
    "classes" => array(
        "Component" => array(
            "file_name" => "component.php",
        ),
        "TemplateComponent" => array(
            "file_name" => "component.php",
            "required_classes" => array("Component"),
        ),
        "ObjectTemplateComponent" => array(
            "file_name" => "component.php",
            "required_classes" => array("TemplateComponent"),
        ),
        "ObjectView" => array(
            "file_name" => "object_view.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
//        "ObjectEdit" => array(
//            "file_name" => "object_edit.php",
//            "required_classes" => array("ObjectTemplateComponent"),
//        ),
        "ObjectsList" => array(
            "file_name" => "objects_list.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
        "QueryObjectsList" => array(
            "file_name" => "objects_list.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
        "PagedQueryObjectsList" => array(
            "file_name" => "objects_list.php",
            "required_classes" => array("ObjectTemplateComponent"),
        ),
        "Pager" => array(
            "file_name" => "pager.php",
            "required_classes" => array("Component"),
        ),
        "Xml" => array(
            "file_name" => "xml.php",
            "need_app" => false,
        ),
        "Menu" => array(
            "file_name" => "menu.php",
            "required_classes" => array("TemplateComponent", "Xml"),
        ),
        "LangMenu" => array(
            "file_name" => "lang_menu.php",
            "required_classes" => array("TemplateComponent"),
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