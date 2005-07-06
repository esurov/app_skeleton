<?php

class Pager {
    // Simple class for page splitting
    var $n_rows_per_page;
    var $n_total_rows;

    var $offset;

    var $app;

    function Pager(&$app, $n_rows_per_page) {
        $this->app =& $app;

        $this->n_rows_per_page = $n_rows_per_page;
        $this->n_total_rows = 0;
        $this->offset = 0;
    }

    function set_total_rows($n_total_rows) {
        $this->n_total_rows = $n_total_rows;
    }

    function read() {
        // Read current offset from CGI and ensure correct it if needed
        $offset = intval(param("offset"));

        if ($this->n_rows_per_page >= $this->n_total_rows) {
            $offset = 0;
        }
        if ($offset >= $this->n_total_rows) {
            $offset = intval(
                (($this->n_total_rows - 1) / $this->n_rows_per_page) * $this->n_rows_per_page
            );
        }
        $this->offset = $offset;
    }

    function get_limit_clause() {
        // Return LIMIT clause
        return "{$this->offset}, {$this->n_rows_per_page}";
    }


    function get_pages_nav_str($url_str) {
        // Create navigation links
        $nav_str = $this->app->get_message("pager_pages_title") . " \n";

        $p = intval(ceil($this->n_total_rows / $this->n_rows_per_page));
        $cp = $this->offset / $this->n_rows_per_page + 1;

        for ($i = 0; $i < $p; ++$i) {
            $cur_offset = $i * $this->n_rows_per_page;
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
                        "[<a href=\"{$url_str}&amp;offset={$cur_offset}\">{$cur_page}</a>]\n";
                }
            }
        }

        return $nav_str;
    }


    function get_simple_nav_str($url_str) {
        // Create simplified navigation links (previous page and next page).
        $simple_nav_str = "";

        $prev_offset = $this->offset - $this->n_rows_per_page;
        $next_offset = $this->offset + $this->n_rows_per_page;

        if ($prev_offset >= 0) {
            $simple_nav_str .=
                "<a href=\"{$url_str}&amp;offset={$prev_offset}\">" .
                "&lt;&lt;&nbsp;Previous&nbsp;results</a>&nbsp;&nbsp;&nbsp;\n";
        }
        
        if ($next_offset < $this->n_total_rows) {
            $simple_nav_str .=
                "<a href=\"{$url_str}&amp;offset={$next_offset}\">" .
                "Next&nbsp;results&nbsp;&gt;&gt;</a>\n";
        }

        return $simple_nav_str;
    }
}

?>