<?php

class UserScript extends CustomPageScript {
    var $user = null;

    function UserScript() {
        parent::CustomPageScript("user");

        $e = array("valid_users" => array("everyone"));

        // Actions:
        $this->actions = array(
            "change_lang" => $e,
            "pg_static" => $e,

            "pg_index" => $e,
            "pg_news" => $e,
            "pg_article" => $e,
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

    function process_contact_form() {
        $fromEmail = param("email");
        if (trim($fromEmail) != "") {
            $toEmail = $this->config->value("contact_form_email_to");
            $toName = $this->config->value("contact_form_from");
            $fromName = param("first_name") . " " . param("last_name");
            $subj = $this->config->value("contact_form_subj");

            $f = params();
            //insert additional non-string params here (get them from param($name))
            $this->message->assign($f);

            pipe_sendmail(array(
                "to"        => $toEmail,
                "to_name"   => $toName,
                "from"      => $fromEmail,
                "from_name" => $fromName,
                "subj"      => $subj,
                "text"      => $this->message->parse_file("contact.msg"),
            ), 0);
        }
        $this->print_static_page("contact_form_processed");
    }
}

?>