<?php

class ObjectEdit extends ObjectTemplateComponent {

    function _init($params) {
        parent::_init($params);
    
        if (is_null($this->obj)) {
            $this->process_fatal_error(
                "Required param 'obj' not found!"
            );
        }
    }
//
    function print_values() {
        $this->print_custom_params();

        return $this->print_object_edit();
    }

    function print_custom_params() {
        $this->app->print_custom_params($this->custom_params);
    }

    function print_object_edit() {
        $this->print_page_titles();
        $this->print_object_values($this->obj);

        $this->app->print_file_new(
            "{$this->templates_dir}/edit_form.{$this->templates_ext}",
            "{$this->template_var_prefix}_form"
        );
        return $this->app->print_file(
            "{$this->templates_dir}/edit.{$this->templates_ext}",
            $this->template_var
        );
    }

    function print_page_titles() {
        $resource = $this->app->get_default_page_title_lang_resource();
        if (!$this->obj->is_definite()) {
            $resource .= "_new";
        }
        $this->app->print_head_and_page_titles($resource);
    }

    function print_object_values(&$obj) {
        $obj->print_form_values(array(
            "templates_dir" => $this->templates_dir,
            "template_var_prefix" => $this->template_var_prefix,
            "context" => $this->context,
            "custom_params" => $this->custom_params,
        ));
    }

}

?>