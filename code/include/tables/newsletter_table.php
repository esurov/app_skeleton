<?php

class NewsletterTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "sent_date",
            "type" => "date",
            "value" => $this->app->get_db_now_date(),
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "newsletter_category_id",
            "type" => "foreign_key",
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "db_object_query",
                    "data" => array(
                        "nonset_value_caption_pair" => array(0, $this->get_lang_str("choose_one")),
                        "obj" => "NewsletterCategory",
                        "captions_field_name" => "name",
                        "query_ex" => array(
                            "where" => "is_active = 1",
                            "order_by" => "name ASC",
                        ),
                    ),
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "title",
            "type" => "varchar",
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
        $this->insert_join(array(
            "type" => "left",
            "obj_class" => "NewsletterCategory",
            "condition" => "{$this->_table_name}.newsletter_category_id = newsletter_category.id",
        ));

        $this->insert_field(array(
            //newsletter_newsletter_category_name
            "obj_class" => "NewsletterCategory",
            "field" => "name",
        ));
    }
//
    function insert_filters() {
        $this->insert_filter(array(
            "name" => "full_text",
            "relation" => "like_many",
            "type" => "text",
            "select" => array(
                "newsletter.title",
                "newsletter.body",
            ),
        ));

        $this->insert_filter(array(
            "name" => "newsletter_category_id",
            "relation" => "equal",
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "db_object_query",
                    "data" => array(
                        "nonset_value_caption_pair" => array(0, $this->app->get_lang_str("all")),
                        "obj" => "NewsletterCategory",
                        "query_ex" => array(
                            "order_by" => "name",
                        ),
                        "captions_field_name" => "name",
                    ),
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "sent_date",
            "relation" => "greater_equal",
            "input" => array(
                "type_attrs" => array(
                    "class" => "date_range",
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "sent_date",
            "relation" => "less_equal",
            "input" => array(
                "type_attrs" => array(
                    "class" => "date_range",
                ),
            ),
        ));
    }
//
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "newsletter_category_id",
                "type" => "not_equal",
                "param" => 0,
                "message" => "newsletter.category_empty",
            ),
            array(
                "field" => "title",
                "type" => "not_empty",
                "message" => "newsletter.title_empty",
            ),
            array(
                "field" => "body",
                "type" => "not_empty",
                "message" => "newsletter.body_empty",
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
                    "message" => "newsletter.image_bad",
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
                    "message" => "newsletter.file_bad",
                )
            );

            $uploaded_file_info = get_uploaded_file_info("file");
            $filesize = $uploaded_file_info["size"];
            if ($filesize > $this->get_config_value("newsletter_file_max_size")) {
                $messages[] = new ErrorStatusMsg("newsletter.file_max_size_reached");
            }
            if (
                $this->get_files_total_size("file_id") + $filesize >
                    $this->get_config_value("newsletter_files_max_total_size")
            ) {
                $messages[] = new ErrorStatusMsg("newsletter.files_max_total_size_reached");
            }
        } /* else {
            $orig_file_id = $this->get_orig_field_value("file_id");
            if ($orig_file_id == 0) {
                $messages[] = new ErrorStatusMsg("newsletter.file_empty");
            }
        } */

        return $messages;
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "newsletters_admin_list_item") {
            $title_short_len = $this->get_config_value("newsletter_title_short_length");
            $this->app->print_varchar_value(
                "{$this->_template_var_prefix}.title.short",
                get_word_shortened_string(strip_tags($this->title), $title_short_len, "...")
            );
            
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->thumbnail_image_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.thumbnail_image",
                "_thumbnail_image.html",
                "_thumbnail_image_empty.html"
            );

            $this->app->print_db_object_info(
                $this->app->fetch_file_without_content($this->file_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.file_info",
                "_file_info.html",
                "_file_info_empty.html"
            );
        }
        
        if (
            $this->_context == "newsletter_view_admin" ||
            $this->_context == "newsletter_edit_admin"
        ) {
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->image_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.image",
                "_image.html",
                "_image_empty.html"
            );

            $this->app->print_db_object_info(
                $this->app->fetch_file_without_content($this->file_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.file_info",
                "_file_info.html",
                "_file_info_empty.html"
            );
        }
    }
    
}

?>