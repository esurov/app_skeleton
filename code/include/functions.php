<?php

function sql2app_date($date) {
    list($year, $month, $day) = explode('-', $date);
    return "{$day}/{$month}/{$year}";
}

function sql2app_time($time) {
    list($hour, $minute, $second) = explode(':', $time);
    return "{$hour}.{$minute}.{$second}";
}


function app2sql_date($date) {
    $parts = explode('/', $date);
    if (count($parts) != 3) {
        return '';
    }
    list($day, $month, $year) = $parts;
    return sprintf("%04d-%02d-%02d", $year, $month, $day);
}

function app2sql_time($time) {
    $parts = explode('.', $time);
    if (count($parts) != 3) {
        return '';
    }
    list($hour, $minute, $second) = $parts;
    return sprintf("%02d:%02d:%02d", $hour, $minute, $second);
}

function format_currency($value) {
    return number_format($value, 2, ".", "");
}

function escape_js($str) {
    return str_replace("'", "\'", $str);
}

function get_textarea_as_html($str) {
    $html = htmlspecialchars($str);
    $html = preg_replace('/\r/', ''    , $html);
    $html = preg_replace('/\n/', '<br>', $html);
    return $html;
}

function create_dependencies_js(
    $formName, $name, $depSelectName, $dep_array
) {
    $dependenciesStr = create_dependencies_str($dep_array);
    return
        "<script language=\"JavaScript\">

        depends[depends.length] = new Dependency(
            '{$formName}',
            '{$name}',
            '{$depSelectName}',
            new Array(
            {$dependenciesStr}
            )
        );

        </script>";
}

function create_dependencies_str($dep_array) {
    $elements = array();
    foreach ($dep_array as $dep_select_array) {
        $elements[] = '    new Array("' . implode('", "', $dep_select_array) . '")';
    }
    return implode(",\n", $elements) . "\n";
}

?>
