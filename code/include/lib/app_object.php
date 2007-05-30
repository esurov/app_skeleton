<?php

class Object {
    
    var $_class_name;
    var $_class_name_without_suffix;

    function Object() {
    }

    function _init($params) {
    }

    function set_class_name($class_name_without_suffix, $class_name_suffix = "") {
        $this->_class_name = "{$class_name_without_suffix}{$class_name_suffix}";
        $this->_class_name_without_suffix = $class_name_without_suffix;
    }

    function get_class_name() {
        return $this->_class_name;
    }

    function get_class_name_without_suffix() {
        return $this->_class_name_without_suffix;
    }

}

class AppObject extends Object {
    
    var $app;

    function AppObject() {
        parent::Object();
    }

    function _init($params) {
        parent::_init($params);
    }
//
    function set_app(&$app) {
        $this->app =& $app;
    }
//
    function &create_object($obj_class_name, $obj_params = array()) {
        return $this->app->create_object($obj_class_name, $obj_params);
    }
//
    function get_config_value($name, $default_value = null) {
        return $this->app->get_config_value($name, $default_value);
    }
//
    function get_log_debug_level() {
        return $this->app->get_log_debug_level();
    }

    function write_log($message, $debug_level, $class_name = null) {
        $this->app->write_log(
            $message,
            $debug_level,
            is_null($class_name) ? $this->get_class_name() : $class_name
        );
    }
//
    function process_fatal_error($message) {
        $this->write_log("FATAL ERROR: {$message}", DL_FATAL_ERROR);

        if ($this->get_log_debug_level() >= DL_DEBUG) {
            $class_name = $this->get_class_name();
            $message = "[{$class_name}] {$message}";
        } else {
            $message = "";
        }
        trigger_error($message, E_USER_ERROR);
    }

    function process_fatal_error_required_param_not_found($param_name, $message_prefix = "") {
        if ($message_prefix != "") {
            $message_prefix = "{$message_prefix}: ";
        }
        $this->process_fatal_error(
            "{$message_prefix}Required param '{$param_name}' not found!"
        );
    }
//
    function get_lang_str($resource, $resource_params = null) {
        return $this->app->get_lang_str($resource, $resource_params);
    }

}

?>