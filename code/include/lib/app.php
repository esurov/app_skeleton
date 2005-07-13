<?php

class App {
    var $app_name;

    // Html page template
    var $page;
    var $html_charset;

    // E-mail messages sender
    var $email_sender;

    var $messages;
    var $actions;

    var $action;
    var $action_params;
    var $page_template_name;
    var $response;

    var $config;
    var $log;

    var $db;
    var $tables;

    var $print_lang_menu;

    var $lang;
    var $dlang;
    var $avail_langs;

    function App($app_name, $tables) {
        $this->app_name = $app_name;
        $this->tables = $tables;
        $this->response = null;

        $this->print_lang_menu = false;

        $this->create_config();
        $this->create_logger();
        $this->create_db();

        $this->create_page_template();
        $this->html_charset = $this->config->get_value("html_charset");
        $this->page->assign("html_charset", $this->html_charset);

        $this->create_pager();

        $this->avail_langs = $this->get_avail_langs();
        $this->dlang = $this->config->get_value("default_language");
        $this->lang = $this->get_current_lang();

        $this->messages = new Config();
        $this->messages->read("lang/default.txt");
        $this->messages->read("lang/{$this->lang}.txt");

        $this->init_lang_dependent_data();
        $this->create_email_sender();

        $action_params = array();
        
        // One action defined, but nobody can access it:
        $actions = array(
            "pg_index" => array(
                "valid_users" => array(),
            ),
        );

        $this->log->write("App", "App '{$this->app_name}' started", 3);
    }
//
    function create_config() {
        $this->config = new Config();
        $this->config->read("config/app.cfg");
    }

    function create_logger() {
        $this->log = new Logger();
        $this->log->set_debug_level($this->config->get_value("log_debug_level", 1));
        $this->log->set_max_file_size($this->config->get_value("log_max_file_size"));
        $this->log->set_truncate_always($this->config->get_value("log_truncate_always", 0));
    }

    function create_db() {
        $sql_config = new Config();
        $sql_config->read("config/sql.cfg");

        $sql_params = array(
            "host"     => $sql_config->get_value("host"),
            "database" => $sql_config->get_value("database"),
            "username" => $sql_config->get_value("username"),
            "password" => $sql_config->get_value("password"),
            "table_prefix" => $sql_config->get_value("table_prefix"),
        );
        $this->db = new Db($sql_params, $this->log);
    }

    function create_page_template() {
        $print_template_name = $this->config->get_value("print_template_name");
        $this->page = new Template("templates/{$this->app_name}", $print_template_name);
        $this->page_template_name = "page.html";
    }

    function create_pager() {
        $n_rows_per_page = $this->config->get_value("{$this->app_name}_rows_per_page", 20);
        $this->pager = new Pager($this, $n_rows_per_page);
    }

    function create_email_sender() {
        $this->email_sender = new PHPMailer();
        $this->email_sender->IsSendmail();
        $this->email_sender->IsHTML(true);
        $this->email_sender->CharSet = $this->html_charset;
    }

    function init_lang_dependent_data() {
        foreach ($this->messages->params as $key => $value) {
            $this->page->assign("str_{$key}", $value);
        }
        $this->page->assign("lang", $this->lang);
    }
//
    function run() {
        $this->create_current_user();
        $this->create_action_name();

        // Ensure that current user is allowed to run this action
        // Check user permission level
        $user_level = $this->get_user_access_level();
        $valid_levels = $this->actions[$this->action]["valid_users"];

        if (in_array($user_level, $valid_levels)) {
            $this->run_action();
        } else {
            $this->log->write(
                "App",
                "User level '{$user_level}' is denied to run action '{$this->action}'",
                1
            );
            $this->run_access_denied_action();
        }

        if (is_null($this->response)) {
            $this->create_html_page_response();
        }
        $this->response->send();
    }
    
    function create_current_user() {
    }

    function create_action_name() {
        $action_name = trim(param("action"));
        $this->log->write("App", "Validating action '{$action_name}'", 3);

        // Ensure that action is valid:
        if (!isset($this->actions[$action_name])) {
            $action_name = $this->get_default_action_name();
            $this->log->write("App", "Invalid action! Will run action '{$action_name}'", 1);
            if (!isset($this->actions[$action_name])) {
                $this->log->write("App", "Action '{$action_name}' is not valid!", 1);
                die();
            }
        }
        $this->action = $action_name;
    }

