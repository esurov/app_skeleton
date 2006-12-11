<?php

class CustomApp extends App {

    function CustomApp($app_name, $tables) {
        parent::App($app_name, $tables);
    }

    function get_app_double_value($php_double_value, $decimals) {
        return format_double_value($php_double_value, $decimals, ",", ".");
    }

    function get_php_double_value($app_double_value) {
        $result = str_replace(".", "", $app_double_value);
        $result = str_replace(",", ".", $result);
        return (double) $result;
    }

    function get_app_integer_value($php_integer_value) {
        return format_integer_value($php_integer_value, ".");
    }

    function get_php_integer_value($app_integer_value) {
        $result = str_replace(".", "", $app_integer_value);
        $result = str_replace(",", "", $result);
        return (int) $result;
    }

    function get_currency_nonset_value_caption_pair() {
        return array(0.0, $this->get_message("not_specified"));
    }

}

?>