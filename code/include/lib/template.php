<?php

// template.php,v 1.3 2001/11/09 09:53:02 max Exp

// class Template.


class Template {

    // HTML templates.

    var $templates_dir;

    var $parsed_files;  // already parsed files cache

    var $fillings;  // hash with template data

//    var $fillings_history; // история изменений fillings


function Template($templates_dir = 'templates')
{
    // Constructor.
    // Store path to templates in the internal variable.

    $this->templates_dir = $templates_dir;

    $this->parsed_files = array();

    $this->fillings = array();
    
//    $this->fillings_history = array();
}


function get_value( $var_name )
{
    // Return current value of given template variable.

    return $this->fillings[$var_name];
}


function set_value($var_name, $value)
{
    // записываем в историю
//    $this->fillings_history[$var_name][] = $value;
    
    $this->fillings[$var_name] = $value;
}


//function get_value_history($var_name)
//{
    // возвращает историю изменений в виде массива
//    if (isset($this->fillings_history[$var_name])) {
//        $result = $this->fillings_history[$var_name];
//    } else {
//        $result = null;
//    }
//
//    return $result;
//}


function assign($more_fillings)
{
    // Add given hash to internal page fillings.

    foreach($more_fillings as $name => $value) {
        $this->set_value($name, $value);
    }
}

//function assign_new($more_fillings, $filling_value = "") {
//    if (is_array($more_fillings)) {
//        foreach (array_keys($more_fillings) as $name => $value) {
//            $this->set_value($name, $value);
//        }
//    } else {
//        $name = $more_fillings;
//        $this->set_value($name, $filling_value);
//    }
//}
//

function parse($raw_text)
{
    // Parse given text, return it with variables filled.

    $parsed_text = preg_replace(
        "/{%(.*?)%}/e",
        " isset(\$this->fillings['$1']) ? \$this->fillings['$1'] : '' ",
        $raw_text
   );

    return $parsed_text;
}


function parse_text($raw_text, $append_to = NULL)
{
    // Parse given text, return it with variables filled.
    // Also append result to given variable in fillings.

    $parsed_text = $this->parse($raw_text);

    if(isset( $append_to) ) {
        $this->append($append_to, $parsed_text);
    }

    return $parsed_text;
}


function parse_file($template_name, $append_to = NULL)
{
    // Parse given template using values from internal hash.
    // Return filled template.

    $raw_text = $this->get_raw_text($template_name);

    $parsed_text = $this->parse_text($raw_text, $append_to);

    return $parsed_text;
}

function parse_file_new($template_name, $variable = NULL) {
    if (!is_null($variable)) {
        $this->set_value($variable, "");
    }
    $this->parse_file($template_name, $variable);
}


function get_raw_text($template_name)
{
    // Return non-parsed template text.
    // Read from file if necessary.

    if(isset( $this->parsed_files[$template_name]) ) {
        $text = $this->parsed_files[$template_name];

    } else {
        $filename = "{$this->templates_dir}/{$template_name}";
        $text = join('', file( $filename) );
        $this->parsed_files[$template_name] = $text;
    }

    return $text;
}


function append($name, $text)
{
    // Append given text to the current filling value.

    if( isset( $this->fillings[$name] ) ) {
        $this->set_value($name, $this->fillings[$name] . $text );
    } else {
        $this->set_value($name, $text);
    }
}


}  // class Template


?>
