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

        $this->app->page =& $this->page;
    }

    function run_action() {
        $this->popup = intval(param("popup"));
        $this->report = intval(param("report"));

        if (!$this->report) {
            $this->page->assign(array(
                "popup_url_param" => "&amp;popup={$this->popup}",
                "popup_hidden" =>
                    "<input type=\"hidden\" name=\"popup\" value=\"{$this->popup}\">\n",
            ));
            $this->print_session_status_messages();
        }
        $page_name = trim(if_null(param("page"), ""));
        $this->page->assign(array(
            "action" => $this->action,
            "page" => $page_name,
        ));
        $this->print_page_titles($page_name);

        $this->print_lang_menu();

        parent::run_action();
    }

    function print_page_titles($page_name) {
        if ($page_name == "") {
            $title_resource = "page_title_{$this->action}";
        } else {
            $title_resource = "page_title_{$this->action}_{$page_name}";
        }
        $this->print_title($title_resource);
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

    function print_main_menu($menu_prefix = "", $menu_var = "main_menu") {
        $menu_actions = $this->config->value("{$this->script_name}_menu_{$menu_prefix}actions");
        if (!is_null($menu_actions)) {
            $menu_items = explode(",", $menu_actions);
            $i = 0;
            $this->page->assign(array(
                "menu_items" => "",
            ));
            foreach ($menu_items as $menu_action) {
                $i++;
                $this->page->assign(array(
                    "caption" => $this->get_message(
                        "{$this->script_name}_menu_{$menu_prefix}item_{$menu_action}"
                    ),
                    "url" => $this->config->value(
                        "{$this->script_name}_menu_{$menu_prefix}action_{$menu_action}"
                    ),
                    "marker" => $menu_action,
                ));
                if (
                    $menu_action == $this->action ||
                    $menu_action == $this->action . "_" . param("page")
                ) {
                    $this->page->parse_file("_main_menu_{$menu_prefix}item_current.html", "menu_items");
                } else {
                    $this->page->parse_file("_main_menu_{$menu_prefix}item.html", "menu_items");
                }
                if ($i != count($menu_items)) {
                    $this->page->parse_file("_main_menu_{$menu_prefix}item_delimiter.html", "menu_items");
                }
            }
        }
        $menu_template_name = "_{$menu_var}.html";
        if ($this->page->is_template_exist($menu_template_name)) {
            $this->page->parse_file($menu_template_name, $menu_var);
        }
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

    function create_view_object_page_title($obj) {
        $resource = $obj->singular_resource_name();
        $resource = "page_title_view_{$resource}_info";
        $this->print_title($resource);
    }

    function create_view_several_objects_page_title($obj) {
        $resource = $obj->plural_resource_name();
        $this->print_title("page_title_view_{$resource}");
    }

    function create_edit_object_page_title($obj) {
        $resource = $obj->singular_resource_name();
        $resource =
            ($obj->is_definite()) ?
            "page_title_edit_{$resource}_info" :
            "page_title_add_{$resource}_info";
        $this->print_title($resource);
    }

    function print_session_status_messages() {
        $this->print_status_messages(
            $this->app->get_and_delete_session_status_messages()
        );
    }

    function create_finish_object_action_status_resource($action, $obj) {
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
            $this->create_finish_object_action_status_resource($action, $obj), "ok"
        );
    }

    function finish_object_action(
        $action, $obj, $aspect, $where_str,
        $default_order_by, $show_search_form, $next_action
    ) {
        $resource = $this->create_finish_object_action_status_resource($action, $obj);
        $message = new OA_OkStatusMsg($resource);
        if (is_null($next_action)) {
//            $this->print_status_message($message);
//
//            $this->print_view_several_objects_page(
//                $obj, $aspect, $where_str, $default_order_by, $show_search_form
//            );
        } else {
            $this->app->add_session_status_message($message);
            self_redirect("?action={$next_action}&popup={$this->popup}");
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

    function error_delete_object_after_check_links($obj, $next_action, $messages) {
        foreach ($messages as $message) {
            $this->app->add_session_status_message($message);
        }
        self_redirect("?action={$next_action}&popup={$this->popup}");
    }

    function change_lang() {
        $this->app->set_current_lang(param("new_lang"));
        $cur_action = param("cur_action");
        $cur_page = param("cur_page");
        $url = "";
        if (!is_null($cur_action)) {
            $url .= "?action={$cur_action}";
        }
        if (!is_null($cur_page)) {
            $url .= "&page={$cur_page}";
        }
        self_redirect($url);
    }

    function pg_static() {
        $page_name = trim(if_null(param("page"), ""));
        $this->print_title("page_title_pg_static_{$page_name}");
        $this->print_static_page($page_name);
    }

    function print_static_page($page_name) {
        $page_path = "static/{$page_name}_{$this->app->lang}.html";
        if (!$this->page->is_template_exist($page_path)) {
            $page_path = "static/{$page_name}.html";
            if (!$this->page->is_template_exist($page_path)) {
                $this->page->assign(array(
                    "body" => "",
                ));
                return "";
            }
        }
        return $this->page->parse_file($page_path, "body");
    }

    function print_title($resource) {
        $resource_text = $this->get_message($resource);
        $this->page->assign(array(
            "title" => $resource_text,
            "title_resource" => $resource,
        ));
        $this->print_head_title($resource);
    }

    function print_head_title($resource) {
        $resource_text = if_null(
            $this->get_message("head_{$resource}"),
            $this->get_message($resource)
        );
        $this->page->assign(array(
            "head_title" => $resource_text,
        ));
    }

    function image() {
        $image = $this->app->read_id_fetch_object("image", "", "1");
        if ($image->is_definite()) {
            $content = $image->content;
            $filename = $image->filename;
            $filesize = $image->filesize;
            $type = $image->type;
            $modified = timestamp2unix($image->updated);
        } else {
            $filename = 'no_image.gif';
            $content = file_get_contents("images/{$filename}");
            $filesize = filesize("images/{$filename}");
            $type = 'image/gif';
            $modified = filectime("images/{$filename}");
        }

        header("Content-type: {$type}");
        header("Content-Disposition: attachment; filename={$filename}");
        header("Accept-Ranges: bytes");
        header("Content-Length: {$filesize}");

        $gm_modified = gmdate("D, d M Y H:i:s", $modified);
        header("Last-Modified: {$gm_modified} GMT");

        echo $content;
        exit();
    }
}

?>
