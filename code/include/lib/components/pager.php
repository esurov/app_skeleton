<?php

// Class for page splitting
class Pager extends AppComponent {

    // Current row offset (starts from 0)
    var $offset;

    // Number of rows per page
    var $n_rows_per_page;

    // Total number of rows
    var $n_total_rows;

    // Common CGI parameters of generated links
    var $suburl_params;

    // "prev_next", "exponential" or "exponential_prev_next"
    var $type;

    // Pages title
    var $pages_title_str;

    // Previous page link title
    var $prev_page_str;

    // Next page link title
    var $next_page_str;

    // String used to prepend to page number in links
    var $page_begin_str;

    // String used to append to page number in links
    var $page_end_str;

    // Delimiter string used to separate non-grouped links
    var $delimiter_str;

    // Page number based on current value of offset (starts from 1)
    var $_current_page;

    // Total pages count based on current values of 'total rows count' and 'rows per page'
    var $_n_total_pages;

    // Common CGI parameters of generated links as string
    var $_suburl_params_str;

    function _init($params) {
        $this->set_n_total_rows(get_param_value($params, "n_total_rows", 0));

        $n_rows_per_page = get_param_value($params, "n_rows_per_page", null);
        if (is_null($n_rows_per_page)) {
            $n_rows_per_page = $this->get_config_value(
                "{$this->app->app_name}.pager.rows_per_page",
                20
            );
        }
        $this->set_n_rows_per_page($n_rows_per_page);

        $this->set_offset(0);

        $this->set_suburl_params(get_param_value($params, "suburl_params", array()));

        $this->type = get_param_value($params, "type", null);
        if (is_null($this->type)) {
            $this->type = "exponential_prev_next";
        }

        $this->pages_title_str = get_param_value($params, "pages_title_str", null);
        if (is_null($this->pages_title_str)) {
            $this->pages_title_str = $this->get_lang_str("pager.pages_title");
        }

        $this->prev_page_str = get_param_value($params, "prev_page_str", null);
        if (is_null($this->prev_page_str)) {
            $this->prev_page_str = $this->get_lang_str("pager.prev_page");
        }

        $this->next_page_str = get_param_value($params, "next_page_str", null);
        if (is_null($this->next_page_str)) {
            $this->next_page_str = $this->get_lang_str("pager.next_page");
        }

        $this->page_begin_str = get_param_value($params, "page_begin_str", null);
        if (is_null($this->page_begin_str)) {
            $this->page_begin_str = $this->get_lang_str("pager.page_begin");
        }

        $this->page_end_str = get_param_value($params, "page_end_str", null);
        if (is_null($this->page_end_str)) {
            $this->page_end_str = $this->get_lang_str("pager.page_end");
        }

        $this->delimiter_str = get_param_value($params, "delimiter_str", null);
        if (is_null($this->delimiter_str)) {
            $this->delimiter_str = $this->get_lang_str("pager.delimiter");
        }
    }
//
    function set_n_total_rows($n_total_rows) {
        $this->n_total_rows = $n_total_rows;
    }

    function set_n_rows_per_page($n_rows_per_page) {
        $this->n_rows_per_page = $n_rows_per_page;

        $this->_n_total_pages = (int) ceil($this->n_total_rows / $this->n_rows_per_page);
    }

    function set_offset($offset) {
        $this->offset = $offset;

        $this->_current_page = (int) ($this->offset / $this->n_rows_per_page) + 1;
    }

