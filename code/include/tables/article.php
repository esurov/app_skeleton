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
            "input"  => "textarea",
        ));
//
        $this->insert_field(array(
            "name"   => "full_text",
            "type"   => "text",
            "select" =>
                "concat(article.title_{$this->lang}, article.body_{$this->lang})",
        ));
//
        $this->insert_where_condition(array(
            "name"     => "full_text",
            "relation" => "like",
        ));

        $this->table_indexes =
            "primary key(id), " .
            "index(created)";
    }

    function write($fields = null) {
        $h = parent::write($fields);

        $body_short_len = $this->config->value("article_body_short_length");
        $body_shortened = strip_tags($this->body);
        if (strlen($body_shortened) > $body_short_len) {
            $n = strpos($body_shortened, ' ', $body_short_len);
            $body_shortened =
                $n ?
                substr($body_shortened, 0, $n):
                substr($body_shortened, 0, $body_short_len);
            $body_shortened .= '...';
        } else {
            $body_shortened = $this->body;
        }
        $h["article_body_short"] = $body_shortened;
        return $h;
    }
}

?>
