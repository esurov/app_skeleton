<?php

// Templates parser and manager
class Template extends AppObject {

    var $TEMPLATE_VAR_EXPR;
    var $TEMPLATE_VAR_REPLACE_FUNC;
    var $TEMPLATE_FILE_EXPR;
    var $TEMPLATE_FILE_REPLACE_FUNC;
    
    var $TEMPLATE_NAME_PATTERN;

    var $_templates_dir;
    var $_is_verbose = false; // used for debug purposes
    var $_is_verbose_saved;

    var $_parsed_files;
    var $_fillings;
    var $_extra_fillings;

    function _init($params) {
        parent::_init($params);

        $this->TEMPLATE_VAR_EXPR = '/{%(.*?)%}/';
        $this->TEMPLATE_VAR_REPLACE_FUNC = array(&$this, "template_var_replace_func");
        $this->TEMPLATE_FILE_EXPR = '/{{(.*?)}}/';
        $this->TEMPLATE_FILE_REPLACE_FUNC = array(&$this, "template_file_replace_func");
        
        $this->TEMPLATE_NAME_PATTERN =
            "\n<!-- TEMPLATE BEGIN '%s' -->\n" .
            "%s" .
            "\n<!-- TEMPLATE END '%s' -->\n";

        $this->_templates_dir = get_param_value($params, "templates_dir", "");
        
        if (get_param_value($params, "is_verbose", false)) {
            $this->verbose_turn_on();
        } else {
            $this->verbose_turn_off();
        }

        $this->_parsed_files = array();
        $this->_fillings = array();
    }
//
    function init_fillings() {
        $this->_fillings = require("{$this->_templates_dir}/global_{$this->app->lang}.php");
    }

    function set_filling_values($values) {
        foreach ($values as $var_name => $var_value) {
            $this->set_filling_value($var_name, $var_value);
        }
    }

    function get_filling_value($name) {
        return get_param_value($this->_fillings, $name, null);
    }

    function set_filling_value($name, $value) {
        $this->_fillings[$name] = $value;
    }

    function append_to_filling_value($name, $value) {
        if ($this->has_filling($name)) {
            $this->set_filling_value($name, $this->get_filling_value($name) . $value);
        } else {
            $this->set_filling_value($name, $value);
        }
    }

    function has_filling($name) {
        return array_key_exists($name, $this->_fillings);
    }
//
    function verbose_turn_on() {
        $this->_is_verbose_saved = $this->_is_verbose;
        $this->_is_verbose = true;
        return $this->_is_verbose_saved;
    }

    function verbose_turn_off() {
        $this->_is_verbose_saved = $this->_is_verbose;
        $this->_is_verbose = false;
        return $this->_is_verbose_saved;
    }

    function verbose_restore() {
        $is_verbose = $this->_is_verbose;
        $this->_is_verbose = $this->_is_verbose_saved;
        $this->_is_verbose_saved = $is_verbose;
        return $is_verbose;
    }
//
    // Parse given text, return it with variables filled
    // Also append result to given variable in filling
    function get_parsed_text(
        $raw_text,
        $append_to_name = null,
        $extra_fillings = array()
    ) {
        $parsed_text = preg_replace_callback(
            $this->TEMPLATE_FILE_EXPR,
            $this->TEMPLATE_FILE_REPLACE_FUNC,
            $raw_text
        );
        $this->_extra_fillings = $extra_fillings;
        $parsed_text = preg_replace_callback(
            $this->TEMPLATE_VAR_EXPR,
            $this->TEMPLATE_VAR_REPLACE_FUNC,
            $parsed_text
        );
        if (!is_null(($append_to_name))) {
            $this->append_to_filling_value($append_to_name, $parsed_text);
        }
        return $parsed_text;
    }

    function template_var_replace_func($matches) {
        $filling_name = $matches[1];
        return isset($this->_extra_fillings[$filling_name]) ?
            $this->_extra_fillings[$filling_name] : (
                isset($this->_fillings[$filling_name]) ?
                    $this->_fillings[$filling_name] :
                    ""
            );
    }

    function template_file_replace_func($matches) {
        $template_name_without_ext = $matches[1];
        $full_template_name = "{$template_name_without_ext}_{$this->app->lang}.html";
        if (!$this->is_file_exist($full_template_name)) {
            $full_template_name = "{$template_name_without_ext}.html";
        }
        return $this->parse_file($full_template_name);
    }

    // Return non-parsed template text
    // Read from file if necessary
    function get_template_text($template_name) {
        if (isset($this->_parsed_files[$template_name])) {
            $template_text = $this->_parsed_files[$template_name];
        } else {
            $template_text = file_get_contents("{$this->_templates_dir}/{$template_name}");
            $this->_parsed_files[$template_name] = $template_text;
        }
        return $template_text;
    }
//
    // Parse given template using values from internal hash
    // Return filled template
    function parse_file($template_name, $append_to_name = null) {
        $template_text = $this->get_template_text($template_name);
        if ($this->_is_verbose) {
            $template_text = sprintf(
                $this->TEMPLATE_NAME_PATTERN,
                $template_name,
                $template_text,
                $template_name
            );
        }
        return $this->get_parsed_text(
            $template_text,
            $append_to_name,
            $this->get_template_lang_resources($template_name)
        );
    }

    function get_template_lang_resources($template_name) {
        $lang_resources_filename = "{$template_name}.local_{$this->app->lang}.php";
        if ($this->is_file_exist($lang_resources_filename)) {
            return require("{$this->_templates_dir}/{$lang_resources_filename}");
        } else {
            return array();
        }
    }    

    // Parse given template using values from internal hash
    // Return filled template and empty the variable
    function parse_file_new($template_name, $name = null) {
        $parsed_text = $this->parse_file($template_name);
        $this->set_filling_value($name, $parsed_text);
        return $parsed_text;
    }

    function parse_file_if_exists($template_name, $name = null) {
        if ($this->is_file_exist($template_name)) {
            return $this->parse_file($template_name, $name);    
        } else {
            return "";
        }
    }

    function parse_file_new_if_exists($template_name, $name = null) {
        if (!is_null($name)) {
            $this->set_filling_value($name, "");
        }
        return $this->parse_file_if_exists($template_name, $name);
    }

    function is_file_exist($file_name) {
        return is_file("{$this->_templates_dir}/{$file_name}");
    }

}

?>