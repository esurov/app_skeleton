<?php

class UserApp extends CustomApp {
    function UserApp($tables) {
        parent::CustomApp("user", $tables);

        $this->print_lang_menu = true;

        $e = array("valid_users" => array("everyone"));

        $this->actions = array(
            "test" => $e,

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
        $this->print_recent_news_articles();
        $this->print_static_page("index");
    }

    function print_recent_news_articles() {
        $n_recent_news_articles = $this->config->get_value("recent_news_articles_number");
        $this->print_many_objects_list_page(array(
            "templates_dir" => "news_article/recent",
            "template_var" => "recent_news_articles",
            "obj_name" => "news_article",
            "default_order_by" => array("created desc", "id desc"),
            "query_ex" => array(
                "limit" => "0, {$n_recent_news_articles}",
            ),
        ));
    }
//
    function pg_view_news_articles() {
        $this->print_many_objects_list_page(array(
            "obj_name" => "news_article",
            "default_order_by" => array("created desc", "id desc"),
            "show_filter_form" => true,
        ));
    }

    function pg_view_news_article() {
        $this->print_object_view_page(array(
            "obj_name" => "news_article",
        ));
    }
//
    function pg_contact_form() {
        $this->page->parse_file(
            "static/_contact_form_notice_{$this->lang}.html",
            "contact_form_notice"
        );
        $this->print_static_page("contact_form");
    }

    function process_contact_form() {
        $email_from = param("email");
        if (trim($email_from) != "") {
            $email_to = $this->config->get_value("contact_form_email_to");
            $name_to = $this->config->get_value("contact_form_name_to");

            $first_name = trim(param("first_name"));
            $last_name = trim(param("last_name"));

            $name_from = trim("{$first_name} {$last_name}");
            $subject = $this->config->get_value("contact_form_subject");

            $this->page->assign(array(
                "first_name" => htmlspecialchars($first_name),
                "last_name" => htmlspecialchars($last_name),
                "email" => htmlspecialchars($email_from),
                "company" => htmlspecialchars(param("company")),
                "address" => htmlspecialchars(param("address")),
                "phone" => htmlspecialchars(param("phone")),
                "fax" => htmlspecialchars(param("fax")),
                "message_text" => convert_lf2br(htmlspecialchars(param("message_text"))),
            ));

            $this->email_sender->From = $email_from;
            $this->email_sender->Sender = $email_from;
            $this->email_sender->FromName = $name_from;
            $this->email_sender->AddAddress($email_to, $name_to);
            $this->email_sender->Subject = $subject;
            $this->email_sender->Body = $this->page->parse_file("email/contact.html");
            $this->email_sender->Send();
        }
        $this->create_self_redirect_response(array(
            "action" => "pg_static",
            "page" => "contact_form_processed",
        ));
    }
}

?>