    function get_default_action_name() {
        return "pg_index";
    }

    function get_user_access_level($user = null) {
        // Return user access level (string) for selecting allowed actions
        // for previously created user by function create_current_user()
        return "everyone";
    }

    function run_action($action_name = null, $action_params = array()) {
        // Run action and return its response
        if (!is_null($action_name)) {
            $this->action = $action_name;
        }
        $this->action_params = $action_params;

        $page_name = trim(param("page"));
        $this->page->assign(array(
            "action" => $this->action,
            "page" => $page_name,
        ));
        $this->print_page_titles($page_name);
        
        $this->log->write("App", "Running action '{$this->action}'", 3);
        $this->{$this->action}();  // NB! Variable function
    }

    function run_access_denied_action() {
        $this->create_access_denied_html_page_response();
    }
//
    function get_http_auth_user_access_level() {
        $login = $this->config->get_value("admin_login");
        $password = $this->config->get_value("admin_password");
        return ($this->is_valid_http_auth_user($login, $password)) ? "user" : "everyone";
    }

    function is_valid_http_auth_user($login, $password) {
        return
            (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) &&
            $_SERVER["PHP_AUTH_USER"] == $login &&
            $_SERVER["PHP_AUTH_PW"] == $password;
    }
//
    function create_html_page_response() {
        $page_response_body = $this->create_html_page_response_body();
        $charset = $this->config->get_value("html_pages_charset");
        $this->response = new HtmlPageResponse($page_response_body, $charset);
    }

    function create_html_page_response_body() {
        return $this->page->parse_file($this->page_template_name);
    }

    function create_xml_page_response($body) {
        $this->response = new XmlPageResponse($body);
    }

    function create_access_denied_html_page_response() {
        $this->page_template_name = "access_denied.html";
        $this->create_html_page_response();
    }

    function create_http_auth_html_page_response($realm) {
        $this->create_access_denied_html_page_response();
        $this->response->push_headers(array(
            new HttpHeader("WWW-Authenticate", "Basic realm=\"{$realm}\""),
            new HttpHeader("HTTP/1.0 401 Unauthorized"),
        ));
    }

    function create_redirect_response($url) {
        $this->response = new RedirectResponse($url);
        $this->log->write("App", "Redirect to {$url}", 3);
    }

    function create_self_redirect_response($suburl_params = array()) {
        $extra_suburl_params = $this->get_app_extra_suburl_params();
        $self_url = create_self_url(
            $suburl_params + $extra_suburl_params
        );
        $this->create_redirect_response($self_url);
    }

    function create_self_full_redirect_response(
        $suburl_params = array(),
        $protocol = "http"
    ) {
        $extra_suburl_params = $this->get_app_extra_suburl_params();
        $self_full_url = create_self_full_url(
            $suburl_params + $extra_suburl_params,
            $protocol
        );
        $this->create_redirect_response($self_full_url);
    }

    function get_app_extra_suburl_params() {
        return array();
    }
//
    function create_db_object($obj_name) {
        if (!isset($this->tables[$obj_name])) {
            $this->log->write(
                "App",
                "Cannot find and instantiate db_object child class for '{$obj_name}'!"
            );
            return null;
        }
        $obj_class_name = $this->tables[$obj_name];
        return new $obj_class_name();
    }

    function fetch_db_object(
        $obj_name,
        $id,
        $where_str = "1",
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        $obj = $this->create_db_object($obj_name);
        if ($id != 0) {
            $obj->fetch(
                "{$obj_name}.id = {$id} AND {$where_str}",
                $field_names_to_select,
                $field_names_to_not_select
            );
        }
        return $obj;
    }

    function fetch_db_objects_list($obj_name, $query_ex) {
        $obj = $this->create_db_object($obj_name);
        $res = $obj->run_expanded_select_query($query_ex);
        $objects = array();
        while ($row = $res->fetch()) {
            $obj->fetch_row($row);
            $objects[] = $obj;
        }
        return $objects;
    }

    function read_id_fetch_db_object(
        $obj_name,
        $where_str = "1",
        $id_param_name = null
    ) {
        // Create new object, read it's PRIMARY KEY from CGI
        // (using CGI variable with name $id_param_name),
        // then fetch object from database table
        if (is_null($id_param_name)) {
            $id_param_name = "{$obj_name}_id";
        }
        $pr_key_value = intval(param($id_param_name));
        return $this->fetch_db_object($obj_name, $pr_key_value, $where_str);
    }
//
    function get_app_datetime_format() {
        return $this->config->get_value("app_datetime_format");
    }

