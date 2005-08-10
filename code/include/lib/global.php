<?php

function v($obj) {
    var_dump($obj);
}

function param($name) {
    if (isset($_GET[$name])) {
        $res = $_GET[$name];
    } else if (isset($_POST[$name])) {
        $res = $_POST[$name];
    } else {
        return null;
    }
    
    if (get_magic_quotes_gpc()) {
        $res = stripslashes($res);
    }

    return $res;
}

function param_cookie($name) {
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
}

function params() {
    return array_merge($_POST, $_GET);
}

function create_suburl($ampersand, $params) {
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
    return join($ampersand, $pairs);
}

function create_self_url($params) {
    $params_suburl = create_http_suburl($params);
    $self_suburl = $_SERVER["SCRIPT_NAME"];
    return "{$self_suburl}?{$params_suburl}";
}

function create_self_full_url($params, $protocol = "http") {
    $host = $_SERVER["HTTP_HOST"];
    $self_url = create_self_url($params);
    return "{$protocol}://{$host}{$self_url}";
}

function create_http_suburl($params) {
    return create_suburl("&", $params);
}

function create_html_suburl($params) {
    return create_suburl("&amp;", $params);
}

function if_null($variable, $value) {
    return is_null($variable) ? $value : $variable;
}

function if_not_null($variable, $value) {
    return !is_null($variable) ? $value : null;
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

//
function format_double_value(
    $double_value, $decimals, $dec_point, $thousands_sep
) {
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

function get_shortened_string($str, $max_length, $end_str = "...") {
    if (strlen($str) > $max_length) {
        $str = substr($str, 0, $max_length);
        return "{$str}{$end_str}";
    } else {
        return $str;
    }
}

function get_word_shortened_string($str, $max_length, $end_str = "...") {
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
function print_html_input($type, $name, $value, $attrs = array()) {
    $attrs_str = get_html_attrs_str($attrs);
    $value_safe = get_html_safe_string($value);
    return "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value_safe}\"{$attrs_str}>";
}

function print_html_hidden($name, $value) {
    return print_html_input("hidden", $name, $value);
}

function print_html_checkbox($name, $value, $attrs = array()) {
    if (intval($value) != 0) {
        $attrs["checked"] = null;
    }
    return print_html_input("checkbox", $name, "1", $attrs);
}

function print_html_select($name, $value_caption_pairs, $current_value, $attrs = array()) {
    $attrs_str = get_html_attrs_str($attrs);
    $select_options = print_html_select_options($value_caption_pairs, $current_value);
    return
        "<select name=\"{$name}\"{$attrs_str}>\n" .
        "{$select_options}" .
        "</select>\n";
}

function print_html_select_options($value_caption_pairs, $current_value) {
    if (count($value_caption_pairs) == 0) {
        return "";
    }

    if (is_array($current_value)) {
//        $selected_value = get_multiple_selected_values(
//            $value_caption_pairs, $current_values
//        );
    } else {
        $selected_value = get_selected_value(
            $value_caption_pairs, $current_value, $current_value
        );
    }

    $output = "";
    foreach ($value_caption_pairs as $value_caption_pair) {
        $value = get_value_from_value_caption_pair($value_caption_pair);
        $caption = get_caption_from_value_caption_pair($value_caption_pair);
        $attrs = array();
        if (
            (!is_array($selected_value) && ((string) $value == (string) $selected_value)) ||
            (is_array($selected_value) && in_array($value, $selected_value))
        ) {
            $attrs["selected"] = null;
        }
        $output .= print_html_select_option($value, $caption, $attrs);
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
    $name, $value_caption_pairs, $current_value, $default_value = null, $is_xhtml = false
) {
    if (count($value_caption_pairs) == 0) {
        return "";
    }

    if (is_null($default_value)) {
        $default_value = $current_value;
    }

    $checked_value = get_selected_value(
        $value_caption_pairs, $current_value, $default_value
    );

    $output = "";
    foreach ($value_caption_pairs as $value_caption_pair) {
        $value = get_value_from_value_caption_pair($value_caption_pair);
        $caption = get_caption_from_value_caption_pair($value_caption_pair);

        $attrs = array();
        if ((string) $value == (string) $checked_value) {
            $attrs["checked"] = null;
        }
        $output .= print_html_radio($name, $value, $attrs);
        $br_str = ($is_xhtml) ? "<br />\n" : "<br>\n";
        $output .= get_html_safe_string($caption) . $br_str;
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
function get_selected_value($value_caption_pairs, $current_value, $default_value) {
    $values = get_values_from_value_caption_pairs($value_caption_pairs);
    if (in_array($current_value, $values)) {
        $selected_value = $current_value;
    } else if (in_array($default_value, $values)) {
        $selected_value = $default_value;
    } else {
        $selected_value = $values[0];
    }
    return $selected_value;
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

//
function create_select_dependency_js(
    $form_name, $main_select_name, $dependent_select_name, $dependency_array
) {
    $dependency_str = create_select_dependency_str($dependency_array);
    return <<<JS
<script>
dependencies[dependencies.length] = new Dependency(
    '{$form_name}',
    '{$main_select_name}',
    '{$dependent_select_name}',
    new Array(
{$dependency_str}
    )
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
<script>
conditions = new Array(
{$client_validate_conditions_str}
);
document.forms['form'].onsubmit = onsubmitValidateFormHandler;
</script>

JS;
}

function create_client_validate_condition_str(
    $input_name, $condition_type, $message_text, $param, $dependent_validate_condition_str
) {
    $message_text_param = (is_null($message_text)) ?
        "null" : quote_string(get_js_safe_string($message_text));

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
    $date_parts_unordered = array();

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
                $date_parts["year"] = $date_parts_unordered[$p++];
                break;
            case "m":
                $date_parts["month"] = $date_parts_unordered[$p++];
                break;
            case "d":
                $date_parts["day"] = $date_parts_unordered[$p++];
                break;
            case "h":
                $date_parts["hour"] = $date_parts_unordered[$p++];
                break;
            case "i":
                $date_parts["minute"] = $date_parts_unordered[$p++];
                break;
            case "s":
                $date_parts["second"] = $date_parts_unordered[$p++];
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
        case "i":
            $res .= '(\d{1,2})';
            break;
        case "s":
            $res .= '(\d{1,2})';
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
    $res .= "/";
    return $res;
}

function create_date_by_format($format, $date_parts, $date_if_unknown) {
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
        case "i":
            $res .= sprintf("%02d", $date_parts["minute"]);
            break;
        case "s":
            $res .= sprintf("%02d", $date_parts["second"]);
            break;
        default:
            $res .= $format_char;
        }
    }
    if (
        $date_parts["year"] == 0 &&
        $date_parts["month"] == 0 &&
        $date_parts["day"] == 0 &&
        $date_parts["hour"] == 0 &&
        $date_parts["minute"] == 0 &&
        $date_parts["second"] == 0
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

function get_gmt_str_from_timestamp($timestamp) {
    $gmt_str = gmdate("D, d M Y H:i:s", $timestamp);
    return "{$gmt_str} GMT";
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

?>