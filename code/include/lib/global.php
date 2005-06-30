<?php

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
    return !is_null($variable) ? $value : $variable;
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

// Quote and escape string for MySql.
function qw($str) {
    return "'" . mysql_escape_string($str) . "'" ;
}

// Quote and escape string for MySql LIKE expression.
function lqw($str, $prefix_str = "", $suffix_str = "") {
    $str = mysql_escape_string($str);
    $str = str_replace('%', '\%', $str);
    $str = str_replace('_', '\_', $str);
    return "'{$prefix_str}{$str}{$suffix_str}'";
}

function create_dependency_js(
    $formName, $name, $depSelectName, $dep_array
) {
    $dependenciesStr = create_dependency_str($dep_array);
    return <<<JS
        <script>

        dependencies[dependencies.length] = new Dependency(
            '{$formName}',
            '{$name}',
            '{$depSelectName}',
            new Array(
{$dependenciesStr}
            )
        );

        </script>

JS;
}

function create_dependency_str($dep_array) {
    $elements = array();
    foreach ($dep_array as $dep_select_array) {
        $elements[] = '    new Array("' . implode('", "', $dep_select_array) . '")';
    }
    return implode(",\n", $elements) . "\n";
}

// Print common HTML controls
function print_html_input($type, $name, $value, $attrs = "") {
    if ($attrs != "") {
        $attrs = " " . $attrs;
    }
    $value_safe = get_html_safe_string($value);
    return "<input type=\"{$type}\" name=\"{$name}\" value=\"{$value_safe}\"{$attrs}>";
}

function print_html_hidden($name, $value) {
    return print_html_input("hidden", $name, $value);
}

function print_html_checkbox($name, $value) {
    $checked_str = (intval($value) == 0) ? "" : "checked";
    return print_html_input("checkbox", $name, "1", $checked_str);
}

function print_html_select($name, $value_caption_pairs, $current_value, $attrs = "") {
    if ($attrs != "") {
        $attrs = " " . $attrs;
    }
    $select_options = print_html_select_options($value_caption_pairs, $current_value);
    $output =
        "<select name=\"{$name}\"{$attrs}>\n" .
        "{$select_options}" .
        "</select>\n";

    return $output;
}

function print_html_select_options($value_caption_pairs, $current_value) {
    if (count($value_caption_pairs) == 0) {
        return "";
    }

    $selected_value = get_selected_value(
        $value_caption_pairs, $current_value, $current_value
    );

    $output = "";
    foreach ($value_caption_pairs as $value => $caption) {
        $output .= print_html_select_option(
            $value,
            $caption,
            ($value == $selected_value) ? " selected" : ""
        );
    }
    return $output;
}

function print_html_select_option($value, $caption, $selected_str = "") {
    $value_safe = get_html_safe_string($value);
    $caption_safe = get_html_safe_string($caption);
    return "<option value=\"{$value_safe}\"{$selected_str}>{$caption_safe}</option>\n";
}

function print_html_radio_group(
    $name, $value_caption_pairs, $current_value, $default_value = null
) {
    if (count($values) == 0) {
        return "";
    }

    if (is_null($default_value)) {
        $default_value = $current_value;
    }

    $checked_value = get_selected_value(
        $value_caption_pairs, $current_value, $default_value
    );

    $output = "";
    foreach ($value_caption_pairs as $value => $caption) {
        $checked_str = ($value == $checked_value) ? "checked" : "";
        $output .= print_html_radio($name, $value, $checked_str);
        $output .= get_html_safe_string($caption) . "<br>\n";
    }
    return $output;
}

function print_html_radio($name, $value, $checked_str = "") {
    return print_html_input("radio", $name, $value, $checked_str);
}

function print_html_textarea($name, $value, $cols, $rows) {
    return
        "<textarea name=\"{$name}\" cols=\"{$cols}\" rows=\"{$rows}\">" .
        get_html_safe_string($value) .
        "</textarea>";
}
//

function get_selected_value($value_caption_pairs, $current_value, $default_value) {
    $values = array_keys($value_caption_pairs);
    if (in_array($current_value, $values)) {
        $selected_value = $current_value;
    } else if (in_array($default_value, $values)) {
        $selected_value = $default_value;
    } else {
        $selected_value = $values[0];
    }
    return $selected_value;
}

function get_db_object_value_caption_pairs($obj_name, $field_name, $query_ex) {
    global $app;
    
    $obj = $app->create_db_object($obj_name);
    $res = $obj->run_expanded_select_query(
        $query_ex, array("id", $field_name)
    );

    $value_caption_pairs = array();
    while($row = $res->fetch()) {
        $obj->fetch_row($row);
        $value_caption_pairs[$obj->id] = $obj->{$field_name};
    }
    return $value_caption_pairs;
}

function get_values_from_value_caption_pairs($pairs) {
    return array_keys($pairs);
}

function get_captions_from_value_caption_pairs($pairs) {
    return array_values($pairs);
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

?>