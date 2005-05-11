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
                "popup_url_param" => "&amp;popup={$this->popup}",
                "popup_hidden" =>
                    "<input type=\"hidden\" name=\"popup\" value=\"{$this->popup}\">\n",
            ));
            $this->print_session_status_messages();
        }
        $page_name = trim(param("page"));
        $this->page->assign(array(
            "action" => $this->action,
            "page" => $page_name,
        ));
        $this->print_page_titles($page_name);

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
        return array(
            "popup" => $this->popup,
        );
    }
}

?>