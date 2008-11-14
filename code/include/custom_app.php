<?php

class CustomApp extends App {

    function CustomApp($app_class_name, $app_name) {
        parent::App($app_class_name, $app_name);
    }
//
    function action_get_security_image() {
        $security_image_generator =& $this->create_object("SecurityImageGenerator");
        $image =& $security_image_generator->get_current_image();

        $this->response = new BinaryContentResponse(
            $image->get_mime_type(),
            $image->get_content(),
            $image->get_content_length()
        );
        $this->response->add_no_cache_headers();
    }

}

?>