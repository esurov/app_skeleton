<?php

$components = array(
    "class_paths" => array(
        _APP_LIB_DIR,
        _APP_LIB_DIR . "/components",
    ),
    "classes" => array(
        "Component" => array(
            "filename" => "component.php",
        ),
        "TemplateComponent" => array(
            "filename" => "component.php",
            "required_classes" => array("Component"),
        ),
        "ObjectTemplateComponent" => array(
            "filename" => "component.php",
            "required_classes" => array("TemplateComponent"),
        ),
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
            "required_classes" => array("Component"),
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
        "PHPMailer" => array(
            "filename" => "phpmailer.php",
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
            "required_classes" => array("Component"),
        ),
    ),
);

?>