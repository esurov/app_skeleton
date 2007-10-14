<?php

class NewsletterTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "newsletter_category_id",
            "type" => "foreign_key",
//            "read" => 0,
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
            "field" => "date_sent",
            "type" => "date",
            "value" => $this->app->get_db_now_date(),
            "index" => "index",
            "read" => 1,
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

//
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
    }
//
//    function del() {
//        $this->del_image("image_id");
//        $this->del_image("thumbnail_image_id");
//        $this->del_file("file_id");
//      
//        parent::del();
//    }
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
    function send_newsletter($user_subscription_list, $params = array()) {
        foreach($user_subscription_list as $user_account) {
            $this->send_newsletter_to_email($user_account, $params);
        }
    }

// ToDo!!: ask Alex about calling parent::print_values() not in children function print_values()
    function send_newsletter_to_email($user_account, $params = array()) {
        parent::print_values($params);

        $email_from = $this->get_config_value("website_email_from");
        $name_from = $this->get_config_value("website_name_from");
        $email_to = $this->app->get_actual_email_to($user_account["user_email"]);
        $name_to = $user_account["login"];
        $subject = $this->title;
        $this->print_values();

        $this->_templates_dir = "newsletter_edit/email";

        $attachment_file = $this->fetch_db_object("File", $this->file_id);
        $attachment_image = $this->fetch_db_object("Image", $this->image_id);

        $this->app->print_db_object_info(
            $this->app->fetch_image_without_content($this->image_id),
            $this->_templates_dir,
            "email.image",
            "_image.html",
            "_image_empty.html"
        );
        $this->app->print_db_object_info(
            $this->app->fetch_file_without_content($this->file_id),
            $this->_templates_dir,
            "email.file_info",
            "_file_info.html",
            "_file_info_empty.html"
        );

        $body = $this->app->print_file("{$this->_templates_dir}/email_sent_to_user.html");

        $email_sender =& $this->app->create_email_sender();
        $email_sender->From = $email_from;
        $email_sender->Sender = $email_from;
        $email_sender->FromName = trim($name_from);
        $email_sender->AddAddress($email_to, trim($name_to));
        $email_sender->AddStringImageAttachment(
            $attachment_image->content,
            "image.jpg",
            "image.jpg",
            "base64",
            "image/jpeg"
        );
        $email_sender->AddAttachment($attachment_file->content, $attachment_file->filename);
        $email_sender->Subject = $subject;
        $email_sender->Body = $body;
        $email_sender->Send();
    }
    
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "list_item") {
            $title_short_len = $this->get_config_value("newsletter_title_short_length");
            $this->app->print_varchar_value(
                "newsletter.title.short",
                get_word_shortened_string(strip_tags($this->title), $title_short_len, "...")
            );
            
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->thumbnail_image_id),
                $this->_templates_dir,
                "newsletter.thumbnail_image",
                "_thumbnail_image.html",
                "_thumbnail_image_empty.html"
            );
            
        }
        
        if ($this->_context == "view" || $this->_context == "edit") {
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->image_id),
                $this->_templates_dir,
                "newsletter.image",
                "_image.html",
                "_image_empty.html"
            );
        }

        $this->app->print_db_object_info(
            $this->app->fetch_file_without_content($this->file_id),
            $this->_templates_dir,
            "newsletter.file_info",
            "_file_info.html",
            "_file_info_empty.html"
        );
    }

}

?>