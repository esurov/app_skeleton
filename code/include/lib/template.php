<?php

// HTML templates parser and manager

class Template {

    var $templates_dir;
    var $parsed_files;  // already parsed files cache
    var $fillings;  // hash with template data
    var $print_template_name; // for debug purposes
    var $TEMPLATE_NAME_PATTERN;


    function Template(
        $templates_dir = "templates",
        $print_template_name = false
    ) {
        // Store path to templates in the internal variable

        $this->templates_dir = $templates_dir;
        $this->parsed_files = array();
        $this->fillings = array();
        $this->print_template_name = $print_template_name;

        $this->TEMPLATE_NAME_PATTERN =
            "\n<!-- TEMPLATE BEGIN '%s' -->\n" .
            "%s" .
            "\n<!-- TEMPLATE END '%s' -->\n";
    }

    function get_value($var_name) {
        return $this->fillings[$var_name];
    }

    function set_value($var_name, $var_value) {
        if (!is_null($var_name)) {
            $this->fillings[$var_name] = $var_value;
        }
    }

    // Parse given text, return it with variables filled
    function parse($raw_text) {

        $parsed_text = preg_replace(
            "/{%(.*?)%}/e",
            " isset(\$this->fillings['$1']) ? \$this->fillings['$1'] : '' ",
            $raw_text
        );

        return $parsed_text;
    }

    // Parse given text, return it with variables filled
    // Also append result to given variable in fillings
    function parse_text($raw_text, $append_to_var_name = null) {
        
        $parsed_text = $this->parse($raw_text);
        if (!is_null(($append_to_var_name))) {
            $this->append($append_to_var_name, $parsed_text);
        }

        return $parsed_text;
    }

    // Return non-parsed template text.
    // Read from file if necessary.
    function get_raw_text($template_name) {

        if (isset($this->parsed_files[$template_name])) {
            $text = $this->parsed_files[$template_name];
        } else {
            $filepath = "{$this->templates_dir}/{$template_name}";
            $text = file_get_contents($filepath);
            $this->parsed_files[$template_name] = $text;
        }

        return $text;
    }

    // Append given text to the current filling value
    function append($name, $text) {
        
        if (isset($this->fillings[$name])) {
            $this->set_value($name, $this->fillings[$name] . $text);
        } else {
            $this->set_value($name, $text);
        }
    }
//
    function assign($more_fillings, $filling_value = null) {
        if (is_array($more_fillings)) {
            foreach ($more_fillings as $filling_name => $filling_value) {
                $this->set_value($filling_name, $filling_value);
            }
        } else {
            $filling_name = $more_fillings;
            $this->set_value($filling_name, $filling_value);
        }
    }

    function is_template_exist($template_name) {
        $filepath = "{$this->templates_dir}/{$template_name}";
        return file_exists($filepath);
    }

    // Parse given template using values from internal hash
    // Return filled template
    function parse_file($template_name, $append_to_var_name = null) {
        $raw_text = $this->get_raw_text($template_name);

        if ($this->print_template_name) {
            $raw_text = sprintf(
                $this->TEMPLATE_NAME_PATTERN,
                $template_name,
                $raw_text,
                $template_name
            );
        }

        $parsed_text = $this->parse_text($raw_text, $append_to_var_name);

        return $parsed_text;
    }

    // Parse given template using values from internal hash
    // Return filled template and empty the variable
    function parse_file_new($template_name, $var_name = null) {
        $this->set_value($var_name, $this->parse_file($template_name));
    }

    function parse_file_if_exists($template_name, $var_name = null) {
        if ($this->is_template_exist($template_name)) {
            $parsed_text = $this->parse_file($template_name, $var_name);    
        } else {
            $parsed_text = "";
            $this->set_value($var_name, $parsed_text);
        }
        return $parsed_text;
    }

    function parse_file_new_if_exists($template_name, $var_name = null) {
        $this->set_value($var_name, "");
        return $this->parse_file_if_exists($template_name, $var_name);
    }
}

?>