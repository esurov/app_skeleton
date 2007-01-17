<?php

class AppComponent extends AppObject {

    function _init($params) {
        parent::_init($params);
    }

}

class TemplateComponent extends AppComponent {

    var $templates_dir;
    var $templates_ext;
    var $template_var;

    function _init($params) {
        parent::_init($params);

        $this->templates_dir = get_param_value($params, "templates_dir", null);
        if (is_null($this->templates_dir)) {
            $this->app->process_fatal_error(
                "Required param 'templates_dir' not found!"
            );
        }
        $this->templates_ext = get_param_value($params, "templates_ext", "html");
        $this->template_var = get_param_value($params, "template_var", null);
    }

    function print_values() {
        $this->_on_before_print_values();
        $content = $this->_print_values();
        $this->_on_after_print_values();
        return $content;
    }

    function _print_values() {
    }

    function _on_before_print_values() {
    }

    function _on_after_print_values() {
    }

}

class ObjectTemplateComponent extends TemplateComponent {

    var $obj;

    var $template_var_prefix;

    var $context;
    var $custom_params;

    function _init($params) {
        parent::_init($params);

        $this->obj = get_param_value($params, "obj", null);

        $this->template_var_prefix = get_param_value(
            $params,
            "template_var_prefix",
            is_null($this->obj) ? "" : $this->obj->_table_name
        );
        
        $this->context = get_param_value($params, "context", "");
        $this->custom_params = get_param_value($params, "custom_params", array());
    }

    function _print_values() {
        parent::_print_values();

        $this->_print_custom_params();
    }

    function _print_custom_params() {
        $this->app->print_custom_params($this->custom_params);
    }

}

?>