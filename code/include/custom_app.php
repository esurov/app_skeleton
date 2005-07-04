<?php

class CustomApp extends App {
    var $popup = false;
    var $report = false;

    function CustomApp($app_name, $tables) {
        parent::App($app_name, $tables);
    }

    function run_action() {
        $this->popup = intval(param("popup"));
        $this->report = intval(param("report"));

        if (!$this->report) {
            $this->page->assign(array(
                "popup_url_param" =>
                    "&amp;popup={$this->popup}",
                "popup_hidden" =>
                    "<input type=\"hidden\" name=\"popup\" value=\"{$this->popup}\">\n",
            ));
            $this->print_session_status_messages();
        }
        $this->print_lang_menu();

        parent::run_action();
    }

    function drop_pager() {
        $this->pager = new Pager($this, 10000, 10000);
    }

    function create_html_page_response_body() {
        if ($this->report) {
            $template_type = ($this->page->is_template_exist("report.html")) ?
                "report" :
                "page";
        } else if ($this->popup) {
            $template_type = ($this->page->is_template_exist("popup.html")) ?
                "popup" :
                "page";
        } else {
            $template_type = "page";
        }

        if ($template_type == "page") {
            $this->print_menu();
        } else {
            $this->page_template_name = "{$template_type}.html";
        }
        
        return parent::create_html_page_response_body();
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