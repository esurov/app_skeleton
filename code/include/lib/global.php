<?php

function v($obj) {
    var_dump($obj);
}

function vx($obj) {
    v($obj);
    exit;
}

function tx() {
    $backtrace_info_array = debug_backtrace();
    echo get_backtrace_str($backtrace_info_array);
    exit;
}

function get_backtrace_str($backtrace_info_array) {
    $trace_body = "";
    array_shift($backtrace_info_array);
    foreach ($backtrace_info_array as $backtrace_item) {
        $full_function_name_safe_str = htmlspecialchars(get_full_function_name_str($backtrace_item));
        $function_args_safe_str = htmlspecialchars(get_backtrace_function_args_str($backtrace_item));
        
        $file_safe_str = htmlspecialchars($backtrace_item["file"]);
        $line_num_safe_str = htmlspecialchars($backtrace_item["line"]);

        $trace_body .= <<<TRACE_ITEM
<b>{$full_function_name_safe_str}($function_args_safe_str)</b>
({$file_safe_str}:&nbsp;<b>{$line_num_safe_str}</b>)<br>
TRACE_ITEM;
    }
    return <<<TRACE_HEADER
<b>Function call backtrace:</b>
<div style="margin-top: 10px;">{$trace_body}</div>
TRACE_HEADER;
}

function get_full_function_name_str($backtrace_item) {
    $class_name = (isset($backtrace_item["class"])) ? $backtrace_item["class"] : "";
    $function_name = (isset($backtrace_item["function"])) ? $backtrace_item["function"] : "";
    $call_type = (isset($backtrace_item["type"])) ? $backtrace_item["type"] : "";
    return ($call_type == "") ? $function_name : "{$class_name}{$call_type}{$function_name}";
}

function get_backtrace_function_args_str($backtrace_item) {
    $args = (isset($backtrace_item["args"])) ? $backtrace_item["args"] : array();

    $args_str = "";
    foreach ($args as $arg) {
        if ($args_str != "") {
            $args_str .= ", ";
        }
        switch (gettype($arg)) {
        case "integer":
        case "double":
            $args_str .= $arg;
            break;
        case "boolean":
            $args_str .= $arg ? "true" : "false";
            break;
        case "string":
            if (strlen($arg) > 64) {
                $arg = substr($arg, 0, 64) . "...";
            }
            $args_str .= "\"$arg\"";
            break;
        case "array":
            $args_str .= "array({count=" . count($arg) ."})";
            break;
        case "object":
            $args_str .= "object(" . get_class($arg) . ")";
            break;
        case "resource":
            $args_str .= "resource(" . strstr($arg, "#") . ")";
            break;
        case "NULL":
            $args_str .= "null";
            break;
        default:
            $args_str .= "UNKNOWN!";
        }
    }
    return $args_str;
}

function param($name) {
    if (isset($_GET[$name])) {
        $param_value = $_GET[$name];
    } else if (isset($_POST[$name])) {
        $param_value = $_POST[$name];
    } else {
        return null;
    }
    if (get_magic_quotes_gpc()) {
        if (is_array($param_value)) {
            $param_values = array();
            foreach ($param_value as $value) {
                $param_values[] = stripslashes($value);
            }
            return $param_values;
        } else {
            return stripslashes($param_value);
        }
    }
    return $param_value;
}

function param_cookie($name) {
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : "";
}

function params() {
    return array_merge($_POST, $_GET);
}

function create_suburl($params = array(), $params_delimiter = "&") {
    $pairs = array();
    foreach ($params as $name => $value) {
        if (is_array($value)) {
            foreach ($value as $array_value) {
                $pairs[] = urlencode($name) . "[]=" . urlencode($array_value);
            }
        } else {
            $pairs[] = urlencode($name) . "=" . urlencode($value);
        }
    }
    return join($params_delimiter, $pairs);
}

function create_self_url($params = array()) {
    $params_suburl = create_suburl($params);
    $self_suburl = $_SERVER["SCRIPT_NAME"];
    if (count($params) == 0) {
        return $self_suburl;            
    } else {
        return "{$self_suburl}?{$params_suburl}";
    }
}

