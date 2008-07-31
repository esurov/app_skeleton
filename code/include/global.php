<?php

function get_full_name($first_name, $last_name) {
    return trim("{$first_name} {$last_name}");
}

function get_full_name_reversed($first_name, $last_name) {
    if (is_value_empty($last_name)) {
        if (is_value_empty($first_name)) {
            return "";
        } else {
            return $first_name;
        }
    } else {
        if (is_value_empty($first_name)) {
            return $last_name;
        } else {
            return "{$last_name}, {$first_name}";
        }
    }
}

function get_excel_csv_safe_string($unsafe_str) {
    $str = str_replace("\n", " ", $unsafe_str);
    $str = str_replace("\r", "", $str);
    return str_replace('"', '""', $str);
}

?>