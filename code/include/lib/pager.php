<?php

class Pager {

    // Simple class for page splitting.

    var $default_rows;
    var $max_rows;

    var $offset;
    var $rows;

    var $n_min;
    var $n_max;

    var $limit;

    var $nav_str;
    var $simple_nav_str;

    var $script;

    function Pager(&$script, $default_rows, $max_rows) {
        // Constructor.

        $this->script =& $script;

        $this->default_rows = $default_rows;
        $this->max_rows     = $max_rows;

        $this->offset = 0;
        $this->rows   = 10;

        $this->n_min = 0;
        $this->n_max = 0;

        $this->limit = '';

        $this->nav_str        = '';
        $this->simple_nav_str = '';
    }


    function set_total_rows($n_min, $n_max = NULL) {
        // Set value of $this->total_rows (n).

        $this->n = $n_min;  // NB! Temporary solution
                            // for compatibility with previous Pager version

        $this->n_min = $n_min;
        $this->n_max = isset($n_max) ? $n_max : $n_min;
    }


    function read() {
        // Read $offset and $rows from CGI and ensure that these values are valid.

        $offset = intval(param('offset'));
        $rows   = intval(param('rows'));

        if (!(1 <= $rows && $rows <= $this->max_rows)) {
            $rows = $this->default_rows;
        }
        if ($rows >= $this->n_max) {
            $offset = 0;
        }
        if ($offset >= $this->n_max) {
            $offset = (($this->n_max-1) / $rows) * $rows;
        }
        //$offset -= ($offset % $rows);  // Commented to have any offset

        $this->offset = $offset;
        $this->rows   = $rows;
    }


    function get_limit_clause() {
        // Return LIMIT clause based on current values
        // of $this->offset and $this->rows

        $limit = "$this->offset, $this->rows";

        return $limit;
    }


    function get_pages_navig($url_str) {
        // Create navigation links:

        $nav_str = $this->script->get_message("pager_pages_title") . " \n";

        $p = (int) ceil($this->n_min / $this->rows);
        $cp = $this->offset / $this->rows + 1;

        for ($i = 0; $i < $p; ++$i) {
            $cur_offset = $i * $this->rows;
            $cur_page   = $i + 1;
            if ($cur_offset == $this->offset) {  // current page
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
                        "[<a href=\"$url_str&amp;offset=$cur_offset&amp;rows=$this->rows\">" .
                        "$cur_page</a>]\n";
                }
            }
        }

        return $nav_str;
    }


    function get_simple_navig($url_str) {
        // Create simplified navigation links (previous page and next page).

        $simple_nav_str = '';

        $prev_offset = $this->offset - $this->rows;
        $next_offset = $this->offset + $this->rows;

        if ($prev_offset >= 0) {
            $simple_nav_str .=
                "<a href=\"$url_str&amp;offset=$prev_offset&amp;rows=$this->rows\">" .
                "&lt;&lt;&nbsp;Previous&nbsp;results</a>&nbsp;&nbsp;&nbsp;\n";
        }
        if ($next_offset < $this->n_max) {
            $simple_nav_str .=
                "<a href=\"$url_str&amp;offset=$next_offset&amp;rows=$this->rows\">" .
                "Next&nbsp;results&nbsp;&gt;&gt;</a>\n";
        }

        return $simple_nav_str;
    }


    function run($query, $sub_url) {
        // Compute all data for page splitting.

        // NB! This function is obsolete and will be removed soon.

        global $app;

        $this->set_total_rows($app->sql->get_query_num_rows($query));

        if ($this->n_min == 0) {
            return;
        }

        $this->read();

        $url_str = "$_SERVER[SCRIPT_NAME]?$sub_url";

        $this->limit          = $this->get_limit_clause();
        $this->nav_str        = $this->get_pages_navig($url_str);
        $this->simple_nav_str = $this->get_simple_navig($url_str);
    }

}  // class Pager

?>