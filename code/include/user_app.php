<?php

class UserApp extends CustomApp {

    function UserApp($tables) {
        parent::CustomApp("user", $tables);

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

    function on_before_run_action() {
        parent::on_before_run_action();
        
        $this->print_lang_menu();
    }

//
    function pg_index() {
        $templates_dir = "index";
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
        
        $this->send_email_contact_form_processed_to_admin($contact_info);
        
        $this->add_session_status_message(new OkStatusMsg("contact_form_processed"));
        $this->create_self_redirect_response(array("action" => "pg_contact_form"));
    }

    function send_email_contact_form_processed_to_admin($contact_info) {
        $email_from = $contact_info->email;
        $name_from = "{$contact_info->first_name} {$contact_info->last_name}";
        $email_to = $this->get_actual_email_to($this->config->get_value("contact_form_email_to"));
        $name_to = $this->config->get_value("contact_form_name_to");
        $subject = $this->config->get_value("email_contact_form_processed_subject");
        $contact_info->print_values();
        $body = $this->print_file("contact_form/email.html");

        $email_sender = $this->create_email_sender();
        $email_sender->From = $email_from;
        $email_sender->Sender = $email_from;
        $email_sender->FromName = trim($name_from);
        $email_sender->AddAddress($email_to, trim($name_to));
        $email_sender->Subject = $subject;
        $email_sender->Body = $body;
        $email_sender->Send();
    }

}

?>