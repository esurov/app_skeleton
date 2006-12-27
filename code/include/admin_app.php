<?php

class AdminApp extends CustomApp {

    function AdminApp() {
        parent::CustomApp("admin");

        $this->set_current_lang($this->dlang);

        $u = array("valid_users" => array("user"));

        $this->actions = array(
            "pg_static" => $u,
            "get_image" => $u,
            "get_file" => $u,

            "pg_index" => $u,

            "pg_view_news_articles" => $u,
            "pg_edit_news_article" => $u,
            "update_news_article" => $u,
            "delete_news_article" => $u,
        );
    }
//
    function get_user_access_level($user = null) {
        return $this->get_http_auth_user_access_level();
    }

    function run_access_denied_action() {
        $this->create_http_auth_html_document_response($this->get_message("admin_auth_realm"));
    }
//
    function pg_index() {
        $this->print_static_page("index");
    }
//
//    function pg_view_news_articles() {
//        $this->print_many_objects_list_page(array(
//            "obj" => "news_article",
//            "templates_dir" => "news_articles",
//            "default_order_by" => array("created DESC", "id DESC"),
//            "show_filter_form" => true,
//        ));
//    }

    function pg_view_news_articles() {
        $templates_dir = "news_articles";

        $list = $this->create_component(
            "objects_paged_list",
            array(
                "obj" => "news_article",
                "templates_dir" => "{$templates_dir}/news_articles_paged_list",
                "template_var" => "paged_news_articles",
                "default_order_by" => array("created DESC", "id DESC"),
                "show_filter_form" => true,
            )
        );
        $list->print_values();
        
        $this->print_file("{$templates_dir}/body.html");

//
//        $templates_dir = "news_articles";
//
//        $this->print_many_objects_list_page(array(
//            "obj" => "news_article",
//            "templates_dir" => "{$templates_dir}/paged_news_articles",
//            "template_var" => "paged_news_articles",
//            "default_order_by" => array("created DESC", "id DESC"),
//            "show_filter_form" => true,
//        ));
        
//        $this->print_file("{$templates_dir}/body.html");
    }
//////////

    function pg_edit_news_article() {
        $news_article = get_param_value($this->action_params, "news_article", null);
        if (is_null($news_article)) {
            $news_article = $this->read_id_fetch_db_object("news_article");
        }
        $this->print_object_edit_page(array(
            "obj" => $news_article,
            "templates_dir" => "news_article_edit",
        ));
    }

    function update_news_article() {
        $news_article = $this->read_id_fetch_db_object("news_article");
        $news_article_old = $news_article;
        $news_article->read();

        $messages = $news_article->validate($news_article_old);

        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_edit_news_article", array("news_article" => $news_article));
        } else {
            if (was_file_uploaded("image_file")) {
                $news_article->process_image_upload_and_imagemagick_resize(
                    "image_id",
                    "image_file",
                    array(
                        "width" => $this->config->get_value("news_article_image_width"),
                        "height" => $this->config->get_value("news_article_image_height"),
                    )
                );
            }
            if (was_file_uploaded("file")) {
                $news_article->process_file_upload("file_id", "file");
            }

            $news_article->save();
            $this->print_status_message_object_updated($news_article_old);
            $this->create_self_redirect_response(array("action" => "pg_view_news_articles"));
        }
    }

    function delete_news_article() {
        $news_article = $this->read_id_fetch_db_object("news_article");
        $this->delete_object(array(
            "obj" => $news_article,
            "success_url_params" => array("action" => "pg_view_news_articles"),
            "error_url_params" => array("action" => "pg_view_news_articles"),
        ));
    }

}

?>