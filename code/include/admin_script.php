<?php

class AdminScript extends CustomPageScript {
    function AdminScript() {
        parent::CustomPageScript("admin");

        $this->app->set_current_lang($this->app->dlang);
        $this->print_lang_menu = false;

        $a = array("valid_users" => array("admin"));

        $this->actions = array(
            "pg_static" => $a,

            "pg_index" => $a,
            "pg_view_article" => $a,
            "pg_edit_article" => $a,
            "update_article" => $a,
            "delete_article" => $a,
        );
    }

    function create_current_user() {
        $this->authorize_admin();
    }

    function get_user_access_level() {
        return "admin";
    }

    function authorize_admin() {
        $this->handleHttpAuth(
            $this->config->value("admin_login"),
            $this->config->value("admin_password"),
            $this->get_message("admin_auth_realm")
        );
    }

    function pg_index() {
        $this->print_static_page("index");
    }

    function pg_view_article() {
        $this->print_view_several_objects_page(
            "article", "", "1", "created desc", true
        );
    }

    function pg_edit_article() {
        $this->pg_edit_object("article", "", "1");
    }

    function update_article() {
        $this->update_object("article", "", "1", "created desc", true, "pg_view_article");
    }

    function delete_article() {
        $this->delete_object("article", "", "1", "created desc", true, "pg_view_article");
    }
}

?>
