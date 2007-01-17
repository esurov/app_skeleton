<?php

class CustomApp extends App {

    function CustomApp($app_class_name, $app_name) {
        parent::App($app_class_name, $app_name);
    }

    // Italian integers
    function get_app_integer_value($php_integer_value) {
        return format_integer_value($php_integer_value, ".");
    }

    function get_php_integer_value($app_integer_value) {
        $result = str_replace(".", "", $app_integer_value);
        $result = str_replace(",", "", $result);
        return (is_php_number($result)) ? (int) $result : 0;
    }

    // Italian float numbers
    function get_app_double_value($php_double_value, $decimals) {
        return format_double_value($php_double_value, $decimals, ",", ".");
    }

    function get_php_double_value($app_double_value) {
        $result = str_replace(".", "", $app_double_value);
        $result = str_replace(",", ".", $result);
        return (is_php_number($result)) ? (double) $result : 0.0;
    }

    function get_currency_nonset_value_caption_pair() {
        return array(0.0, $this->get_lang_str("not_specified"));
    }

}

?>