    function get_app_date_format() {
        return $this->config->get_value("app_date_format");
    }

    function get_app_time_format() {
        return $this->config->get_value("app_time_format");
    }

    function get_db_datetime_format() {
        return $this->config->get_value("db_datetime_format");
    }

    function get_db_date_format() {
        return $this->config->get_value("db_date_format");
    }

    function get_db_time_format() {
        return $this->config->get_value("db_time_format");
    }
//
    function get_app_datetime($db_datetime, $date_if_unknown = "") {
        $date_parts = parse_date_by_format(
            $this->get_db_datetime_format(), $db_datetime
        );
        return create_date_by_format(
            $this->get_app_datetime_format(), $date_parts, $date_if_unknown
        );
    }

    function get_app_date($db_date, $date_if_unknown = "") {
        $date_parts = parse_date_by_format(
            $this->get_db_date_format(), $db_date
        );
        return create_date_by_format(
            $this->get_app_date_format(), $date_parts, $date_if_unknown
        );
    }

    function get_app_time($db_time, $date_if_unknown = "") {
        $date_parts = parse_date_by_format(
            $this->get_db_time_format(), $db_time
        );
        return create_date_by_format(
            $this->get_app_time_format(), $date_parts, $date_if_unknown
        );
    }

    function get_db_datetime($app_datetime, $date_if_unknown = "0000-00-00 00:00:00") {
        $date_parts = parse_date_by_format(
            $this->get_app_datetime_format(), $app_datetime
        );
        return create_date_by_format(
            $this->get_db_datetime_format(), $date_parts, $date_if_unknown
        );
    }

    function get_db_date($app_date, $date_if_unknown = "0000-00-00") {
        $date_parts = parse_date_by_format(
            $this->get_app_date_format(), $app_date
        );
        return create_date_by_format(
            $this->get_db_date_format(), $date_parts, $date_if_unknown
        );
    }

    function get_db_time($app_time, $date_if_unknown = "00:00:00") {
        $date_parts = parse_date_by_format(
            $this->get_app_time_format(), $app_time
        );
        return create_date_by_format(
            $this->get_db_time_format(), $date_parts, $date_if_unknown
        );
    }
//    
    function get_db_now_datetime() {
        $date_parts = get_date_parts_from_timestamp(time());
        return create_date_by_format(
            $this->get_db_datetime_format(), $date_parts, ""
        );
    }

    function get_db_now_date() {
        $date_parts = get_date_parts_from_timestamp(time());
        return create_date_by_format(
            $this->get_db_date_format(), $date_parts, ""
        );
    }
//
    function get_timestamp_from_db_datetime($db_datetime) {
        return get_timestamp_from_date_parts(
            parse_date_by_format(
                $this->get_db_datetime_format(), $db_datetime
            )
        );
    }
//
    function get_timestamp_from_db_date($db_date) {
        return get_timestamp_from_date_parts(
            parse_date_by_format(
                $this->get_db_date_format(), $db_date
            )
        );
    }
//
    function get_app_integer_value($php_integer_value) {
        return format_integer_value($php_integer_value, ",");
    }

    function get_php_integer_value($app_integer_value) {
        $result = str_replace(",", "", $app_integer_value);
        return intval($result);
    }

    function get_app_double_value($php_double_value, $decimals) {
        return format_double_value($php_double_value, $decimals, ".", ",");
    }

    function get_php_double_value($app_double_value) {
        $result = str_replace(",", "", $app_double_value);
        return doubleval($result);
    }

    function get_app_currency_value($php_double_value, $decimals) {
        return $this->get_app_double_value($php_double_value, $decimals);
    }

    function get_app_currency_with_sign_value(
        $php_double_value, $decimals = 2, $currency_sign = null, $sign_at_start = null
    ) {
        if (is_null($currency_sign)) {
            $currency_sign = $this->get_currency_sign();
        }
        if (is_null($sign_at_start)) {
            $sign_at_start = $this->is_currency_sign_at_start();
        }
        $formatted_currency_value = $this->get_app_currency_value($php_double_value, $decimals);
        return ($sign_at_start) ?
            "{$currency_sign}{$formatted_currency_value}" :
            "{$formatted_currency_value}{$currency_sign}";
    }

