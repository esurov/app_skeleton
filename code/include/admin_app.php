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
            "delete_news_article_image" => $u,
            "delete_news_article_file" => $u,
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
    function action_pg_index() {
        $this->print_static_page("index");
    }
//
    function action_pg_view_news_articles() {
        $templates_dir = "news_articles";

        $news_article = $this->create_db_object("news_article");
        $news_articles_list = $this->create_component(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/news_articles",
                "template_var" => "news_articles",
                "obj" => $news_article,
                "default_order_by" => array("created DESC", "id DESC"),
                "filter_form.visible" => true,
                "context" => "list_item_admin",
            )
        );
        $news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");

    }

    function action_pg_edit_news_article() {
        $news_article = get_param_value($this->action_params, "news_article", null);
        if (is_null($news_article)) {
            $news_article = $this->read_id_fetch_db_object("news_article");
        }
        $this->print_object_edit_page(array(
            "obj" => $news_article,
            "templates_dir" => "news_article_edit",
            "context" => "edit",
        ));
    }

    function action_update_news_article() {
        $news_article = $this->read_id_fetch_db_object("news_article");
        $news_article_old = $news_article;
        $news_article->read();

        $messages = $news_article->validate($news_article_old);

        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_edit_news_article", array("news_article" => $news_article));
        } else {
            $this->process_uploaded_image(
                $news_article,
                "image_id",
                "image_file",
                array(
                    "image_processor.class" => $this->config->get_value("image_processor"),
                    "image_processor.actions" => array(
                        array(
                            "name" => "crop_and_resize",
                            "width" => $this->config->get_value("news_article_image_width"),
                            "height" => $this->config->get_value("news_article_image_height"),
                        ),
                    ),
                    "is_thumbnail" => 0,
                )
            );
            $this->process_uploaded_image(
                $news_article,
                "thumbnail_image_id",
                "image_file",
                array(
                    "image_processor.class" => $this->config->get_value("image_processor"),
                    "image_processor.actions" => array(
                        array(
                            "name" => "crop_and_resize",
                            "width" => $this->config->get_value(
                                "news_article_thumbnail_image_width"
                            ),
                            "height" => $this->config->get_value(
                                "news_article_thumbnail_image_height"
                            ),
                        ),
                        // This is example of second image processor action
                        // Remove if grayscale is not needed (almost always ;) )
                        array(
                            "name" => "convert_to_grayscale",
                        ),
                    ),
                    "is_thumbnail" => 1,
                )
            );

            if (was_file_uploaded("file")) {
                $news_article->process_file_upload("file_id", "file");
            }

            $news_article->save();
            $this->print_status_message_object_updated($news_article_old);
            $this->create_self_redirect_response(array("action" => "pg_view_news_articles"));
        }
    }

    function action_delete_news_article() {
        $news_article = $this->read_id_fetch_db_object("news_article");
        $this->delete_object(array(
            "obj" => $news_article,
            "success_url_params" => array("action" => "pg_view_news_articles"),
            "error_url_params" => array("action" => "pg_view_news_articles"),
        ));
    }

    function action_delete_news_article_image() {
        $news_article = $this->read_id_fetch_db_object("news_article");

        $this->delete_object_image($news_article, "image_id");
        
        $this->add_session_status_message(new OkStatusMsg("news_article_image_deleted"));
        $this->create_self_redirect_response(array(
            "action" => "pg_edit_news_article",
            "news_article_id" => $news_article->id,
        ));
    }

    function action_delete_news_article_file() {
        $news_article = $this->read_id_fetch_db_object("news_article");

        $this->delete_object_file($news_article, "file_id");
        
        $this->add_session_status_message(new OkStatusMsg("news_article_file_deleted"));
        $this->create_self_redirect_response(array(
            "action" => "pg_edit_news_article",
            "news_article_id" => $news_article->id,
        ));
    }

}

?>