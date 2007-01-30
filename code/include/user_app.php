<?php

class UserApp extends CustomApp {

    function UserApp() {
        parent::CustomApp("UserApp", "user");

        $e = array("valid_users" => array("guest"));

        $this->actions = array(
            "change_lang" => $e,
            "pg_static" => $e,
            "get_image" => $e,
            "get_file" => $e,

            "pg_index" => $e,

            "pg_news_articles" => $e,
            "pg_news_article_view" => $e,

            "pg_contact_form" => $e,
            "process_contact_form" => $e,
        );
    }
//
    function action_pg_access_denied() {
        $this->create_self_redirect_response();
    }

    function on_before_run_action() {
        parent::on_before_run_action();
        
        $this->print_lang_menu();
    }
//
    function action_pg_index() {
        $templates_dir = "index";

        $news_article = $this->create_db_object("NewsArticleTable");
        $n_recent_news_articles = $this->get_config_value("recent_news_articles_number");
        $recent_news_articles_list = $this->create_object(
            "QueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/recent_news_articles",
                "template_var" => "recent_news_articles",
                "obj" => $news_article,
                "query_ex" => array(
                    "order_by" => "created DESC, id DESC",
                    "limit" => "0, {$n_recent_news_articles}",
                ),
                "context" => "list_item",
            )
        );
        $recent_news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }
//
    function action_pg_news_articles() {
        $templates_dir = "news_articles";
        
        $news_article = $this->create_db_object("NewsArticleTable");
        $news_articles_list = $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/news_articles",
                "template_var" => "news_articles",
                "obj" => $news_article,
                "query_ex" => array(
                    "order_by" => "created DESC, id DESC",
                ),
                "filter_form.visible" => true,
                "context" => "list_item",
            )
        );
        $news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_pg_news_article_view() {
        $templates_dir = "news_article_view";

        $news_article = $this->read_id_fetch_db_object("NewsArticleTable");
        $news_article_view = $this->create_object(
            "ObjectView",
            array(
                "templates_dir" => "{$templates_dir}/news_article_view",
                "template_var" => "news_article_view",
                "obj" => $news_article,
                "context" => "view",
            )
        );
        $news_article_view->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }
//
    function action_pg_contact_form() {
        $templates_dir = "contact_form";

        $contact_info = get_param_value($this->action_params, "contact_info", null);
        if (is_null($contact_info)) {
            $contact_info = $this->create_db_object("ContactInfo");
        }
        $contact_form = $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/contact_info_edit",
                "template_var" => "contact_info_edit",
                "obj" => $contact_info,
            )
        );
        $contact_form->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_process_contact_form() {
        $contact_info = $this->create_db_object("ContactInfo");
        $contact_info_old = $contact_info;
        $contact_info->read();

        $messages = $contact_info->validate($contact_info_old);

        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_contact_form", array("contact_info" => $contact_info));
        } else {
            $this->send_email_contact_form_processed_to_admin($contact_info);
            
            $this->add_session_status_message(new OkStatusMsg("contact_form_processed"));
            $this->create_self_redirect_response(array("action" => "pg_contact_form"));
        }
    }

    function send_email_contact_form_processed_to_admin($contact_info) {
        $email_from = $contact_info->email;
        $name_from = "{$contact_info->first_name} {$contact_info->last_name}";
        $email_to = $this->get_actual_email_to($this->get_config_value("contact_form_email_to"));
        $name_to = $this->get_config_value("contact_form_name_to");
        $subject = $this->get_config_value("email_contact_form_processed_subject");
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