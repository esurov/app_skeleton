<?php

class CustomPageScript extends PageScript {
    // E-mail message
    var $message;

    var $popup = false;
    var $report = false;

    var $print_lang_menu = true;

    // From App
    var $lang;
    var $messages;

    function CustomPageScript($name) {
        parent::PageScript($name);

        $this->init_lang_dependent_data();
        $this->init_email_message();

        $this->page->assign(array(
            'logo'  => $this->get_message('app_title'),
            'logo2' => $this->get_message('logo2'),
        ));
        $this->app->page =& $this->page;
    }

    function run() {
        $this->print_title("app_head_title", "head_title");
        $this->print_lang_menu();


        $this->popup = intval(param("popup"));
        $this->report = intval(param("report"));

        if (!$this->report) {
            $this->page->assign(array(
                "popup_url_param" => "&amp;popup={$this->popup}",
                "popup_hidden" =>
                    "<input type=\"hidden\" name=\"popup\" value=\"{$this->popup}\">\n",
            ));
            $this->print_action_message();
        }

        parent::run();
    }

    function drop_pager() {
        $this->pager = new Pager($this, 10000, 10000);
    }

    function init_email_message() {
        $templ_dir = 'templates/' . $this->config->value('email_templates_dir');
        $this->message = new Template($templ_dir);
        $dirname = dirname($_SERVER["SCRIPT_NAME"]);
        $dirname = $dirname ? $dirname . '/' : '/';

        $this->message->assign(array(
            'logo' => $this->get_message('app_title'),
            'host' => 'http://' . $_SERVER["HTTP_HOST"],
            'admin_script' => $dirname . 'admin.php',
        ));
    }

    function init_lang_dependent_data() {
        $this->messages =& $this->app->messages;
        $this->lang =& $this->app->lang;
        foreach ($this->messages->params as $key => $value) {
            $this->page->assign(array("str_{$key}" => $value));
        }
        $this->page->assign(array("lang" => $this->lang));
    }

    function print_page() {
        if ($this->report) {
            echo $this->page->parse_file('report.html');
        } else if ($this->popup) {
            echo $this->page->parse_file('popup.html');
        } else {
            $this->print_main_menu();
            echo $this->page->parse_file('page.html');
        }
    }

    function print_main_menu() {
        $menu_actions = $this->config->value("{$this->script_name}_menu_actions");
        if (!is_null($menu_actions)) {
            $menu_items = explode(",", $menu_actions);
            $i = 0;
            foreach ($menu_items as $menu_action) {
                $i++;
                $this->page->assign(array(
                    "caption" => $this->get_message(
                        "{$this->script_name}_menu_item_{$menu_action}"
                    ),
                ));
                if (
                    $menu_action == $this->action ||
                    $menu_action == $this->action . "_" . param("page")
                ) {
                    $this->page->parse_file("_main_menu_item_current.html", "menu_items");
                } else {
                    $this->page->assign(array(
                        "url" => $this->config->value(
                            "{$this->script_name}_menu_action_{$menu_action}"
                        ),
                    ));
                    $this->page->parse_file("_main_menu_item.html", "menu_items");
                }
                if ($i != count($menu_items)) {
                    $this->page->parse_file("_main_menu_item_delimiter.html", "menu_items");
                }
            }
        }
        $this->page->parse_file("_main_menu.html", "main_menu");
    }

    // Should be redefined in child class
    function get_menu_items_info() {
        return array();
    }
    
    function print_lang_menu() {
        if (!$this->print_lang_menu) {
            return;
        }
        $avail_langs = $this->app->get_avail_langs();
        foreach ($avail_langs as $lang) {
            if ($lang == $this->lang) {
                $this->page->assign(array(
                    "current_lang_name" => $this->get_message($lang),
                    "current_lang_image_url" =>
                        $this->config->value("lang_image_current_url_{$lang}"),
                ));
                $this->page->parse_file("_lang_menu_item_current.html", "lang_menu_items");
            } else {
                $this->page->assign(array(
                    "new_lang" => $lang,
                    "new_lang_name" => $this->get_message($lang),
                    "new_lang_image_url" =>
                        $this->config->value("lang_image_url_{$lang}"),
                ));
                $this->page->parse_file("_lang_menu_item.html", "lang_menu_items");
            }
        }
        $this->page->parse_file("_lang_menu.html", "lang_menu");
    }

    function get_pager_custom_url_params() {
        return "&amp;popup={$this->popup}";
    }

