<?php

// Templates parser and manager
class Template {

    var $templates_dir;
    var $parsed_files;
    var $fillings;
    var $print_template_name; // used for debug purposes
    var $TEMPLATE_NAME_PATTERN;

    function Template($templates_dir = "templates", $print_template_name = false) {
        $this->templates_dir = $templates_dir;
        $this->parsed_files = array();
        $this->fillings = array();
        $this->print_template_name = $print_template_name;

        $this->TEMPLATE_NAME_PATTERN =
            "\n<!-- TEMPLATE BEGIN '%s' -->\n" .
            "%s" .
            "\n<!-- TEMPLATE END '%s' -->\n";
    }
//
    function get_filling_value($name) {
        return get_param_value($this->fillings, $name, null);
    }

    function set_filling_value($name, $value) {
        $this->fillings[$name] = $value;
    }

    function set_filling_values($values) {
        foreach ($values as $var_name => $var_value) {
            $this->set_filling_value($var_name, $var_value);
        }
    }

    function append_to_filling_value($name, $value) {
        if ($this->has_filling($name)) {
            $this->set_filling_value($name, $this->get_filling_value($name) . $value);
        } else {
            $this->set_filling_value($name, $value);
        }
    }

    function has_filling($name) {
        return array_key_exists($name, $this->fillings);
    }
//
    // Parse given text, return it with variables filled
    // Also append result to given variable in filling
    function get_parsed_text($raw_text, $append_to_name = null) {
        $parsed_text = preg_replace(
            '/{%(.*?)%}/e',
            'isset($this->fillings["$1"]) ? $this->fillings["$1"] : ""',
            $raw_text
        );
        $parsed_text = preg_replace(
            '/{{(.*?)}}/e',
            '$this->parse_file("$1")',
            $parsed_text
        );
        if (!is_null(($append_to_name))) {
            $this->append_to_filling_value($append_to_name, $parsed_text);
        }
        return $parsed_text;
    }

    // Return non-parsed template text
    // Read from file if necessary
    function get_template_text($template_name) {
        if (isset($this->parsed_files[$template_name])) {
            $template_text = $this->parsed_files[$template_name];
        } else {
            $template_text = file_get_contents("{$this->templates_dir}/{$template_name}");
            $this->parsed_files[$template_name] = $template_text;
        }
        return $template_text;
    }
//
    // Parse given template using values from internal hash
    // Return filled template
    function parse_file($template_name, $append_to_name = null) {
        $template_text = $this->get_template_text($template_name);
        if ($this->print_template_name) {
            $template_text = sprintf(
                $this->TEMPLATE_NAME_PATTERN,
                $template_name,
                $template_text,
                $template_name
            );
        }
        return $this->get_parsed_text($template_text, $append_to_name);
    }

    // Parse given template using values from internal hash
    // Return filled template and empty the variable
    function parse_file_new($template_name, $name = null) {
        $parsed_text = $this->parse_file($template_name);
        $this->set_filling_value($name, $parsed_text);
        return $parsed_text;
    }

    function parse_file_if_exists($template_name, $name = null) {
        if ($this->is_template_exist($template_name)) {
            return $this->parse_file($template_name, $name);    
        } else {
            $this->set_filling_value($name, "");
            return "";
        }
    }

    function parse_file_new_if_exists($template_name, $name = null) {
        if (!is_null($name)) {
            $this->set_filling_value($name, "");
        }
        return $this->parse_file_if_exists($template_name, $name);
    }

    function is_template_exist($template_name) {
        return is_file("{$this->templates_dir}/{$template_name}");
    }
}

?>