<?php

class ObjectEdit extends ObjectTemplateComponent {

    var $input_name_prefix;
    var $input_name_suffix;

    var $_page_title_resource;

    function _init($params) {
        parent::_init($params);
    
        if (is_null($this->obj)) {
            $this->process_fatal_error_required_param_not_found("obj");
        }

        $this->input_name_prefix = get_param_value(
            $params,
            "input_name_prefix",
            $this->obj->_table_name
        );
        $this->input_name_suffix = get_param_value(
            $params,
            "input_name_suffix",
            ""
        );
        
        $this->_page_title_resource = get_param_value(
            $params,
            "page_title_resource",
            $this->app->get_action_lang_resource()
        );
        if (!is_null($this->_page_title_resource) && !$this->obj->is_definite()) {
            $this->_page_title_resource .= "_new";
        }
    }
//
    function _print_values() {
        parent::_print_values();

        return $this->_print_object_edit();
    }

    function _print_object_edit() {
        $this->_print_page_titles();

        $this->_print_object_values($this->obj);

        $this->app->print_file_new(
            "{$this->templates_dir}/edit_form.{$this->templates_ext}",
            "{$this->template_var_prefix}_form"
        );
        return $this->app->print_file(
            "{$this->templates_dir}/edit.{$this->templates_ext}",
            $this->template_var
        );
    }

    function _print_page_titles() {
        if (!is_null($this->_page_title_resource)) {
            $this->app->print_head_and_page_titles($this->_page_title_resource);
        }
    }

    function _print_object_values(&$obj) {
        $obj->print_form_values(array(
            "templates_dir" => $this->templates_dir,
            "template_var_prefix" => $this->template_var_prefix,
            "context" => $this->context,
            "custom_params" => $this->custom_params,
            "input_name_prefix" => $this->input_name_prefix,
            "input_name_suffix" => $this->input_name_suffix,
        ));
    }

}

?>