    function get_currency_sign() {
        return "\xE2\x82\xAC ";
    }

    function is_currency_sign_at_start() {
        return true;
    }
//
    function get_message($name) {
        return $this->messages->get_value($name);
    }

    function get_current_lang() {
        $cur_lang = Session::get_param("current_language");
        if (!$this->is_valid_lang($cur_lang)) {
            $cur_lang = $this->config->get_value("default_language");
        }
        return $cur_lang;
    }

    function get_avail_langs() {
        return explode(",", $this->config->get_value("languages"));
    }

    function is_valid_lang($lang) {
        if (is_null($lang) || !in_array($lang, $this->avail_langs)) {
            return false;
        } else {
            return true;
        }
    }

    function set_current_lang($new_lang) {
        if ($this->is_valid_lang($new_lang)) {
            Session::set_param("current_language", $new_lang);
        }
    }

//  Common actions
    function change_lang() {
        $this->set_current_lang(param("new_lang"));
        $cur_action = param("cur_action");
        $cur_page = param("cur_page");
        $url = "";
        if (!is_null($cur_action)) {
            $url .= "?action={$cur_action}";
        }
        if (!is_null($cur_page)) {
            $url .= "&page={$cur_page}";
        }
        $this->create_redirect_response($url);
    }

    function pg_static() {
        $page_name = trim(param("page"));
        $this->print_page_title("page_title_pg_static_{$page_name}");
        $this->print_static_page($page_name);
    }

//  Page contruction helper functions
    function print_static_page($page_name) {
        $page_path = "static/{$page_name}_{$this->lang}.html";
        if (!$this->page->is_template_exist($page_path)) {
            $page_path = "static/{$page_name}.html";
        }
        return $this->page->parse_file_if_exists($page_path, "body");
    }

    function print_menu($menu_prefix = "", $menu_var = "menu") {
        $menu_actions = $this->config->get_value("{$this->app_name}_menu_{$menu_prefix}actions");
        if (is_null($menu_actions)) {
            $menu_actions = $this->config->get_value("{$this->app_name}_menu_actions");
        }
        if (!is_null($menu_actions)) {
            $menu_items = explode(",", $menu_actions);

            $i = 0;
            $this->page->assign("menu_items", "");

            foreach ($menu_items as $menu_action) {
                $i++;
                $caption = $this->get_message(
                    "{$this->app_name}_menu_{$menu_prefix}item_{$menu_action}"
                );
                if (is_null($caption)) {
                    $caption = $this->get_message(
                        "{$this->app_name}_menu_item_{$menu_action}"
                    );
                }

                $url = $this->config->get_value(
                    "{$this->app_name}_menu_{$menu_prefix}action_{$menu_action}"
                );
                if (is_null($url)) {
                    $url = $this->config->get_value(
                        "{$this->app_name}_menu_action_{$menu_action}"
                    );
                }

                $this->page->assign(array(
                    "caption" => $caption,
                    "url" => $url,
                    "marker" => $menu_action,
                ));

                if (
                    $menu_action == $this->action ||
                    $menu_action == $this->action . "_" . param("page")
                ) {
                    $this->page->parse_file("_menu_{$menu_prefix}item_current.html", "menu_items");
                } else {
                    $this->page->parse_file("_menu_{$menu_prefix}item.html", "menu_items");
                }
                if ($i != count($menu_items)) {
                    $this->page->parse_file("_menu_{$menu_prefix}item_delimiter.html", "menu_items");
                }
            }
        }
        
        $this->page->parse_file_if_exists("_{$menu_var}.html", $menu_var);
    }

