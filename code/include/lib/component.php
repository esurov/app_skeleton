<?php

class Component {

    var $app;

    function init($params) {
    }

}

class TemplateComponent extends Component {

    var $templates_dir;
    var $templates_ext;

    function init($params) {
        parent::init($params);

        $this->templates_dir = get_param_value($params, "templates_dir", null);
        if (is_null($this->templates_dir)) {
            $this->app->process_fatal_error(
                "TemplateComponent",
                "No 'templates_dir' in TemplateComponent::init()"
            );
        }
        $this->templates_ext = get_param_value($params, "templates_ext", "html");
    }

    function print_values() {
    }

}

class ObjectTemplateComponent extends TemplateComponent {

    var $obj;

    var $template_var_prefix;
    var $template_var;

    var $context;
    var $custom_params;

    function init($params) {
        parent::init($params);

        $this->obj = get_param_value($params, "obj", null);

        $this->template_var_prefix = get_param_value(
            $params,
            "template_var_prefix",
            is_null($this->obj) ? "" : $this->obj->_table_name
        );
        $this->template_var = get_param_value($params, "template_var", null);
        
        $this->context = get_param_value($params, "context", "");
        $this->custom_params = get_param_value($params, "custom_params", array());
    }

}

?>