<?php

class NewsArticleTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "created_date",
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
    }
//
    function insert_filters() {
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
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "title",
                "type" => "not_empty",
                "message" => "news_article.title_empty",
            ),
        );
    }

    function validate($context = null, $context_params = array()) {
        $messages = parent::validate($context, $context_params);

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
                    "message" => "news_article.image_bad",
                )
            );
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
                    "message" => "news_article.file_bad",
                )
            );

            $uploaded_file_info = get_uploaded_file_info("file");
            $filesize = $uploaded_file_info["size"];
            if ($filesize > $this->get_config_value("news_article_file_max_size")) {
                $messages[] = new ErrorStatusMsg("news_article.file_max_size_reached");
            }
            if (
                $this->get_files_total_size("file_id") + $filesize >
                    $this->get_config_value("news_article_files_max_total_size")
            ) {
                $messages[] = new ErrorStatusMsg("news_article.files_max_total_size_reached");
            }
        } /* else {
            $orig_file_id = $this->get_orig_field_value("file_id");
            if ($orig_file_id == 0) {
                $messages[] = new ErrorStatusMsg("news_article.file_empty");
            }
        } */

        return $messages;
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if (
            $this->_context == "index_list_item" ||
            $this->_context == "news_articles_list_item" ||
            $this->_context == "news_articles_admin_list_item"
        ) {
            $title_short_len = $this->get_config_value("news_article_title_short_length");
            $this->app->print_varchar_value(
                "news_article.title.short",
                get_word_shortened_string(strip_tags($this->title), $title_short_len, "...")
            );
            
            $body_short_len = $this->get_config_value("news_article_body_short_length");
            $this->app->print_varchar_value(
                "news_article.body.short",
                get_word_shortened_string(strip_tags($this->body), $body_short_len, "...")
            );

            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->thumbnail_image_id),
                $this->_templates_dir,
                "news_article.thumbnail_image",
                "_thumbnail_image.html",
                "_thumbnail_image_empty.html"
            );
            
            if ($this->_context == "news_articles_admin_list_item") {
                $this->app->print_db_object_info(
                    $this->app->fetch_file_without_content($this->file_id),
                    $this->_templates_dir,
                    "news_article.file_info",
                    "_file_info.html",
                    "_file_info_empty.html"
                );
            }
        }
        
        if (
            $this->_context == "news_article_edit" ||
            $this->_context == "news_article_view"
        ) {
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->image_id),
                $this->_templates_dir,
                "news_article.image",
                "_image.html",
                "_image_empty.html"
            );
            $this->app->print_db_object_info(
                $this->app->fetch_file_without_content($this->file_id),
                $this->_templates_dir,
                "news_article.file_info",
                "_file_info.html",
                "_file_info_empty.html"
            );
        }
    }

}

?>