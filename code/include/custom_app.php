<?php

class CustomApp extends App {
    var $popup = false;
    var $report = false;
    var $printable = false;

    function CustomApp($app_name, $tables) {
        parent::App($app_name, $tables);
    }

    function run_action($action_name = null, $action_params = array()) {
        $this->popup = (int) param("popup");
        $this->report = (int) param("report");
        $this->printable = (int) param("printable");

        if (!$this->report && !$this->printable) {
            $this->print_hidden_input_form_value("popup", $this->popup);
            $this->print_suburl_value("popup", $this->popup);
            $this->print_session_status_messages();
        }
        $this->print_lang_menu();

        parent::run_action($action_name, $action_params);
    }

    function drop_pager() {
        $this->pager->n_rows_per_page = 10000;
    }

    function create_html_document_body_content() {
        $this->print_menu();
        return parent::create_html_document_body_content();
    }

    function create_html_page_template_name() {
        if ($this->popup && $this->is_file_exist("page_popup.html")) {
            $this->page_template_name = "page_popup.html";
        } else if ($this->report && $this->is_file_exist("page_report.html")) {
            $this->page_template_name = "page_report.html";
        } else if ($this->printable && $this->is_file_exist("page_printable.html")) {
            $this->page_template_name = "page_printable.html";
        } else {
            parent::create_html_page_template_name();
        }
    }

    function get_app_extra_suburl_params() {
        $params = array();
        if ($this->popup != 0) {
            $params["popup"] = $this->popup;
        }
        return $params;
    }
//
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