    function print_title($title, $varName = "title") {
        $this->page->assign(array('title' => ''));
        $this->print_message($title, $varName);
    }

    function create_view_object_page_title($obj) {
        $resource = $obj->singular_resource_name();
        $resource = "title_view_{$resource}_info";
        $this->page->assign(array(
            'title' => $this->get_message($resource),
        ));
    }

    function create_view_several_objects_page_title($obj) {
        $resource = $obj->plural_resource_name();
        $this->page->assign(array(
            'title' => $this->get_message("title_view_{$resource}"),
        ));
    }

    function create_edit_object_page_title($obj) {
        $resource = $obj->singular_resource_name();
        $resource =
            ($obj->is_definite()) ?
            "title_edit_{$resource}_info" :
            "title_add_{$resource}_info";

        $this->page->assign(array(
            'title' => $this->get_message($resource),
        ));
    }

    /**
     * Prints message, sequence: function parameter, cgi, default.
     */
    function print_action_message($default = '', $passed = null) {
        $message = '';
        if (!is_null($passed)) {
            $message = $passed;
        } else if (param('message')) {
            $message = param('message');
        } else {
            $message = $default;
        }
        $this->print_status($message, 'ok');
    }

    /** Prints special message (ok|err)  */
    function print_status($msg_name, $msg_type = 'ok')
    {
        $msg_text = $this->get_message($msg_name);
        if (!$msg_text) {
            $msg_text = $msg_name;
        }
        $this->page->assign(array(
            'text_of_message' => '',
        ));
        $this->page->parse_text($msg_text, 'text_of_message');
        $this->page->parse_file('status_message.html', $msg_type);
    }

    function status_message($msg_name, $vars = array()) {
        $msg_text = $this->get_message($msg_name);
        $this->page->assign(array(
            'text_of_message' => '',
        ));
        $this->page->assign($vars);

        $this->page->parse_text($msg_text, 'text_of_message');
        return $this->page->parse_file('status_message.html');
    }

    function create_finish_object_action_status($action, $obj) {
        if ($action == "delete") {
            $operation = "deleted";
        } else if ($action == "update") {
            $operation = "updated";
        } else {
            $operation = "added";
        }
        return "{$obj->class_name}_{$operation}";
    }

    function print_finish_object_action_status($action, $obj) {
        $this->print_status(
            $this->create_finish_object_action_status($action, $obj), "ok"
        );
    }

    function finish_object_action(
        $action, $obj, $aspect, $where_str,
        $default_order_by, $show_search_form, $next_action
    ) {
        if (is_null($next_action)) {
            $this->print_finish_object_action_status($action, $obj);

            $this->print_view_several_objects_page(
                $obj, $aspect, $where_str, $default_order_by, $show_search_form
            );
        } else {
            $message = $this->create_finish_object_action_status($action, $obj);
            self_redirect("?action={$next_action}&message={$message}&popup={$this->popup}");
        }
    }

    function finish_add_object(
        $obj, $aspect, $where_str,
        $default_order_by, $show_search_form, $next_action
    ) {
        return $this->finish_object_action(
            "add", $obj, $aspect, $where_str,
            $default_order_by, $show_search_form, $next_action
        );
    }

    function finish_update_object(
        $obj, $aspect, $where_str,
        $default_order_by, $show_search_form, $next_action
    ) {
        return $this->finish_object_action(
            "update", $obj, $aspect, $where_str,
            $default_order_by, $show_search_form, $next_action
        );
    }

    function finish_delete_object(
        $obj, $aspect, $where_str,
        $default_order_by, $show_search_form, $next_action
    ) {
        return $this->finish_object_action(
            "delete", $obj, $aspect, $where_str,
            $default_order_by, $show_search_form, $next_action
        );
    }

    function change_lang() {
        $this->app->set_current_lang(param("new_lang"));
        self_redirect("?action=pg_index");
    }

    function pg_static() {
        $avail_pages = explode(",", $this->config->value("static_pages"));
        $page_name = param("page");
        if (!in_array($page_name, $avail_pages)) {
            $this->pg_access_denied();
        }
        $page_path = "static/{$page_name}.html";
        $localized_page_path = "static/{$page_name}_{$this->app->lang}.html";
        if ($this->page->is_template_exist($localized_page_path)) {
            $page_path = $localized_page_path;
        }
        $this->print_title("page_title_{$page_name}");
        $this->page->parse_file("static/{$page_name}.html", "body");
    }
}

?>
