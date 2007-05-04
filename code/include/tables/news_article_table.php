<?php

class NewsArticleTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

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
            "input" => array(
                "type_attrs" => array(
                    "class" => "varchar_wide",
                )
            ),
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

        $this->insert_field(array(
            "field" => "thumbnail_image_id",
            "type" => "foreign_key",
            "read" => 0,
        ));

        $this->insert_field(array(
            "field" => "file_id",
            "type" => "foreign_key",
            "read" => 0,
        ));
//
        $this->insert_filter(array(
            "name" => "full_text",
            "relation" => "like_many",
            "type" => "text",
            "select" => array(
                "news_article.title_{$this->app->lang}",
                "news_article.body_{$this->app->lang}",
            ),
        ));
    }
//
    function del() {
        $this->del_image("image_id");
        $this->del_image("thumbnail_image_id");
        $this->del_file("file_id");
      
        parent::del();
    }
//
    function get_validate_conditions() {
        return array(
            array(
                "field" => "title",
                "type" => "not_empty",
                "message" => "news_article_title_empty",
            ),
        );
    }

    function validate($old_obj = null, $context = null, $context_params = array()) {
        $messages = parent::validate($old_obj, $context, $context_params);

        if (was_file_uploaded("image_file")) {
            $this->validate_condition(
                $messages,
                array(
                    "field" => "image_id",
                    "type" => "uploaded_file_types",
                    "param" => array(
                        "input_name" => "image_file",
                        "type" => "images",
                    ),
                    "message" => "news_article_image_bad",
                ),
                $old_obj
            );

            $uploaded_file_info = get_uploaded_file_info("file");
            $filesize = $uploaded_file_info["size"];
            if ($filesize > $this->get_config_value("news_article_file_max_size")) {
                $messages[] = new ErrorStatusMsg("news_article_file_max_size_reached");
            }
            if (
                $this->get_files_total_size("file_id") + $filesize >
                    $this->get_config_value("news_article_files_max_total_size")
            ) {
                $messages[] = new ErrorStatusMsg("news_article_files_max_total_size_reached");
            }
        }

        if (was_file_uploaded("file")) {
            $this->validate_condition(
                $messages,
                array(
                    "field" => "file_id",
                    "type" => "uploaded_file_types",
                    "param" => array(
                        "input_name" => "file",
                        "type" => "files",
                    ),
                    "message" => "news_article_file_bad",
                ),
                $old_obj
            );

            $uploaded_file_info = get_uploaded_file_info("file");
            $filesize = $uploaded_file_info["size"];
            if ($filesize > $this->get_config_value("news_article_file_max_size")) {
                $messages[] = new ErrorStatusMsg("news_article_file_max_size_reached");
            }
            if (
                $this->get_files_total_size("file_id") + $filesize >
                    $this->get_config_value("news_article_files_max_total_size")
            ) {
                $messages[] = new ErrorStatusMsg("news_article_files_max_total_size_reached");
            }
        } /* else {
            if ($old_obj->file_id == 0) {
                $messages[] = new ErrorStatusMsg("news_article_file_empty");
            }
        } */

        return $messages;
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "list_item" || $this->_context == "list_item_admin") {
            $title_short_len = $this->get_config_value("news_article_title_short_length");
            $this->app->print_varchar_value(
                "news_article_title.short",
                get_word_shortened_string(strip_tags($this->title), $title_short_len, "...")
            );
            
            $body_short_len = $this->get_config_value("news_article_body_short_length");
            $this->app->print_varchar_value(
                "news_article_body.short",
                get_word_shortened_string(strip_tags($this->body), $body_short_len, "...")
            );

            $this->print_image_info("thumbnail_image_id", "_thumbnail_image");
            if ($this->_context == "list_item_admin") {
                $this->print_file_info("file_id", "_file_info");
            }
        }
        
        if ($this->_context == "view" || $this->_context == "edit") {
            $this->print_image_info("image_id", "_image");
            $this->print_file_info("file_id", "_file_info");
        }
    }

}

?>