<?php

class UserScript extends CustomPageScript {
    function UserScript() {
        parent::CustomPageScript("user");

        $this->print_lang_menu = false;

        $e = array("valid_users" => array("everyone"));

        // Actions:
        $this->actions = array(
            "change_lang" => $e,
            "pg_static" => $e,

            "pg_index" => $e,

            "pg_news" => $e,
            "pg_article" => $e,

            "pg_contact_form" => $e,
            "process_contact_form" => $e,
        );
    }

    function pg_access_denied() {
        self_redirect("?action=pg_index");
    }

    function get_user_access_level() {
        return "everyone";
    }

    function pg_index() {
        $this->print_recent_news();
        $this->print_static_page("index");
    }

    function print_recent_news() {
        $this->page->assign(array("text_article_items" => ""));
        $n_recent = $this->config->value("recent_news_number");
        $article = new Article();
        $res = $article->get_expanded_result(array(
            "order_by" => "created desc",
            "limit" => "0, {$n_recent}",
        ));
        while ($row = $res->fetch()) {
            $article->fetch_row($row);
            $this->page->assign($article->write());
            $this->page->parse_file("article/item_recent.html", "text_article_items");
        }
        $this->page->parse_file("article/view_all_recent.html", "recent_news");
    }

    function pg_news() {
        $this->print_view_several_objects_page(
            "article", "", "1", "created desc", true, "news"
        );
        $this->page->parse_file("article/page.html", "body");
    }

    function pg_article() {
        $article = $this->app->read_id_fetch_object("article", "", "1");
        if (!$article->is_definite()) {
            self_redirect("?action=pg_news");
        }
        $this->print_view_object_page($article, "");
    }

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
            $email_to = $this->config->value("contact_form_email_to");
            $name_to = $this->config->value("contact_form_name_to");

            $first_name = trim(param("first_name"));
            $last_name = trim(param("last_name"));

            $name_from = trim("{$first_name} {$last_name}");
            $subject = $this->config->value("contact_form_subject");

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

            $mailer = new PHPMailer();
            $mailer->IsSendmail();
            $mailer->IsHTML(true);
            $mailer->From = $email_from;
            $mailer->Sender = $email_from;
            $mailer->FromName = $name_from;
            $mailer->AddAddress($email_to, $name_to);
            $mailer->Subject = $subject;
            $mailer->Body = $this->page->parse_file("email/contact.html");
            $mailer->Send();
        }
        self_redirect("?action=pg_static&page=contact_form_processed");
    }
}

?>