function create_self_full_url($params = array(), $protocol = "http") {
    $host = $_SERVER["HTTP_HOST"];
    $self_url = create_self_url($params);
    return "{$protocol}://{$host}{$self_url}";
}

function unset_array_value_if_exists($value, &$values) {
    $i = array_search($value, $values);
    if ($i !== false) {
        unset($values[$i]);
    }
}

function get_param_value($params, $param_name, $default_value) {
    if (array_key_exists($param_name, $params)) {
        return $params[$param_name];
    } else {
        return $default_value;
    }
}

//
function is_value_empty($value) {
    return trim($value) == "";
}

function is_value_not_empty($value) {
    return !is_value_empty($value);
}

function is_value_email($value) {
    return preg_match('/.+@.+\..+/', $value);
}

function is_php_number($value) {
    return (preg_match('/^\s*[-+]?(?:[0-9]*\.)?[0-9]*\s*$/', $value));
}

//
function format_double_value($double_value, $decimals, $dec_point, $thousands_sep) {
    return number_format($double_value, $decimals, $dec_point, $thousands_sep);
}

function format_integer_value($integer_value, $thousands_sep) {
    return number_format($integer_value, 0, "", $thousands_sep);
}

function convert_lf2br($str, $is_xhtml = false) {
    $br_tag = ($is_xhtml) ? "<br />" : "<br>";
    return str_replace("\n", $br_tag, convert_crlf2lf($str));
}

function convert_crlf2lf($str) {
    return preg_replace('/(\r\n)|\r|\n/m', "\n", $str);
}

function transliterate_western_europian_to_ascii($str) {
    $western_europian = array();
    for ($i = 128; $i <= 255; $i++) {
        $western_europian[] = chr($i);
    }
    $transliteration = array(
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?", "?",
        "A`", "A'", "?", "?", "?", "?", "?", "C", "E`", "E'", "?", "?", "I`", "I'", "?", "?",
        "?", "?", "O`", "O'", "?", "?", "?", "?", "?", "U`", "U'", "?", "?", "?", "?", "SS",
        "a`", "a'", "?", "?", "?", "?", "?", "c", "e`", "e'", "?", "?", "i`", "i'", "?", "?",
        "?", "?", "o`", "o'", "?", "?", "?", "?", "?", "u`", "u'", "?", "?", "?", "?", "?",
    );
    return str_replace($western_europian, $transliteration, $str);
}

function get_html_safe_string($unsafe_str) {
    return htmlspecialchars($unsafe_str);
}

function get_html_safe_array($unsafe_array) {
    $safe_array = array();
    foreach ($unsafe_array as $key => $unsafe_str) {
        $safe_array[$key] = get_html_safe_string($unsafe_str);
    }
    return $safe_array;
}

function get_js_safe_string($unsafe_str) {
    $str = str_replace("\\", "\\\\", $unsafe_str);
    $str = str_replace("'", "\\'", $str);
    $str = str_replace("\"", "\\\"", $str);
    $str = convert_crlf2lf($str);
    return str_replace("\n", '\n', $str);
}

function quote_string($str) {
    return "'{$str}'";
}

// Quote and escape string for MySql.
function qw($str) {
    return quote_string(mysql_escape_string($str));
}

// Quote and escape string for MySql LIKE expression.
function lqw($str, $prefix_str = "", $suffix_str = "") {
    $str = mysql_escape_string($str);
    $str = str_replace('%', '\%', $str);
    $str = str_replace('_', '\_', $str);
    return quote_string("{$prefix_str}{$str}{$suffix_str}");
}

function get_shortened_string($str, $max_length, $end_str = "") {
    if (strlen($str) > $max_length) {
        $str = substr($str, 0, $max_length);
        return "{$str}{$end_str}";
    } else {
        return $str;
    }
}

