<?php

class UserApp extends CustomApp {
    function UserApp($tables) {
        parent::CustomApp("user", $tables);

        $this->print_lang_menu = true;

        $e = array("valid_users" => array("everyone"));

        $this->actions = array(
            "change_lang" => $e,
            "pg_static" => $e,
            "get_image" => $e,

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
        $this->print_lang_menu();
        $this->print_recent_news_articles();
        $this->print_static_page("index");
    }

    function print_recent_news_articles() {
        $n_recent_news_articles = $this->config->get_value("recent_news_articles_number");
        $this->print_many_objects_list_page(array(
            "obj_name" => "news_article",
            "templates_dir" => "news_article/recent",
            "template_var" => "recent_news_articles",
            "query_ex" => array(
                "limit" => "0, {$n_recent_news_articles}",
            ),
            "default_order_by" => array("created DESC", "id DESC"),
        ));
    }
//
    function pg_view_news_articles() {
        $this->print_many_objects_list_page(array(
            "obj_name" => "news_article",
            "default_order_by" => array("created DESC", "id DESC"),
            "show_filter_form" => true,
        ));
    }

    function pg_view_news_article() {
        $this->print_object_view_page(array("obj_name" => "news_article"));
    }
//
    function pg_contact_form() {
        $contact = $this->create_db_object("contact");
        $contact->print_form_values();
        $this->print_file("contact/form.html", "body");
    }

    function process_contact_form() {
        $contact = $this->create_db_object("contact");
        $contact->read();
        if (is_value_not_empty($contact->email)) {
            $email_to = $this->config->get_value("contact_form_email_to");
            $name_to = $this->config->get_value("contact_form_name_to");

            $first_name = trim($contact->first_name);
            $last_name = trim($contact->last_name);

            $name_from = trim("{$first_name} {$last_name}");
            $subject = $this->config->get_value("contact_form_subject");

            $contact->print_values();

            $this->email_sender->From = $contact->email;
            $this->email_sender->Sender = $contact->email;
            $this->email_sender->FromName = $name_from;
            $this->email_sender->AddAddress($email_to, $name_to);
            $this->email_sender->Subject = $subject;
            $this->email_sender->Body = $this->print_file("email/contact.html");
            $this->email_sender->Send();
        }
        $this->add_session_status_message(new OkStatusMsg("contact_form_processed"));
        $this->create_self_redirect_response(array("action" => "pg_contact_form"));
    }
}

?>