<?php

class SampleApp extends CustomApp {

    function SampleApp() {
        parent::CustomApp("SampleApp", "_sample_app");

        $e = array("roles" => array("guest"));

        $this->actions = array(
            "index" => $e,

            "set_cookie" => $e,
            "check_cookie" => $e,
            "get_news_articles_with_new_field_xml" => $e,
/*
            "sample_records_list_in_context1" => $e,
            "sample_records_list_in_context2" => $e,
            "sample_records_list_as_objects" => $e,
*/
            "print_and_save_one_sample2_record" => $e,

            "component_view" => $e,
        );
    }
//
    function get_page_templates_dir() {
        return "templates/__sample";
    }
//
    function action_index() {
        foreach (array_keys($this->actions) as $action) {
            $this->print_varchar_value("action_name", $action);
            $this->print_varchar_value(
                "action_url",
                create_self_url(array("action" => $action))
            );
            $this->print_file("actions_list/list_item.html", "actions_list");
        }
        $this->print_file("actions_list/list_items.html", "body");
    }

    function action_set_cookie() {
        $this->create_self_redirect_response(array("action" => "check_cookie"));
        $this->response->add_cookie(new Cookie("Cookie1", "&%$#@!Value1"));
    }

    function action_check_cookie() {
        v($_COOKIE);
        exit;
    }

    function action_get_news_articles_with_new_field_xml() {
        $news_article =& $this->create_db_object("NewsArticle");
        $news_article->insert_field(array(
            "field" => "new_field",
            "type" => "integer",
            "select" => "RAND(1000) * 1000",
        ));
        $news_articles_list =& $this->create_object(
            "QueryObjectsList",
            array(
                "templates_dir" => "news_article/xml",
                "templates_ext" => "xml",
                "obj" => $news_article,
                "query_ex" => array(
                    "order_by" => "created_date DESC, id DESC",
                ),
            )
        );
        $xml_content = $news_articles_list->print_values();
        $this->create_xml_document_response($xml_content);
    }

//  Context usage example
//  Print list with specific to context1 template variables
/*
    function action_sample_records_list_in_context1() {
        $this->print_many_objects_list_page(array(
            "obj" => "Sample",
            "templates_dir" => "_sample_table/list_context1",
            "context" => "context1",
            "custom_params" => array(
                "param1" => "param1_value",
                "param2" => "param2_value",
            ),
        ));
    }

    function action_sample_records_list_in_context2() {
        $this->print_many_objects_list_page(array(
            "obj" => "Sample",
            "templates_dir" => "_sample_table/list_context2",
            "context" => "context2",
        ));
    }

    function action_sample_records_list_as_objects() {
        $objects = $this->fetch_db_objects_list(
            "Sample",
            array("order_by" => "created DESC")
        );
        $this->print_many_objects_list(array(
            "objects" => $objects,
            "templates_dir" => "_example/list_as_objects",
            "template_var" => "body",
        ));
    }
*/
    function action_print_and_save_one_sample2_record() {
        $obj =& $this->fetch_db_object("Sample2", 1);
        $obj->print_form_values();
        $obj->save();
        $obj->read();
        vx($this->page->_fillings);
    }
//
    function action_component_view() {
        $templates_dir = "sample_component_view";

        $component =& $this->create_object(
            "SampleComponent2",
            array(
                "templates_dir" => "{$templates_dir}/sample_component2",
                "template_var" => "sample_component2",
            )
        );
        $component->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

}

?>