function get_word_shortened_string($str, $max_length, $end_str = "") {
    if (strlen($str) > $max_length) {
        $str = substr($str, 0, $max_length);
        $n = strrpos($str, " ");
        if ($n !== false) {
            $str = substr($str, 0, $n);
        }
        return "{$str}{$end_str}";
    } else {
        return $str;
    }
}

// Print common HTML controls
function print_raw_html_input($type, $name, $value, $attrs = array()) {
    $attrs_str = get_html_attrs_str($attrs);
    return "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value}\"{$attrs_str}>";
}

function print_html_input($type, $name, $value, $attrs = array()) {
    $value_safe = get_html_safe_string($value);
    return print_raw_html_input($type, $name, $value_safe, $attrs);
}

function print_raw_html_hidden($name, $value) {
    return print_raw_html_input("hidden", $name, $value);
}

function print_html_hidden($name, $value) {
    return print_html_input("hidden", $name, $value);
}

function print_html_checkbox(
    $name,
    $value,
    $checked = null,
    $attrs = array()
) {
    if (is_null($checked)) {
        if ((int) $value != 0) {
            $attrs["checked"] = null;
        }
    } else if ($checked) {
        $attrs["checked"] = null;
    }
    return print_html_input("checkbox", $name, $value, $attrs);
}

function print_html_select(
    $name,
    $value_caption_pairs,
    $current_value,
    $attrs = array()
) {
    $attrs_str = get_html_attrs_str($attrs);
    $select_options = print_html_select_options($value_caption_pairs, $current_value);
    return
        "<select name=\"{$name}\"{$attrs_str}>\n" .
        "{$select_options}" .
        "</select>";
}

function print_html_select_options($value_caption_pairs, $current_value) {
    if (is_array($current_value)) {
//        $selected_value = get_multiple_selected_values(
//            $value_caption_pairs, $current_values
//        );
    } else {
        $selected_value = get_actual_current_value($value_caption_pairs, $current_value);
    }

    $output = "";
    foreach ($value_caption_pairs as $value_caption_pair) {
        $value = get_value_from_value_caption_pair($value_caption_pair);
        $caption = get_caption_from_value_caption_pair($value_caption_pair);
        
        $option_attrs = array();
        if (!is_null($selected_value)) {
            if (is_array($selected_value)) {
                if (in_array($value, $selected_value)) {
                    $option_attrs["selected"] = null;
                }
            } else {
                if ((string) $value == (string) $selected_value) {
                    $option_attrs["selected"] = null;
                }
            }
        }
        $output .= print_html_select_option($value, $caption, $option_attrs);
    }
    return $output;
}

function print_html_select_option($value, $caption, $attrs = array()) {
    $attrs_str = get_html_attrs_str($attrs);
    $value_safe = get_html_safe_string($value);
    $caption_safe = get_html_safe_string($caption);
    return "<option value=\"{$value_safe}\"{$attrs_str}>{$caption_safe}</option>\n";
}

function print_html_radio_group(
    $name,
    $value_caption_pairs,
    $current_value,
    $attrs = array(),
    $delimiter = ""
) {
    $checked_value = get_actual_current_value($value_caption_pairs, $current_value);

    $output = "";
    $delimiter_str = "";
    foreach ($value_caption_pairs as $value_caption_pair) {
        $value = get_value_from_value_caption_pair($value_caption_pair);
        $caption = get_caption_from_value_caption_pair($value_caption_pair);

        $radio_attrs = $attrs;
        if (!is_null($checked_value) && ((string) $value == (string) $checked_value)) {
            $radio_attrs["checked"] = null;
        }
        $output .= $delimiter_str;
        $output .= print_html_radio($name, $value, $radio_attrs);
        $output .= get_html_safe_string($caption);
        $delimiter_str = $delimiter;
    }
    return $output;
}

function print_html_radio($name, $value, $attrs = array()) {
    return print_html_input("radio", $name, $value, $attrs);
}

function print_html_textarea($name, $value, $attrs = array()) {
    $attrs_str = get_html_attrs_str($attrs);
    return
        "<textarea name=\"{$name}\"{$attrs_str}>" .
        get_html_safe_string($value) .
        "</textarea>";
}