    function set_suburl_params($suburl_params) {
        $this->suburl_params = $suburl_params;

        $this->_suburl_params_str = create_suburl($this->suburl_params, "&amp;");
    }
//
    function get_query_ex() {
        return array(
            "limit" => "{$this->offset}, {$this->n_rows_per_page}",
        );
    }
//
    function read() {
        // Read current offset from CGI and ensure correct it if needed
        $offset = (int) param("offset");

        // Offset normalization
        if ($offset <= 0) {
            // Making offset to first page
            $offset = 0;
        } else {
            // Making offset page aligned
            $offset = ((int) ($offset / $this->n_rows_per_page)) * $this->n_rows_per_page;

            // Making offset to last page
            if ($offset >= $this->n_total_rows) {
                $offset = ($this->_n_total_pages - 1) * $this->n_rows_per_page;
            }
        }
        
        $this->set_offset($offset);
    }
//
    function print_values() {
        switch ($this->type) {
        case "prev_next":
            $nav_str = $this->_create_prev_next_nav_str();
            break;
        
        case "exponential":
            $nav_str = $this->_create_exponential_nav_str();
            break;
        
        case "exponential_prev_next":
            $nav_str = $this->_create_exponential_prev_next_nav_str();
            break;
        
        default:
            $nav_str = "";
        }
        $this->app->print_raw_value("nav_str", $nav_str);
        return $nav_str;
    }

    function _create_prev_next_nav_str() {
        // Create previous and next page navigation links
        return
            "{$this->pages_title_str}\n" .
            $this->_create_prev_page_link_str() .
            "&nbsp;&nbsp;" .
            $this->_create_next_page_link_str();
    }

    function _create_exponential_nav_str() {
        // Create exponential page navigation links
        return
            "{$this->pages_title_str}\n" .
            $this->_create_exponential_page_links_str();
    }

    function _create_exponential_prev_next_nav_str() {
        // Create exponential with previous and next page navigation links
        return
            "{$this->pages_title_str}\n" .
            $this->_create_prev_page_link_str() .
            "&nbsp;&nbsp;" .
            $this->_create_exponential_page_links_str() .
            "&nbsp;&nbsp;" .
            $this->_create_next_page_link_str();
    }
//
    function _get_page_offset($page) {
        return ($page - 1) * $this->n_rows_per_page;
    }

    function _create_page_str($page) {
        return "<strong>{$this->page_begin_str}{$page}{$this->page_end_str}</strong>\n";
    }

    function _create_page_link_str($page) {
        $page_offset = $this->_get_page_offset($page);
        return
            "{$this->page_begin_str}" .
            "<a href=\"?{$this->_suburl_params_str}&amp;offset={$page_offset}\">{$page}</a>" .
            "{$this->page_end_str}\n";
    }

    function _create_prev_page_link_str() {
        if ($this->_current_page == 1) {
            return "";
        } else {
            $prev_page_offset = $this->_get_page_offset($this->_current_page - 1);
            return
                "<a href=\"?{$this->_suburl_params_str}&amp;offset={$prev_page_offset}\">" .
                "{$this->prev_page_str}</a>\n";
        }
    }

    function _create_next_page_link_str() {
        if ($this->_current_page == $this->_n_total_pages) {
            return "";
        } else {
            $next_page_offset = $this->_get_page_offset($this->_current_page + 1);
            return
                "<a href=\"?{$this->_suburl_params_str}&amp;offset={$next_page_offset}\">" .
                "{$this->next_page_str}</a>\n";
        }
    }

    function _create_exponential_page_links_str() {
        $add_delimiter = false;
        $links_str = "";
        for ($current_page = 1; $current_page <= $this->_n_total_pages; $current_page++) {
            if ($current_page == $this->_current_page) {
                $links_str .= $this->_create_page_str($current_page);
            } else {
                $dist = abs($current_page - $this->_current_page);
                if (
                    ($dist < 5) ||
                    ($current_page == 1) ||
                    ($current_page == $this->_n_total_pages) ||
                    ($current_page == 10) ||
                    ($current_page == 50) ||
                    ($current_page == 100) ||
                    ($current_page == 500) ||
                    ($current_page == 1000) ||
                    ($current_page == 5000) ||
                    ($current_page == 1000) ||
                    ($current_page == 10000)
                ) {
                    if ($add_delimiter) {
                        $links_str .= "{$this->delimiter_str}\n";
                        $add_delimiter = false;
                    }
                    $links_str .= $this->_create_page_link_str($current_page);
                } else {
                    $add_delimiter = true;
                }
            }
        }
        return $links_str;
    }

}

?>