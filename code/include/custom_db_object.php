<?php

class CustomDbObject extends DbObject {

    function get_app_datetime_format() {
        return "d/m/y h.i.s";
    }

    function get_app_date_format() {
        return "d/m/y";
    }

    function get_app_time_format() {
        return "h.i.s";
    }

    function get_app_double_value($php_double_value, $decimals) {
        return format_double_value($php_double_value, $decimals, ",", ".");
    }

    function get_php_double_value($app_double_value) {
        $result = str_replace(".", "", $app_double_value);
        $result = str_replace(",", ".", $result);
        return doubleval($result);
    }

    function get_app_integer_value($php_integer_value) {
        return format_integer_value($php_integer_value, ".");
    }

    function get_php_integer_value($app_integer_value) {
        $result = str_replace(".", "", $app_integer_value);
        $result = str_replace(",", "", $result);
        return intval($result);
    }
}

?>