//
function get_actual_current_value($value_caption_pairs, $current_value) {
    $values = get_values_from_value_caption_pairs($value_caption_pairs);
    if (is_null($current_value) || count($values) == 0) {
        return null;
    } else if (in_array($current_value, $values)) {
        return $current_value;
    } else {
        return $values[0];
    }
}

//function get_multiple_selected_values($value_caption_pairs, $current_values) {
//    $values = get_values_from_value_caption_pairs($value_caption_pairs);
//    $selected_values = array();
//    foreach ($current_values as $current_value) {
//        if (in_array($current_value, $values)) {
//            $selected_values[] = $current_value;
//        }
//    }
//    return $selected_values;
//}

function get_html_attrs_str($attrs) {
    if (count($attrs) == 0) {
        return "";
    }
    $attrs_lines = array();
    foreach ($attrs as $attr_name => $attr_value) {
        $attr_name_safe = get_html_safe_string($attr_name);
        if (is_null($attr_value)) {
            $attrs_lines[] = get_html_safe_string($attr_name);
        } else { 
            $attr_value_safe = get_html_safe_string($attr_value);
            $attrs_lines[] = "{$attr_name_safe}=\"{$attr_value_safe}\"";
        }
    }
    return " " . join(" ", $attrs_lines);
}

function get_value_from_value_caption_pair($value_caption_pair) {
    return $value_caption_pair[0];
}

function get_caption_from_value_caption_pair($value_caption_pair) {
    return $value_caption_pair[1];
}

function get_values_from_value_caption_pairs($value_caption_pairs) {
    $values = array();
    foreach ($value_caption_pairs as $value_caption_pair) {
        $values[] = get_value_from_value_caption_pair($value_caption_pair);
    }
    return $values;
}

function get_captions_from_value_caption_pairs($value_caption_pairs) {
    $captions = array();
    foreach ($value_caption_pairs as $value_caption_pair) {
        $values[] = get_caption_from_value_caption_pair($value_caption_pair);
    }
    return $captions;
}

function get_caption_by_value_from_value_caption_pairs($value_caption_pairs, $value) {
    if (is_null($value)) {
        return null;
    }
    foreach ($value_caption_pairs as $value_caption_pair) {
        if ((string) get_value_from_value_caption_pair($value_caption_pair) == (string) $value) {
            return get_caption_from_value_caption_pair($value_caption_pair);
        }
    }
    return null;
}

//
function create_select_dependency_js(
    $form_name,
    $main_select_name,
    $dependent_select_name,
    $dependency_array
) {
    $dependency_str = create_select_dependency_str($dependency_array);
    return <<<JS
<script language="JavaScript" type="text/javascript">
dependencies[dependencies.length] = new Dependency(
    '{$form_name}',
    '{$main_select_name}',
    '{$dependent_select_name}',
    new Array({$dependency_str})
);
</script>

JS;
}

function create_select_dependency_str($dependency_array) {
    $lines = array();
    foreach ($dependency_array as $dependent_select_values) {
        $dependent_select_values_str = join("', '", $dependent_select_values);
        $lines[] = <<<JS
new Array('{$dependent_select_values_str}')

JS;
    }
    return join(",\n", $lines) . "\n";
}

function create_client_validation_js($client_validate_condition_strs) {
    $client_validate_conditions_str = join(",\n", $client_validate_condition_strs);
    return <<<JS
<script language="JavaScript" type="text/javascript">
conditions = new Array(
{$client_validate_conditions_str}
);
document.forms['form'].onsubmit = onsubmitValidateFormHandler;
</script>

JS;
}

