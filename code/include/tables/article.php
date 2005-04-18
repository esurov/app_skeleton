<?php

class Article extends CustomDbObject {

    function Article() {

        parent::CustomDbObject("article");

        $this->insert_field(array(
            "column" => "id",
            "type"   => "integer",
            "attr"   => "auto_increment",
        ));

        $this->insert_field(array(
            "column" => "created",
            "type"   => "date",
            "value"  => $this->mysql_now_date(),
        ));

        $this->insert_field(array(
            "column" => "title",
            "type"   => "varchar",
            "value"  => "",
            "multilingual" => 1,
        ));

        $this->insert_field(array(
            "column" => "body",
            "type"   => "text",
            "value"  => "",
            "multilingual" => 1,
        ));
//
        $this->insert_field(array(
            "name"   => "full_text",
            "type"   => "text",
            "value"  => "",
            "select" =>
                "concat(article.title_{$this->lang}, article.body_{$this->lang})",
        ));
//
        $this->insert_where_condition(array(
            "name"     => "full_text",
            "relation" => "like",
        ));

        $this->table_indexes =
            "index(created), " .
            "primary key(id)";
    }

    function write($fields = null) {
        $h = parent::write($fields);

        $h["article_title_short"] = $this->shorten($this->title, 40);

        $body_short_len = $this->config->value("article_body_short_length");
        $h["article_body_short"] = $this->shorten($this->body, $body_short_len);
        
        return $h;
    }

    function shorten($str, $max_length) {
        $shortened_str = strip_tags($str);
        if (strlen($shortened_str) > $max_length) {
            $n = strpos($shortened_str, ' ', $max_length);
            $shortened_str =
                $n ?
                substr($shortened_str, 0, $n):
                substr($shortened_str, 0, $max_length);
            $shortened_str .= "...";
        } else {
            $shortened_str = $str;
        }
        return $shortened_str;
    }
}

?>