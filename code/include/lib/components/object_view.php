<?php

class ObjectView extends ObjectTemplateComponent {

    var $_page_title_resource;

    function _init($params) {
        parent::_init($params);
    
        if (is_null($this->obj)) {
            $this->process_fatal_error_required_param_not_found("obj");
        }

        $this->_page_title_resource = get_param_value(
            $params,
            "page_title_resource",
            null
        );
    }
//
    function _print_values() {
        parent::_print_values();

        return $this->_print_object_view();
    }

    function _print_object_view() {
        $this->_print_page_titles();

        $this->_print_object_values($this->obj);

        $this->app->print_file_new(
            "{$this->templates_dir}/view_info.{$this->templates_ext}",
            "{$this->template_var_prefix}_info"
        );
        return $this->app->print_file(
            "{$this->templates_dir}/view.{$this->templates_ext}",
            $this->template_var
        );
    }

    function _print_page_titles() {
        if (!is_null($this->_page_title_resource)) {
            $this->app->print_head_and_page_titles($this->_page_title_resource);
        }
    }

    function _print_object_values(&$obj) {
        $obj->print_values(array(
            "templates_dir" => $this->templates_dir,
            "template_var_prefix" => $this->template_var_prefix,
            "context" => $this->context,
            "custom_params" => $this->custom_params,
        ));
    }

}

?>