    function print_lang_menu() {
        if (!$this->print_lang_menu) {
            return;
        }
        $avail_langs = $this->get_avail_langs();
        $this->page->assign("lang_menu_items", "");
        foreach ($avail_langs as $lang) {
            if ($lang == $this->lang) {
                $this->page->assign(array(
                    "current_lang_name" => $this->get_message($lang),
                    "current_lang_image_url" =>
                        $this->config->get_value("lang_image_current_url_{$lang}"),
                ));
                $this->page->parse_file("_lang_menu_item_current.html", "lang_menu_items");
            } else {
                $this->page->assign(array(
                    "new_lang" => $lang,
                    "new_lang_name" => $this->get_message($lang),
                    "new_lang_image_url" =>
                        $this->config->get_value("lang_image_url_{$lang}"),
                ));
                $this->page->parse_file("_lang_menu_item.html", "lang_menu_items");
            }
        }
        $this->page->parse_file_new("_lang_menu.html", "lang_menu");
    }

//  Object functions
    function print_many_objects_list_page($params = array()) {
        $obj_name = get_param_value($params, "obj_name", null);
        if (is_null($obj_name)) {
            die("No obj_name in print_many_objects_list_page()");    
        }
        $obj = $this->create_db_object($obj_name);

        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $templates_ext = get_param_value($params, "templates_ext", "html");
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", "body");
        $query = get_param_value($params, "query", $obj->get_select_query());
        $query_ex = get_param_value($params, "query_ex", array());
        $default_order_by = get_param_value($params, "default_order_by", "id asc");
        $show_filter_form = get_param_value($params, "show_filter_form", false);
        $custom_params = get_param_value($params, "custom_params", array());

        $query->expand($query_ex);

        // Read filtering (WHERE) and ordering (ORDER_BY) conditions:
        $obj->read_filters();
        list($actual_where_sql, $actual_having_sql) = $obj->get_filter_sql();
        $actual_where_params = $obj->get_filters_params();

        list($actual_order_by_sql, $actual_order_by_param) =
            $obj->read_order_by($default_order_by);
        // Apply filtering and ordering conditions to query:
        $query->expand(array(
            "where" => $actual_where_sql,
            "order_by" => $actual_order_by_sql,
            "having" => $actual_having_sql,
        ));

        // Make sub-URLs with all necessary parameters stored:
        $action_param = array("action" => $this->action);
        $extra_suburl_params = $this->get_app_extra_suburl_params();

        $action_suburl = create_html_suburl(
            $action_param +
            $extra_suburl_params
        );
               
        $action_where_suburl = create_html_suburl(
            $action_param +
            $actual_where_params +
            $extra_suburl_params
        );

        $action_where_order_by_suburl = create_html_suburl(
            $action_param +
            $actual_where_params +
            $actual_order_by_param +
            $extra_suburl_params
        );

        $this->page->assign(array(
            "action_suburl" => "?{$action_suburl}",
            "action_where_suburl" => "?{$action_where_suburl}",
            "action_where_order_by_suburl" => "?{$action_where_order_by_suburl}",
        ));

        if ($show_filter_form) {
            $this->page->assign($actual_where_params);
            $obj->print_filter_form_values();
            $this->page->parse_file_new(
                "{$templates_dir}/filter_form.{$templates_ext}", "{$obj_name}_filter_form"
            );
        }

        $n = $this->db->get_select_query_num_rows($query);

        if ($n == 0) {
            $objects = array(); // use empty objects list
        } else {
            $this->pager->set_total_rows($n);
            $this->pager->read();
            if ($query->limit == "") {
                $query->expand(array(
                    "limit" => $this->pager->get_limit_clause(),
                ));
            }
            $objects = null; // use constructed query to get objects list
        }

        $this->print_many_objects_list(array(
            "obj_name" => $obj_name,
            "query" => $query,
            "templates_dir" => $templates_dir,
            "templates_ext" => $templates_ext,
            "template_var" => "{$obj_name}_list",
            "context" => $context,
            "objects" => $objects,
            "custom_params" => $custom_params,
        ));

        if ($n > 0) {
            $pager_suburl = "?{$action_where_order_by_suburl}";
            $this->page->assign(array(
                "simple_nav_str" => $this->pager->get_simple_nav_str($pager_suburl),
                "nav_str" => $this->pager->get_pages_nav_str($pager_suburl),
                "total" => $obj->get_quantity_str($n),
            ));
        }

        return $this->page->parse_file("{$templates_dir}/list.{$templates_ext}", $template_var);
    }
//
    function print_many_objects_list($params) {
        $obj_name = get_param_value($params, "obj_name", null);
        if (is_null($obj_name)) {
            die("No obj_name in print_many_objects_list()");
        }
        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $templates_ext = get_param_value($params, "templates_ext", "html");
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", null);
        $custom_params = get_param_value($params, "custom_params", array());

        $objects = get_param_value($params, "objects", null);
        $objects_passed = !is_null($objects);

        if ($objects_passed) {
            $n = count($objects);
        } else {
            $obj = $this->create_db_object($obj_name);

            $query = get_param_value($params, "query", $obj->get_select_query());
            $query_ex = get_param_value($params, "query_ex", array());
            
            $query->expand($query_ex);
            $res = $this->db->run_select_query($query);
            $n = $res->get_num_rows();
        }

        $no_items_template_name = "{$templates_dir}/list_no_items.{$templates_ext}";
        if ($n == 0 && $this->page->is_template_exist($no_items_template_name)) {
            $this->page->parse_file_new($no_items_template_name, $template_var);
        } else {
            $this->page->assign("{$obj_name}_items", "");

            for ($i = 0; $i < $n; $i++) {
                if ($objects_passed) {
                    $row = array();
                    $obj = $objects[$i];
                } else {
                    $row = $res->fetch();
                    $obj->fetch_row($row);
                }

                $parity = $i % 2;
                $obj->print_values(array(
                     "templates_dir" => $templates_dir,
                     "context" => $context,
                     "row" => $row,
                     "row_number" => $i,
                     "row_parity" => $parity,
                     "custom_params" => $custom_params,
                ));

                $this->page->assign(array(
                    "list_item_parity" => $parity,
                    "list_item_style" => ($parity == 0) ?
                        "list-item-even" :
                        "list-item-odd",
                ));

                $this->page->parse_file(
                    "{$templates_dir}/list_item.{$templates_ext}", "{$obj_name}_items"
                );
            }

            return $this->page->parse_file_new(
                "{$templates_dir}/list_items.{$templates_ext}", $template_var
            );
        }
    }
//
    function print_object_view_page($params) {
        $obj = get_param_value($params, "obj", null);
        if (is_null($obj)) {
            $obj_name = get_param_value($params, "obj_name", null);
            if (!is_null($obj_name)) {
                $obj = $this->read_id_fetch_db_object($obj_name);
            } else {
                die("No obj_name in print_object_edit_page()");
            }
        } else {
            $obj_name = $obj->table_name;
        }

        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", "body");
        $custom_params = get_param_value($params, "custom_params", array());

        $obj->print_values(array(
            "templates_dir" => $templates_dir,
            "context" => $context,
            "custom_params" => $custom_params,
        ));
        $this->page->parse_file_new("{$templates_dir}/view_info.html", "{$obj_name}_info");
        return $this->page->parse_file_new("{$templates_dir}/view.html", $template_var);
    }
//
    function print_object_edit_page($params) {
        $obj = get_param_value($params, "obj", null);
        if (is_null($obj)) {
            $obj_name = get_param_value($params, "obj_name", null);
            if (!is_null($obj_name)) {
                $obj = $this->read_id_fetch_db_object($obj_name);
            } else {
                die("No obj_name in print_object_edit_page()");
            }
        } else {
            $obj_name = $obj->table_name;
        }

        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", "body");
        $custom_params = get_param_value($params, "custom_params", array());

        $obj->print_form_values(array(
            "templates_dir" => $templates_dir,
            "context" => $context,
            "custom_params" => $custom_params,
        ));
        $this->page->parse_file_new("{$templates_dir}/edit_form.html", "{$obj_name}_form");
        $this->print_object_edit_page_titles($obj);
        return $this->page->parse_file_new("{$templates_dir}/edit.html", $template_var);
    }
//
    function delete_object($params = array()) {
        $obj = get_param_value($params, "obj", null);
        if (is_null($obj)) {
            $obj_name = get_param_value($params, "obj_name", null);
            if (!is_null($obj_name)) {
                $obj = $this->read_id_fetch_db_object($obj_name);
            } else {
                die("No obj_name in delete_object()");
            }
        } else {
            $obj_name = $obj->table_name;
        }

        $default_url = "?action=pg_view_" . $obj->get_plural_resource_name();
        $error_url = get_param_value($params, "error_url", $default_url);
        $success_url = get_param_value($params, "success_url", $default_url);
        $cascade = get_param_value($params, "cascade", false);

        if ($cascade) {
            $obj->del_cascade();
        } else {
            $messages = $obj->check_restrict_relations_before_delete();
            
            if (count($messages) != 0) {
                $this->print_status_messages_cannot_delete_object($messages);
                $this->create_redirect_response($error_url);
                return;
            } else {
                $obj->del();
            }
        }
        $this->print_status_message_object_deleted($obj);
        $this->create_redirect_response($success_url);
    }
//
//  Page titles and status messages
    function print_page_titles($page_name = "") {
        $this->print_head_and_page_title(
            $this->create_page_title_resource($page_name)
        );
    }