function create_client_validate_condition_str(
    $input_name,
    $condition_type,
    $message_text,
    $param,
    $dependent_validate_condition_str
) {
    $message_text_param = (is_null($message_text)) ?
        "null" :
        quote_string(get_js_safe_string($message_text));

    $params_safe = array();
    if (!is_null($param)) {
        if (!is_array($param)) {
            $param = array($param);
        }
        foreach ($param as $param_value) {
            $params_safe[] = get_js_safe_string($param_value);
        }
    }
    $params_str = (count($params_safe) == 0) ?
        "" :
        quote_string(join("', '", $params_safe));

    if (is_null($dependent_validate_condition_str)) {
        $dependent_validate_condition_str = "null";
    }

    return <<<JS
new ValidateCondition(
    '{$input_name}',
    '{$condition_type}',
    {$message_text_param},
    new Array({$params_str}),
{$dependent_validate_condition_str}
)
JS;
}

//
function parse_date_by_format($format, $value) {
    $regexp = create_date_regexp_by_format($format);

    $date_parts = array(
        "year" => 0,
        "month" => 0,
        "day" => 0,
        "hour" => 0,
        "minute" => 0,
        "second" => 0,
    );
    if (preg_match($regexp, $value, $date_parts_unordered)) {
        $p = 1;
        $format_len = strlen($format);
        for ($i = 0; $i < $format_len; $i++) {
            $format_char = $format{$i};
            switch ($format_char) {
            case "y":
                $date_parts["year"] = (int) $date_parts_unordered[$p++];
                break;
            case "m":
                $date_parts["month"] = (int) $date_parts_unordered[$p++];
                break;
            case "d":
                $date_parts["day"] = (int) $date_parts_unordered[$p++];
                break;
            case "h":
                $date_parts["hour"] = (int) $date_parts_unordered[$p++];
                break;
            case "H":
                $date_parts["hour"] = (int) $date_parts_unordered[$p++];
                break;
            case "i":
                $date_parts["minute"] = (int) $date_parts_unordered[$p++];
                break;
            case "s":
                $date_parts["second"] = (int) $date_parts_unordered[$p++];
                break;
            case "t":
                $hour = $date_parts["hour"];
                $am_pm_str = strtoupper($date_parts_unordered[$p++]);
                if ($am_pm_str == "PM") {
                    if ($hour != 12) {
                        $hour += 12;
                    }    
                } else {
                    if ($hour == 12) {
                        $hour = 0;
                    }    
                }
                break;
            }
        }

        if ($date_parts["month"] > 12) {
            $date_parts["month"] = 12;
        }
        if ($date_parts["day"] > 31) {
            $date_parts["day"] = 31;
        }
        if ($date_parts["hour"] > 23) {
            $date_parts["hour"] = 23;
        }
        if ($date_parts["minute"] > 59) {
            $date_parts["minute"] = 59;
        }
        if ($date_parts["second"] > 59) {
            $date_parts["second"] = 59;
        }
    }

    return $date_parts;
}

function create_date_regexp_by_format($format) {
    $res = "/";
    $format_len = strlen($format);
    for ($i = 0; $i < $format_len; $i++) {
        $format_char = $format{$i};
        switch ($format_char) {
        case "y":
            $res .= '(\d{1,4})';
            break;
        case "m":
            $res .= '(\d{1,2})';
            break;
        case "d":
            $res .= '(\d{1,2})';
            break;
        case "h":
            $res .= '(\d{1,2})';
            break;
        case "H":
            $res .= '(\d{1,2})';
            break;
        case "i":
            $res .= '(\d{1,2})';
            break;
        case "s":
            $res .= '(\d{1,2})';
            break;
        case "t":
            $res .= '(AM|PM)?';
            break;
        case ".":
            $res .= '\.';
            break;
        case "/":
            $res .= '\/';
            break;
        case "\\":
            $res .= "\\\\";
            break;
        default:
            $res .= $format_char;
        }
    }
    $res .= "/i";
    return $res;
}

