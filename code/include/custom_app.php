<?php

class CustomApp extends App {

    function CustomApp($app_class_name, $app_name) {
        parent::App($app_class_name, $app_name);
    }

    function &create_email_sender() {
        $email_sender =& $this->create_object("CustomPHPMailer");
        $email_sender->IsSendmail();
        $email_sender->IsHTML($this->get_config_value("email_is_html"));
        $email_sender->CharSet = $this->get_config_value("email_charset");
        return $email_sender;
    }

}

?>