    function create_page_title_resource($page_name = "") {
        if ($page_name == "") {
            $resource = "page_title_{$this->action}";
        } else {
            $resource = "page_title_{$this->action}_{$page_name}";
        }
        return $resource;
    }

    function print_head_and_page_title($resource) {
        $this->page->assign("page_title_resource", $resource);
        $this->print_page_title($resource);
        $this->print_head_page_title($resource);
    }

    function print_page_title($resource) {
        $this->page->assign("page_title", $this->get_message($resource));
    }

    function print_head_page_title($resource) {
        $resource_text = $this->get_message("head_{$resource}");
        if (is_null($resource_text)) {
            $resource_text = $this->get_message($resource);
        }
        $this->page->assign("head_page_title", $resource_text);
    }

    function print_object_edit_page_titles($obj) {
        $resource = $this->create_page_title_resource();
        if (!$obj->is_definite()) {
            $resource .= "_new";
        }
        $this->print_head_and_page_title($resource);
    }

    function print_status_message($message) {
        $msg_text_raw = $this->get_message($message->resource);
        $this->page->assign($message->resource_params);
        $this->page->assign(array(
            "text" => "",
            "type" => $message->type,
        ));
        $this->page->parse_text($msg_text_raw, "text");
        return $this->page->parse_file("_status_message.html", "status_messages");
    }

