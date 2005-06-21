<?php

class TestApp extends CustomApp {
    function TestApp($tables) {
        parent::CustomApp("test", $tables);

        $e = array("valid_users" => array("everyone"));

        $this->actions = array(
            "pg_index" => $e,

            "set_cookie" => $e,
            "check_cookie" => $e,
            "get_news_articles_xml" => $e,
        );
    }
//
    function pg_index() {
        $t = new Example();
//        $t->store();
        $query = $t->get_select_query();
var_dump($query);
        $this->db->run_select_query($query);

        $t->run_expanded_select_query(array(
            "order_by" => "id ASC",
        ));
        exit;
    }

    function set_cookie() {
        $this->create_self_redirect_response(array(
            "action" => "check_cookie",
        ));

        $this->response->add_cookie(new Cookie("Cookie1", "&%$#@!Value1"));
    }

    function check_cookie() {
        var_dump($_COOKIE);
        exit;
    }

    function get_news_articles_xml() {
        $xml_page = $this->print_many_objects_list(array(
            "templates_dir" => "news_article/xml",
            "templates_ext" => "xml",
            "obj_name" => "news_article",
            "default_order_by" => array("created desc", "id desc"),
        ));
        $this->create_xml_page_response($xml_page);
    }
}

?>