function create_date_by_format($format, $date_parts, $date_if_unknown) {
    $am_pm_str = "";
    $res = "";
    $format_len = strlen($format);
    for ($i = 0; $i < $format_len; $i++) {
        $format_char = $format{$i};
        switch ($format_char) {
        case "y":
            $res .= sprintf("%04d", $date_parts["year"]);
            break;
        case "m":
            $res .= sprintf("%02d", $date_parts["month"]);
            break;
        case "d":
            $res .= sprintf("%02d", $date_parts["day"]);
            break;
        case "h":
            $res .= sprintf("%02d", $date_parts["hour"]);
            break;
        case "H":
            $hour = $date_parts["hour"];
            if ($hour <= 12) {
                $am_pm_str = "AM";
            } else {
                $hour -= 12;
                $am_pm_str = "PM";
            }
            if ($hour == 0) {
                $hour = 12;
            }
            $res .= sprintf("%02d", $hour);
            break;
        case "i":
            $res .= sprintf("%02d", $date_parts["minute"]);
            break;
        case "s":
            $res .= sprintf("%02d", $date_parts["second"]);
            break;
        case "t":
            $res .= sprintf("%s", $am_pm_str);
            break;
        default:
            $res .= $format_char;
        }
    }
    if (
        $date_parts["year"] == 0 &&
        $date_parts["month"] == 0 &&
        $date_parts["day"] == 0 &&
        (isset($date_parts["hour"]) && $date_parts["hour"] == 0) &&
        (isset($date_parts["minute"]) && $date_parts["minute"] == 0) &&
        (isset($date_parts["second"]) && $date_parts["second"] == 0)
    ) {
        return $date_if_unknown;
    } else {
        return $res;
    }
}

function get_date_parts_from_timestamp($ts) {
    $date = date("Y-m-d-H-i-s", $ts);
    $date_parts_unordered = explode("-", $date);
    $date_parts = array(
        "year" => $date_parts_unordered[0],
        "month" => $date_parts_unordered[1],
        "day" => $date_parts_unordered[2],
        "hour" => $date_parts_unordered[3],
        "minute" => $date_parts_unordered[4],
        "second" => $date_parts_unordered[5],
    );
    return $date_parts;
}

function get_timestamp_from_date_parts($date_parts) {
    return mktime(
        $date_parts["hour"],
        $date_parts["minute"],
        $date_parts["second"],
        $date_parts["month"],
        $date_parts["day"],
        $date_parts["year"]
    );
}

function get_microtime_as_double($microtime_str = null) {
    if (is_null($microtime_str)) {
        $microtime_str = microtime();
    }
    list($usec, $sec) = explode(" ", $microtime_str);
    return ((double) $usec + (double) $sec);
}

function get_gmt_str_from_timestamp($timestamp) {
    $gmt_str = gmdate("D, d M Y H:i:s", $timestamp);
    return "{$gmt_str} GMT";
}

function get_gmt_str_from_if_modified_since($if_modified_since_str) {
    $strs = explode(";", $if_modified_since_str);
    return $strs[0];
}

function read_and_parse_csv_file($filepath, $separator = ";") {
    $f = fopen($filepath, "r");
    if (!$f) {
        return false;
    }
    $lines = array();
    while (!feof($f)) {
        $line = trim(fgets($f, 1024));
        $line_values = preg_split('/\s*' . $separator . '\s*/', $line);
        $lines[] = $line_values;
    }
    fclose($f);
    return $lines;
}

if (!function_exists("file_put_contents")) {
function file_put_contents($filename, $content, $should_append = false) {
    $fp = fopen($filename, ($should_append) ? "a+" : "w+");
    if (!$fp) {
        return false;
    }
    $result = fwrite($fp, $content);
    fclose($fp);
    return ($result !== false);
}
}

function get_uploaded_file_info($input_name) {
    return $_FILES[$input_name];
}

function was_file_uploaded($input_name) {
    return isset($_FILES[$input_name]) &&
        ($_FILES[$input_name]["error"] == UPLOAD_ERR_OK) &&
        isset($_FILES[$input_name]["size"]) &&
        ($_FILES[$input_name]["size"] > 0);
}

