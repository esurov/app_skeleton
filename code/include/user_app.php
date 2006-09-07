<?php

class UserApp extends CustomApp {
    function UserApp($tables) {
        parent::CustomApp("user", $tables);

        $this->print_lang_menu = true;

        $e = array("valid_users" => array("guest"));

        $this->actions = array(
            "change_lang" => $e,
            "pg_static" => $e,
            "get_image" => $e,
            "get_file" => $e,

            "pg_index" => $e,

            "pg_view_news_articles" => $e,
            "pg_view_news_article" => $e,

            "pg_contact_form" => $e,
            "process_contact_form" => $e,
        );
    }
//
    function pg_access_denied() {
        $this->create_self_redirect_response();
    }
//
    function pg_index() {
        $templates_dir = "index";
        $this->print_lang_menu();
        $this->print_recent_news_article_list(
            "{$templates_dir}/recent_news_article_list",
            "recent_news_article_list"
        );
        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function print_recent_news_article_list($templates_dir, $template_var) {
        $n_recent_news_articles = $this->config->get_value("recent_news_articles_number");
        $this->print_many_objects_list(array(
            "obj_name" => "news_article",
            "templates_dir" => $templates_dir,
            "template_var" => $template_var,
            "query_ex" => array(
                "order_by" => "created DESC, id DESC",
                "limit" => "0, {$n_recent_news_articles}",
            ),
        ));
    }
//
    function pg_view_news_articles() {
        $this->print_many_objects_list_page(array(
            "obj_name" => "news_article",
            "templates_dir" => "news_articles",
            "default_order_by" => array("created DESC", "id DESC"),
            "show_filter_form" => true,
        ));
    }

    function pg_view_news_article() {
        $this->print_object_view_page(array(
            "obj_name" => "news_article",
            "templates_dir" => "news_article_view",
        ));
    }
//
    function pg_contact_form() {
        $contact_info = $this->create_db_object("contact_info");
        $contact_info->print_form_values();
        $this->print_file("contact_form/body.html", "body");
    }

    function process_contact_form() {
        $contact_info = $this->create_db_object("contact_info");
        $contact_info->read();
        if (is_value_not_empty($contact_info->email)) {
            $contact_info->print_values();
            $this->send_email(
                $contact_info->email,
                trim("{$contact_info->first_name} {$contact_info->last_name}"),
                $this->config->get_value("contact_form_email_to"),
                $this->config->get_value("contact_form_name_to"),
                $this->config->get_value("email_contact_form_processed_subject"),
                "contact_form/email.html"
            );
        }
        $this->add_session_status_message(new OkStatusMsg("contact_form_processed"));
        $this->create_self_redirect_response(array("action" => "pg_contact_form"));
    }

}

?>