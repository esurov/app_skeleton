<?php

class ObjectView extends ObjectTemplateComponent {

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
        $this->app->print_custom_params($this->custom_params);

        return $this->print_object_view();
    }

    function print_object_view() {
        $this->print_object_values($this->obj);

        $this->app->print_file_new(
            "{$this->templates_dir}/view_info.{$this->templates_ext}",
            "{$this->template_var_prefix}_info"
        );
        return $this->app->print_file(
            "{$this->templates_dir}/view.{$this->templates_ext}",
            $this->template_var
        );
    }

    function print_object_values(&$obj) {
        $obj->print_values(array(
            "templates_dir" => $this->templates_dir,
            "template_var_prefix" => $this->template_var_prefix,
            "context" => $this->context,
            "custom_params" => $this->custom_params,
        ));
    }

}

?>