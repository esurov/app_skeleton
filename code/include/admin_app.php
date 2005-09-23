<?php

class AdminApp extends CustomApp {
    function AdminApp($tables) {
        parent::CustomApp("admin", $tables);

        $this->set_current_lang($this->dlang);

        $u = array("valid_users" => array("user"));

        $this->actions = array(
            "pg_static" => $u,
            "get_image" => $u,

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
    function pg_view_news_articles() {
        $this->print_many_objects_list_page(array(
            "obj_name" => "news_article",
            "default_order_by" => array("created DESC", "id DESC"),
            "show_filter_form" => true,
        ));
    }

    function pg_edit_news_article() {
        $news_article = get_param_value($this->action_params, "news_article", null);
        $this->print_object_edit_page(array(
            "obj" => $news_article,
            "obj_name" => "news_article",
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
            if (Image::was_uploaded()) {
                $news_article->process_image_upload_and_imagemagick_resize();
            }
            $news_article->save();
            $this->print_status_message_object_updated($news_article_old);
            $this->create_self_redirect_response(array("action" => "pg_view_news_articles"));
        }
    }

    function delete_news_article() {
        $this->delete_object(array(
            "obj_name" => "news_article",
            "success_url_params" => array("action" => "pg_view_news_articles"),
            "error_url_params" => array("action" => "pg_view_news_articles"),
        ));
    }
}

?>