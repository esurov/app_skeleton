<?php

class SetupScript extends CustomPageScript {
    function SetupScript() {
        parent::CustomPageScript("setup");

        $this->app->set_current_lang($this->app->dlang);
        $this->print_lang_menu = false;

        $a = array("valid_users" => array("admin"));

        $this->actions = array(
            "pg_index" => $a,
            "create_tables" => $a,
            "delete_tables" => $a,
            "update_tables" => $a,
            "insert_test_data" => $a,
        );
    }

    /** Return user access level (string) for selecting allowed actions.*/
    function get_user_access_level() {
        $this->authorize_admin();
        return "admin";
    }

    function authorize_admin() {
        $this->handleHttpAuth(
            $this->config->value("admin_login"),
            $this->config->value("admin_password"),
            $this->get_message("setup_auth_realm"),
            $this->get_message("setup_auth_access_denied_page")
        );
    }

    function pg_index() {
        $this->print_index_page();
    }

    function print_index_page() {
        $this->page->assign(array(
            "title" => $this->get_message("setup_area"),
        ));
        $this->page->parse_file("index.html", "body");
    }

    /** Create all MySQL database tables. */
    function create_tables() {
        foreach($this->app->tables as $name => $class_name) {
            $obj = new $class_name;  // !!!
            $obj->create_table();
        }

        $this->page->assign(array(
            "ok" => $this->get_message("tables_created"),
        ));
        $this->print_index_page();
    }

    /** Create all MySQL database tables. */
    function update_tables() {
        foreach($this->app->tables as $name => $class_name) {
            $obj = new $class_name;  // note: variable class
            $obj->update_table();
        }

        $this->page->assign(array(
            "ok" => $this->get_message("tables_updated"),
        ));
        $this->print_index_page();
    }

    /** Delete all MySQL database tables. */
    function delete_tables() {
        foreach($this->app->tables as $name => $class_name) {
            $obj = new $class_name;  // !!!
            $obj->delete_table();
        }

        $this->page->assign(array(
            "ok" => $this->get_message("tables_deleted"),
        ));
        $this->print_index_page();
    }

    function insert_test_data() {
        $this->insert_articles();

        $this->page->assign(array(
            "ok" => $this->get_message("test_data_inserted"),
        ));
        $this->print_index_page();
    }

    function insert_articles() {
        $article = new Article();
        $article->created = "2004-02-28";
        $article->title_it = "Italian title 1";
        $article->body_it = "Italian body 1";
        $article->title_en = "Ask Joel about offshoring/outsourcing";
        $article->body_en =
            "<a href=\"http://discuss.fogcreek.com/newyork/default.asp?cmd=show&" .
            "ixPost=2160&ixReplies=17\">This thread</a> in Ask Joel about offshoring/" .
            "outsourcing is much better than anything I could have written on the" .
            " subject myself. Ken sets up the strawman; eloquent readers from around " . 
            "the globe tear it down.";
        $article->store();

        $article->created = "2004-02-27";
        $article->title_it = "Italian title 2";
        $article->body_it = "Italian body 2";
        $article->title_en = "Excellent stuff going down on the Ask Joel forum";
        $article->body_en =
            "I hope you're not all missing the excellent stuff going down on the " .
            "<a href=\"http://discuss.fogcreek.com/newyork/default.asp\">Ask Joel</a> forum.<br><br>" .
            "On Apress: \"And although they would not put a doggie " .
            "on the cover of my book as I requested, because a certain other " .
            "book publisher threatens to sue his competitors when they put " .
            "anything animal like within 90 feet of their covers, their " .
            "graphic designer worked overtime to create underground cover " .
            "art called User Interface Design for Doggies complete with three " .
            "golden retrievers, which they framed and sent to me.\"";
        $article->store();
    }
}

?>
