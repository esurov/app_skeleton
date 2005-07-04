<?php

class NewsArticle extends CustomDbObject {
    function NewsArticle() {
        parent::CustomDbObject("news_article");

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "created",
            "type" => "date",
            "value" => $this->app->get_db_now_date(),
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "title",
            "type" => "varchar",
            "multilingual" => 1,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "body",
            "type" => "text",
            "multilingual" => 1,
        ));

        $this->insert_field(array(
            "field" => "image_id",
            "type" => "foreign_key",
            "read" => 0,
        ));
//
        $this->insert_field(array(
            "field" => "full_text",
            "type" => "text",
            "select" =>
                "CONCAT(news_article.title_{$this->lang}, news_article.body_{$this->lang})",
        ));
//
        $this->insert_filter(array(
            "name" => "full_text",
            "relation" => "like",
        ));
    }

    function print_values($params = array()) {
        $h1 = parent::print_values($params);
        $h = array();

        $title_short_len = $this->config->get_value("news_article_title_short_length");
        $h["news_article_title_short"] =
            get_word_shortened_string(strip_tags($this->title), $title_short_len);
        
        $body_short_len = $this->config->get_value("news_article_body_short_length");
        $h["news_article_body_short"] =
            get_word_shortened_string(strip_tags($this->body), $body_short_len);

        $this->print_image();

        $this->assign_values($h);
        return $h1 + $h;
    }

    function del() {
        $this->del_image();
      
        parent::del();
    }

    function validate($old_obj = null) {
        $messages = array();

        if (!$this->validate_not_empty_field("title")) {
            $messages[] = new ErrorStatusMsg("news_article_title_empty");
//        } else {
//            if (!$this->validate_unique_field("title", $old_obj)) {
//                $messages[] = new ErrorStatusMsg("shop_name_exists");
//            }
        }

        return $messages;
    }
}

?>