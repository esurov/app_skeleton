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

            "pg_view_examples_in_context1" => $e,
            "pg_view_examples_in_context2" => $e,
            "pg_view_all_examples_as_objects" => $e,

            "print_one_example" => $e,
        );
    }
//
    function pg_index() {
        foreach (array_keys($this->actions) as $action) {
            $this->page->assign(array(
                "action_name" => get_html_safe_string($action),
                "action_suburl" => "?action=" . urlencode($action),
            ));
            $this->page->parse_file("actions_list/list_item.html", "actions_list");
        }
        $this->page->parse_file("actions_list/list_items.html", "body");
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
        $xml_content = $this->print_many_objects_list(array(
            "obj_name" => "news_article",
            "templates_dir" => "news_article/xml",
            "templates_ext" => "xml",
            "query_ex" => array(
                "order_by" => "created DESC, id DESC"
            ),
        ));
        $this->create_xml_document_response($xml_content);
    }

//  Context usage example
//  Print list with specific to context1 template variables
    function pg_view_examples_in_context1() {
        $this->print_many_objects_list_page(array(
            "obj_name" => "_example",
            "templates_dir" => "_example/list_context1",
            "context" => "context1",
            "custom_params" => array(
                "param1" => "param1_value",
                "param2" => "param2_value",
            ),
        ));
    }

    function pg_view_examples_in_context2() {
        $this->print_many_objects_list_page(array(
            "obj_name" => "_example",
            "templates_dir" => "_example/list_context2",
            "context" => "context2",
        ));
    }

    function pg_view_all_examples_as_objects() {
        $examples = $this->fetch_db_objects_list("_example", array(
            "order_by" => "created DESC",
        ));
        $this->print_many_objects_list(array(
            "obj_name" => "_example",
            "templates_dir" => "_example/list_as_objects",
            "template_var" => "body",
        ));
    }

    function print_one_example() {
        $example = $this->fetch_db_object("_example", 1);
        v($this->get_app_currency_with_sign_value($example->field_currency + 12345678.09));
        v($example->print_values());
        $example->field_currency = 99999999.99;
        $example->save();
        exit;
    }
}

?>