function get_formatted_filesize_str($filesize) {
    $kb = 1024;
    $mb = 1024 * $kb;
    $gb = 1024 * $mb;

    if ($filesize < $kb) {
        $str = "{$filesize} " . ($filesize == 1 ? "byte" : "bytes");
    } else if ($filesize < $mb) {
        $str = number_format($filesize / $kb, 2) . " Kb";
    } else if ($filesize < $gb) {
        $str = number_format($filesize / $mb, 2) . " Mb";
    } else {
        $str = number_format($filesize / $gb, 2) . " Gb";
    }
    return $str;
}

function get_file_extension($filename) {
    $pos = strrpos($filename, ".");
    return ($pos === false) ? "" : (string) substr($filename, $pos + 1);
}

function get_image_file_extension_by_type($image_type) {
    switch ($image_type) {
    case IMAGETYPE_GIF:
        $file_ext = "gif";
        break;
    case IMAGETYPE_JPEG:
        $file_ext = "jpg";
        break;
    case IMAGETYPE_PNG:
        $file_ext = "png";
        break;
    case IMAGETYPE_PSD:
        $file_ext = "psd";
        break;
    case IMAGETYPE_BMP:
        $file_ext = "bmp";
        break;
    case IMAGETYPE_TIFF:
        $file_ext = "tiff";
        break;
    case IMAGETYPE_IFF:
        $file_ext = "gif";
        break;
    case IMAGETYPE_WBMP:
        $file_ext = "wbmp";
        break;
    case IMAGETYPE_XBM:
        $file_ext = "xbm";
        break;
    default:
        $file_ext = "";
    }
    return $file_ext;
}

function get_mime_type_by_file_extension($file_ext) {
    switch (strtolower($file_ext)) {

    // Image formats
    case "jpg":
    case "jpe":
    case "jpeg":
        $mime_type = "image/jpeg";
        break;
    case "gif":
        $mime_type = "image/gif";
        break;
    case "png":
        $mime_type = "image/png";
        break;
    case "bmp":
        $mime_type = "image/bmp";
        break;
    case "psd":
        $mime_type = "image/psd";
        break;
    case "tif":
    case "tiff":
        $mime_type = "image/tiff";
        break;
    case "iff":
        $mime_type = "image/iff";
        break;
    case "wbmp":
        $mime_type = "image/vnd.wap.wbmp";
        break;
    case "xbm":
        $mime_type = "image/x-xbitmap";
        break;
    case "swf":
        $mime_type = "application/x-shockwave-flash";
        break;

    // Text formats
    case "txt":
        $mime_type = "text/plain";
        break;
    case "css":
        $mime_type = "text/css";
        break;
    case "htm":
    case "html":
        $mime_type = "text/html";
        break;
    case "xml":
        $mime_type = "text/xml";
        break;
    case "js":
        $mime_type = "text/javascript";
        break;
    case "csv":
        $mime_type = "text/csv";
        break;
    case "rtf":
    case "rtx":
        $mime_type = "application/rtf";
        break;

    // Application document formats
    case "pdf":
        $mime_type = "application/pdf";
        break;
    case "doc":
    case "dot":
        $mime_type = "application/msword";
        break;
    case "xls":
        $mime_type = "application/vnd.ms-excel";
        break;
    case "ppt":
        $mime_type = "application/vnd.ms-powerpoint";
        break;
    case "ai":
    case "eps":
    case "ps":
        $mime_type = "application/postscript";
        break;

    // Archive formats
    case "zip":
        $mime_type = "application/zip";
        break;
    case "gz":
        $mime_type = "application/x-gzip";
        break;
    case "tar":
        $mime_type = "application/x-tar";
        break;

    // Sound formats
    case "mp2":
    case "mp3":
        $mime_type = "audio/mpeg";
        break;
    case "wav":
        $mime_type = "audio/x-wav";
        break;
    case "mid":
        $mime_type = "audio/x-midi";
        break;

    // Video formats
    case "mpg":
    case "mpe":
    case "mpeg":
        $mime_type = "video/mpeg";
        break;
    case "qt":
    case "mov":
        $mime_type = "video/quicktime";
        break;
    case "avi":
        $mime_type = "video/x-msvideo";
        break;

    default:
        $mime_type = "application/octet-stream";
    }
    return $mime_type;
}

?>