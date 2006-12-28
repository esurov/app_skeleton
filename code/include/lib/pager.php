<?php

// Class for page splitting
class Pager {

    var $app;

    var $n_rows_per_page;
    var $n_total_rows;

    // "exponential" or "simple"
    var $style;
    var $suburl_params;

    var $_offset;

    function init($params) {
        $this->_offset = 0;

        $this->n_rows_per_page = get_param_value($params, "n_rows_per_page", null);
        if (is_null($this->n_rows_per_page)) {
            $this->n_rows_per_page = $this->app->config->get_value(
                "{$this->app->app_name}_rows_per_page",
                20
            );
        }
        $this->n_total_rows = get_param_value($params, "n_total_rows", 0);

        $this->style = get_param_value($params, "style", null);
        if (is_null($this->style)) {
            $this->style = "exponential";
        }
        $this->suburl_params = get_param_value($params, "suburl_params", array());
    }
//
    function read() {
        // Read current offset from CGI and ensure correct it if needed
        $offset = (int) param("offset");

        if ($this->n_rows_per_page >= $this->n_total_rows) {
            $offset = 0;
        }
        if ($offset >= $this->n_total_rows) {
            $offset = (int) (
                (($this->n_total_rows - 1) / $this->n_rows_per_page) * $this->n_rows_per_page
            );
        }
        $this->_offset = $offset;
    }

    function get_suburl_params() {
        return array("offset" => $this->_offset);
    }

    function get_query_ex() {
        return array(
            "limit" => "{$this->_offset}, {$this->n_rows_per_page}",
        );
    }
//
    function print_values() {
        switch ($this->style) {
        case "exponential":
            $this->print_exponential_nav_str();
            break;
        case "simple":
            $this->print_simple_nav_str();
            break;
        }
    }

    function print_exponential_nav_str() {
        // Create navigation links
        $nav_str = $this->app->get_message("pager_pages_title") . " \n";

        $suburl_params_str = create_suburl($this->suburl_params, "&amp;");

        $p = (int) ceil($this->n_total_rows / $this->n_rows_per_page);
        $cp = $this->_offset / $this->n_rows_per_page + 1;

        for ($i = 0; $i < $p; ++$i) {
            $cur_offset = $i * $this->n_rows_per_page;
            $cur_page   = $i + 1;
            if ($cur_offset == $this->_offset) {  // current page
                $nav_str .= "<b>[$cur_page]</b>\n";
            } else {
                $dist = abs($cur_page - $cp);
                if ($dist <    5                          ||
                    $dist <   50 && $cur_page %   10 == 0 ||
                    $dist <  500 && $cur_page %  100 == 0 ||
                    $dist < 5000 && $cur_page % 1000 == 0 ||
                    $i == 0                               ||
                    $i + 1 == $p
                ) {
                    $nav_str .=
                        "[<a href=\"?{$suburl_params_str}&amp;offset={$cur_offset}\">" .
                        "{$cur_page}</a>]\n";
                }
            }
        }
        
        $this->app->print_raw_value("nav_str", $nav_str);
    }

    function print_simple_nav_str() {
        // Create simplified navigation links (previous page and next page).
        $nav_str = "";

        $suburl_params_str = create_suburl($this->suburl_params, "&amp;");

        $prev_offset = $this->_offset - $this->n_rows_per_page;
        $next_offset = $this->_offset + $this->n_rows_per_page;

        if ($prev_offset >= 0) {
            $prev_page_str = $this->app->get_message("pager_previous_page");
            $nav_str .=
                "<a href=\"{$suburl_params_str}&amp;offset={$prev_offset}\">" .
                "&lt;&lt;&nbsp;{$prev_page_str}</a>&nbsp;&nbsp;&nbsp;\n";
        }
        
        if ($next_offset < $this->n_total_rows) {
            $next_page_str = $this->app->get_message("pager_next_page");
            $nav_str .=
                "<a href=\"{$suburl_params_str}&amp;offset={$next_offset}\">" .
                "{$next_page_str}&nbsp;&gt;&gt;</a>\n";
        }

        $this->app->print_raw_value("nav_str", $nav_str);
    }

}

?>