    function print_status_messages($messages) {
        foreach ($messages as $message) {
            $this->print_status_message($message);
        }    
    }

    function print_status_message_object_updated($obj) {
        $action_done = ($obj->is_definite()) ? "updated" : "added";
        $this->add_session_status_message(
            new OkStatusMsg("{$obj->table_name}_{$action_done}")
        );
    }
        
    function print_status_message_object_deleted($obj) {
        $this->add_session_status_message(
            new OkStatusMsg("{$obj->table_name}_deleted")
        );
    }

    function print_session_status_messages() {
        $this->print_status_messages(
            $this->get_and_delete_session_status_messages()
        );
    }

    function print_status_messages_cannot_delete_object($messages) {
        foreach ($messages as $message) {
            $this->add_session_status_message($message);
        }
    }

    function add_session_status_message($new_msg) {
        if (Session::has_param("status_messages")) {
            $old_msgs = Session::get_param("status_messages");
        } else {
            $old_msgs = array();
        }
        $msgs = array_merge($old_msgs, array($new_msg));
        Session::set_param("status_messages", $msgs);
    }

    function get_and_delete_session_status_messages() {
        if (!Session::has_param("status_messages")) {
            return array();
        } else {
            $msgs = Session::get_param("status_messages");
            Session::unset_param("status_messages");
            return $msgs;
        }
    }
//
    function process_create_update_tables() {
        $actual_table_names = $this->db->get_actual_table_names();
        $table_names = array_keys($this->tables);

        $table_names_to_create = array_diff($table_names, $actual_table_names);
        $table_names_to_update = array_intersect($table_names, $actual_table_names);
        $table_names_to_drop = array_diff($actual_table_names, $table_names);

        foreach ($table_names_to_create as $table_name) {
            $obj = $this->create_db_object($table_name);
            $obj->create_table();
        }

        foreach ($table_names_to_update as $table_name) {
            $obj = $this->create_db_object($table_name);
            $obj->update_table();
        }

        $this->process_delete_tables($table_names_to_drop);
    }

    function process_delete_tables($table_names_to_drop = null) {
        if (is_null($table_names_to_drop)) {
            $table_names_to_drop = $this->db->get_actual_table_names();
        }
        foreach ($table_names_to_drop as $table_name) {
            $this->db->drop_table($table_name);
        }
    }
//
    function get_image() {
        $image = $this->read_id_fetch_db_object("image");
        if ($image->is_definite()) {
            $cached_gmt_str = (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) ?
                $_SERVER["HTTP_IF_MODIFIED_SINCE"] : null;
            $this->response = new ImageResponse($image, $cached_gmt_str);
        } else {
            $this->response = new HttpResponse();
            $this->response->add_header(new HttpHeader("HTTP/1.0 404 Not Found"));
        }
    }
}

?>