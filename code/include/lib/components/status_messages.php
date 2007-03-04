<?php

class StatusMessages extends TemplateComponent {

    var $messages;

    function _init($params) {
        parent::_init($params);

        $this->messages = array();
    }
//
    function _print_values() {
        parent::_print_values();

        return $this->_print_status_messages();
    }

    function _print_status_messages() {
        foreach ($this->messages as $message) {
            $this->_print_status_message($message);
        }
        return $this->app->print_file("{$this->templates_dir}/body.html", "status_messages");
    }

    function _print_status_message($message) {
        $this->app->print_raw_values(array(
            "text" => $this->get_lang_str($message->resource, $message->resource_params),
            "type" => $message->type,
        ));
        return $this->app->print_file("{$this->templates_dir}/item.html", "items");
    }
//
    function add($status_message) {
        $this->messages[] = $status_message;
    }

}

?>