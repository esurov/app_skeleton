<?php

class App {

    var $app_name;

    // Html page template
    var $page;
    var $html_charset;

    var $messages;
    var $actions;

    var $user;
    var $action;
    var $action_params;
    var $page_template_name;
    var $response;

    var $config;
    var $log;

    var $db;
    var $tables;

    var $popup = 0;
    var $report = 0;
    var $printable = 0;

    var $lang;
    var $dlang;
    var $avail_langs;

    function App($app_name, $tables) {
        $this->app_name = $app_name;
        $this->tables = $tables;
        $this->response = null;

        $this->create_config();
        $this->create_logger();
        $this->create_db();

        $this->create_page_template();
        $this->html_charset = $this->config->get_value("html_charset");
        $this->print_raw_value("html_charset", $this->html_charset);

        $this->create_pager();

        $this->avail_langs = $this->get_avail_langs();
        $this->dlang = $this->config->get_value("default_language");
        $this->lang = $this->get_current_lang();

        $this->messages = new Config();
        $this->messages->read("lang/default.txt");
        $this->messages->read("lang/{$this->lang}.txt");

        $this->init_lang_dependent_data();

        $action_params = array();
        
        // One action defined, but nobody can access it
        $actions = array("pg_index" => array("valid_users" => array()));

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
        $this->log->set_max_filesize($this->config->get_value("log_max_filesize"));
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
        $this->page_template_name = "";
    }

    function create_pager() {
        $n_rows_per_page = $this->config->get_value("{$this->app_name}_rows_per_page", 20);
        $this->pager = new Pager($this, $n_rows_per_page);
    }

    function drop_pager() {
        $this->pager->n_rows_per_page = 10000;
    }

    function create_email_sender() {
        $email_sender = new PHPMailer();
        $email_sender->IsSendmail();
        $email_sender->IsHTML($this->config->get_value("email_is_html"));
        $email_sender->CharSet = $this->config->get_value("email_charset");
        return $email_sender;
    }

    function get_actual_email_to($email_to) {
        return $this->config->get_value("email_debug_mode") ?
            $email_to : $this->config->get_value("admin_email_to");
    }

    function init_lang_dependent_data() {
        foreach ($this->messages->params as $key => $value) {
            $this->print_raw_value("str_{$key}", $value);
        }
        $this->print_raw_value("lang", $this->lang);
    }
//
    function run() {
        // Create user used to run allowed actions only
        $this->create_current_user();

        // Read action name
        $this->action = trim(param("action"));
        if ($this->action == "") {
            $this->action = $this->get_default_action_name();
        }

        // Ensure that action name is valid
        $this->log->write("App", "Validating action '{$this->action}'", 3);

        if ($this->validate_action_name()) {
            // Ensure that current user is allowed to run this action
            // Validate user permission level
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
        } else {
            $this->action = $this->get_default_action_name();
            $this->log->write(
                "App", "Invalid action! Will try default action '{$this->action}'", 3
            );
            if ($this->validate_action_name()) {
                $this->run_invalid_action_name_action();
            } else {
                $this->log->write("App", "Action '{$this->action}' is invalid too!", 3);
                die();
            }
        }

        if (is_null($this->response)) {
            $this->create_html_document_response();
        }

        $this->response->send();
    }
    
    function create_current_user() {
        return null;
    }

    function validate_action_name() {
        return isset($this->actions[$this->action]);
    }

    function get_default_action_name($user = null) {
        return "pg_index";
    }

    function get_user_access_level($user = null) {
        // Return user access level (string) for selecting allowed actions
        // for previously created user by function create_current_user()
        return "guest";
    }
//
    function run_action($action_name = null, $action_params = array()) {
        // Run action and return its response
        if (!is_null($action_name)) {
            $this->action = $action_name;
        }
        $this->action_params = $action_params;
        $page_name = get_param_value($action_params, "page", trim(param("page")));

        $action_func_name = $this->action;
        $action_name_expanded = ($page_name == "") ?
            $this->action : $this->action . "_" . $page_name;
        
        $this->print_values(array(
            "action" => $action_func_name,
            "action_expanded" => $action_name_expanded,
            "page" => $page_name,
        ));
        $this->action = $action_name_expanded;

        $this->print_page_titles();
        
        $this->on_before_run_action();

        $this->log->write("App", "Running action '{$action_func_name}'", 3);
        $this->{$action_func_name}();  // NB! Variable function

        $this->on_after_run_action();
    }

    function on_before_run_action() {
        $this->popup = (int) param("popup");
        $this->report = (int) param("report");
        $this->printable = (int) param("printable");

        $this->print_custom_param("popup", $this->popup);
        $this->print_custom_param("report", $this->report);
        $this->print_custom_param("printable", $this->printable);

        if (!$this->report && !$this->printable) {
            $this->print_session_status_messages();
        }
    }

    function on_after_run_action() {
    }
//
    function run_access_denied_action() {
        $this->create_access_denied_html_document_response();
    }

    function run_invalid_action_name_action() {
        $this->create_self_redirect_response(array("action" => $this->action));
    }
//
    function get_http_auth_user_access_level() {
        $login = $this->config->get_value("admin_login");
        $password = $this->config->get_value("admin_password");
        return ($this->is_valid_http_auth_user($login, $password)) ? "user" : "guest";
    }

    function is_valid_http_auth_user($login, $password) {
        return
            (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) &&
            $_SERVER["PHP_AUTH_USER"] == $login &&
            $_SERVER["PHP_AUTH_PW"] == $password;
    }
//
    function create_redirect_response($url) {
        $this->response = new RedirectResponse($url);
        $this->log->write("App", "Redirect to {$url}", 3);
    }

    function create_self_redirect_response($suburl_params = array(), $protocol = "http") {
        $this->create_redirect_response(
            create_self_full_url(
                $suburl_params + $this->get_app_extra_suburl_params(),
                $protocol
            )
        );
    }

    function get_app_extra_suburl_params() {
        $params = array();
        if ($this->popup != 0) {
            $params["popup"] = $this->popup;
        }
        return $params;
    }
//
    function create_binary_content_response($content, $filename) {
        $this->response = new BinaryContentResponse($content, "application/octet-stream");
        $this->response->add_content_disposition_header($filename);
    }
//
    function create_html_document_response() {
        $this->create_html_page_template_name();
        $this->response = new HtmlDocumentResponse(
            $this->create_html_document_body_content(), $this->html_charset
        );
    }

    function create_html_document_body_content() {
        $this->print_menu();
        return $this->print_file($this->page_template_name);
    }

    function create_html_page_template_name() {
        if ($this->page_template_name == "") {
            if ($this->popup != 0) {
                $popup_page_template_name = "page_popup.html";
                if ($this->is_file_exist($popup_page_template_name)) {
                    $this->page_template_name = $popup_page_template_name;
                }
            } else if ($this->report != 0) {
                $report_page_template_name = "page_report.html";
                if ($this->is_file_exist($report_page_template_name)) {
                    $this->page_template_name = $report_page_template_name;
                }
            } else if ($this->printable != 0) {
                $printable_page_template_name = "page_printable.html";
                if ($this->is_file_exist($printable_page_template_name)) {
                    $this->page_template_name = $printable_page_template_name;
                }
            }
            if ($this->page_template_name == "") {
                $this->page_template_name = "page.html";
            }
        }
    }

    function create_access_denied_html_document_response() {
        $this->page_template_name = "page_access_denied.html";
        $this->create_html_document_response();
    }

    function create_http_auth_html_document_response($realm) {
        $this->create_access_denied_html_document_response();
        $this->response->push_headers(array(
            new HttpHeader("WWW-Authenticate", "Basic realm=\"{$realm}\""),
            new HttpHeader("HTTP/1.0 401 Unauthorized"),
        ));
    }
//
    function create_xml_document_response($content) {
        $this->response = new XmlDocumentResponse($content);
    }

    function create_plain_text_document_response(
        $content, $filename = null, $open_inline = true
    ) {
        $this->response = new PlainTextDocumentResponse($content, $filename, $open_inline);
    }

    function create_pdf_document_response(
        $content, $filename = null, $open_inline = true
    ) {
        $this->response = new PdfDocumentResponse($content, $filename, $open_inline);
    }
//
    function create_db_object($obj_name) {
        if (!isset($this->tables[$obj_name])) {
            $this->log->write(
                "App",
                "Cannot find and instantiate db_object child class for '{$obj_name}'!"
            );
            die();
        }
        $obj_class_name = $this->tables[$obj_name];
        if (!class_exists($obj_class_name)) {
            require_once(TABLES_DIR . "/{$obj_name}.php");
        }
        return new $obj_class_name();
    }
//
    function fetch_db_object(
        $obj,
        $id,
        $where_str = "1",
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        // If object is given by its name then create instance here
        // If no then instance should be created outside this function
        // and could be expanded with new fields (using insert_field())
        if (is_string($obj)) {
            $obj = $this->create_db_object($obj);
        }
        if ($id != 0) {
            $obj->fetch(
                "{$obj->table_name}.id = {$id} AND {$where_str}",
                $field_names_to_select,
                $field_names_to_not_select
            );
        }
        return $obj;
    }

    function fetch_db_objects_list(
        $obj,
        $query_ex,
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        if (is_string($obj)) {
            $obj = $this->create_db_object($obj);
        }
        $res = $obj->run_expanded_select_query(
            $query_ex,
            $field_names_to_select,
            $field_names_to_not_select
        );
        $objects = array();
        while ($row = $res->fetch()) {
            $obj->fetch_row($row);
            $objects[] = $obj;
        }
        return $objects;
    }

    function fetch_rows_list($query) {
        $res = $this->db->run_select_query($query);
        $rows = array();
        while ($row = $res->fetch()) {
            $rows[] = $row;
        }
        return $rows;
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
        $pr_key_value = (int) param($id_param_name);
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
        $date_parts = parse_date_by_format($this->get_db_date_format(), $db_date);
        return create_date_by_format($this->get_app_date_format(), $date_parts, $date_if_unknown);
    }

    function get_app_time($db_time, $date_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_time_format(), $db_time);
        return create_date_by_format($this->get_app_time_format(), $date_parts, $date_if_unknown);
    }

    function get_db_datetime($app_datetime, $date_if_unknown = "0000-00-00 00:00:00") {
        $date_parts = parse_date_by_format($this->get_app_datetime_format(), $app_datetime);
        return create_date_by_format($this->get_db_datetime_format(), $date_parts, $date_if_unknown);
    }

    function get_db_date($app_date, $date_if_unknown = "0000-00-00") {
        $date_parts = parse_date_by_format($this->get_app_date_format(), $app_date);
        return create_date_by_format($this->get_db_date_format(), $date_parts, $date_if_unknown);
    }

    function get_db_time($app_time, $date_if_unknown = "00:00:00") {
        $date_parts = parse_date_by_format($this->get_app_time_format(), $app_time);
        return create_date_by_format($this->get_db_time_format(), $date_parts, $date_if_unknown);
    }
//    
    function get_db_datetime_from_timestamp($ts) {
        $date_parts = get_date_parts_from_timestamp($ts);
        return create_date_by_format(
            $this->get_db_datetime_format(),
            $date_parts,
            ""
        );
    }

    function get_db_date_from_timestamp($ts) {
        $date_parts = get_date_parts_from_timestamp($ts);
        return create_date_by_format(
            $this->get_db_date_format(),
            $date_parts,
            ""
        );
    }

    function get_db_now_datetime() {
        return $this->get_db_datetime_from_timestamp(time());
    }

    function get_db_now_date() {
        return $this->get_db_date_from_timestamp(time());
    }
//
    function get_timestamp_from_db_datetime($db_datetime) {
        return get_timestamp_from_date_parts(
            parse_date_by_format(
                $this->get_db_datetime_format(),
                $db_datetime
            )
        );
    }
//
    function get_timestamp_from_db_date($db_date) {
        return get_timestamp_from_date_parts(
            parse_date_by_format(
                $this->get_db_date_format(),
                $db_date
            )
        );
    }
//
    function get_app_integer_value($php_integer_value) {
        return format_integer_value($php_integer_value, ",");
    }

    function get_php_integer_value($app_integer_value) {
        $result = str_replace(",", "", $app_integer_value);
        return (int) $result;
    }

    function get_app_double_value($php_double_value, $decimals) {
        return format_double_value($php_double_value, $decimals, ".", ",");
    }

    function get_php_double_value($app_double_value) {
        $result = str_replace(",", "", $app_double_value);
        return (double) $result;
    }

    function get_app_currency_value($php_double_value, $decimals) {
        return $this->get_app_double_value($php_double_value, $decimals);
    }

    function get_app_currency_with_sign_value(
        $php_double_value,
        $decimals = 2,
        $sign = null,
        $sign_at_start = null,
        $nonset_value_caption_pair = null
    ) {
        if (is_null($sign)) {
            $sign = $this->get_currency_sign();
        }
        if (is_null($sign_at_start)) {
            $sign_at_start = $this->is_currency_sign_at_start();
        }

        if (is_null($nonset_value_caption_pair)) {
            $nonset_value_caption_pair = $this->get_currency_nonset_value_caption_pair();
        }

        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((double) $php_double_value == (double) $nonset_value) {
                return get_caption_from_value_caption_pair($nonset_value_caption_pair);
            }
        }
        return $this->append_currency_sign(
            $this->get_app_currency_value($php_double_value, $decimals),
            $sign,
            $sign_at_start
        );
    }

    function append_currency_sign($str, $sign, $sign_at_start) {
        return ($sign_at_start) ? "{$sign}{$str}" : "{$str}{$sign}";
    }

    function get_currency_sign() {
        return "\xE2\x82\xAC ";
    }

    function is_currency_sign_at_start() {
        return true;
    }

    function get_currency_nonset_value_caption_pair() {
        return null;
    }
//
    function print_raw_value($template_var, $value) {
        $this->page->set_filling_value($template_var, $value);
    }

    function print_raw_values($h) {
        $this->page->set_filling_values($h);
    }

    function print_value($template_var, $value) {
        $this->print_raw_value($template_var, get_html_safe_string($value));
    }

    function print_values($h) {
        foreach ($h as $template_var => $value) {
            $this->print_value($template_var, $value);
        }
    }

    function print_suburl_value($template_var, $suburl_params) {
        if (!is_array($suburl_params)) {
            $suburl_params = array($template_var => $suburl_params);
        }
        $this->print_value("{$template_var}_suburl", create_suburl($suburl_params));
    }

    function print_suburl_values($suburls_info) {
        foreach ($suburls_info as $template_var => $suburl_params) {
            $this->print_suburl_value($template_var, $suburl_params);
        }
    }
//
    function print_file($template_name, $append_to_name = null) {
        return $this->page->parse_file($template_name, $append_to_name);
    }

    function print_file_new($template_name, $name = null) {
        return $this->page->parse_file_new($template_name, $name);
    }

    function print_file_if_exists($template_name, $name = null) {
        return $this->page->parse_file_if_exists($template_name, $name);
    }

    function print_file_new_if_exists($template_name, $name = null) {
        return $this->page->parse_file_new_if_exists($template_name, $name);
    }

    function is_file_exist($template_name) {
        return $this->page->is_template_exist($template_name);
    }
//
    function print_primary_key_value($template_var, $value) {
        $this->print_raw_value("{$template_var}", $value);
    }

    function print_foreign_key_value($template_var, $value) {
        $this->print_raw_value("{$template_var}", $value);
    }

    function print_integer_value(
        $template_var,
        $value,
        $nonset_value_caption_pair = null
    ) {
        $value_with_nonset = $value;
        $value_formatted = $this->get_app_integer_value($value);
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((int) $value == (int) $nonset_value) {
                $nonset_caption = get_html_safe_string(
                    get_caption_from_value_caption_pair($nonset_value_caption_pair)
                );
                $value_with_nonset = $nonset_caption;
                $value_formatted = $nonset_caption;
            }
        }
        $this->print_raw_values(array(
            "{$template_var}" => $value_formatted,
            "{$template_var}_orig" => $value,
            "{$template_var}_orig_with_nonset" => $value_with_nonset,
        ));
    }

    function print_double_value(
        $template_var,
        $value,
        $decimals,
        $nonset_value_caption_pair = null
    ) {
        $value_with_nonset = $value;
        $value_formatted = $this->get_app_double_value($value, $decimals);
        $value_formatted_2 = $this->get_app_double_value($value, 2);
        $value_formatted_5 = $this->get_app_double_value($value, 5);
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((double) $value == (double) $nonset_value) {
                $nonset_caption = get_html_safe_string(
                    get_caption_from_value_caption_pair($nonset_value_caption_pair)
                );
                $value_with_nonset = $nonset_caption;
                $value_formatted = $nonset_caption;
                $value_formatted_2 = $nonset_caption;
                $value_formatted_5 = $nonset_caption;
            }
        }
        $this->print_raw_values(array(
            "{$template_var}" => $value_formatted,
            "{$template_var}_2" => $value_formatted_2,
            "{$template_var}_5" => $value_formatted_5,
            "{$template_var}_orig" => $value,
            "{$template_var}_orig_with_nonset" => $value_with_nonset,
        ));
    }

    function print_currency_value(
        $template_var,
        $value,
        $decimals,
        $sign = null,
        $sign_at_start = null,
        $nonset_value_caption_pair = null
    ) {
        $value_with_nonset = $value;
        $value_formatted = $this->get_app_currency_with_sign_value(
            $value,
            $decimals,
            $sign,
            $sign_at_start,
            $nonset_value_caption_pair
        );
        $value_formatted_without_sign = $this->get_app_currency_value($value, $decimals);
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((double) $value == (double) $nonset_value) {
                $nonset_caption = get_html_safe_string(
                    get_caption_from_value_caption_pair($nonset_value_caption_pair)
                );
                $value_with_nonset = $nonset_caption;
                $value_formatted_without_sign = $nonset_caption;
            }
        }
        $this->print_raw_values(array(
            "{$template_var}" => $value_formatted,
            "{$template_var}_without_sign" => $value_formatted_without_sign,
            "{$template_var}_orig" => $value,
            "{$template_var}_orig_with_nonset" => $value_with_nonset,
        ));
    }

    function print_boolean_value($template_var, $value, $value_caption_pairs = null) {
        if (is_null($value_caption_pairs)) {
            $value_caption_pairs = array(
                array(0, $this->get_message("no")),
                array(1, $this->get_message("yes")),
            );
        }
        $caption = get_caption_by_value_from_value_caption_pairs(
            $value_caption_pairs,
            ((int) $value != 0) ? 1 : 0
        );
        $this->print_value("{$template_var}", $caption);
        $this->print_raw_value("{$template_var}_orig", $value);
    }

    function print_enum_value($template_var, $enum_value, $value_caption_pairs) {
        $enum_value = get_actual_current_value($value_caption_pairs, $enum_value);
        $enum_caption = get_caption_by_value_from_value_caption_pairs(
            $value_caption_pairs,
            $enum_value
        );
        $enum_caption = is_null($enum_caption) ? "" : $enum_caption;
        $this->print_value("{$template_var}", $enum_caption);
        $this->print_raw_value("{$template_var}_caption_orig", $enum_caption);
        $this->print_raw_value("{$template_var}_orig", $enum_value);
    }

    function print_varchar_value($template_var, $value) {
        $this->print_value("{$template_var}", $value);
        $safe_value = get_html_safe_string($value);
        $this->print_raw_value("{$template_var}_orig", $value);
    }

    function print_text_value($template_var, $value) {
        $this->print_varchar_value("{$template_var}", $value);
        $safe_value = get_html_safe_string($value);
        $this->print_raw_value("{$template_var}_lf2br", convert_lf2br($safe_value));
    }

    function print_datetime_value($template_var, $db_datetime) {
        $this->print_values(array(
            "{$template_var}" => $this->get_app_datetime($db_datetime),
            "{$template_var}_orig" => $db_datetime,
        ));
    }

    function print_date_value($template_var, $db_date) {
        $this->print_values(array(
            "{$template_var}" => $this->get_app_date($db_date),
            "{$template_var}_orig" => $db_date,
        ));
    }

    function print_time_value($template_var, $db_time) {
        $this->print_values(array(
            "{$template_var}" => $this->get_app_time($db_time),
            "{$template_var}_orig" => $db_time,
        ));
    }
//
    function print_primary_key_form_value($template_var, $value) {
        $this->print_hidden_input_form_value($template_var, $value);
        $this->print_text_input_form_value($template_var, $value);
    }

    function print_foreign_key_form_value(
        $template_var,
        $value,
        $input_type,
        $input_attrs,
        $values_info,
        $input_type_params
    ) {
        $this->print_hidden_input_form_value($template_var, $value);

        switch ($input_type) {
        case "text":
            $printed_value = $this->print_text_input_form_value(
                $template_var,
                $value,
                $input_attrs
            );
            break;
        case "radio":
            $printed_value = $this->print_radio_group_input_form_value(
                $template_var,
                $value,
                $input_attrs,
                $values_info
            );
            break;
        case "select":
            $printed_value = $this->print_select_input_form_value(
                $template_var,
                $value,
                $input_attrs,
                $values_info
            );
            break;
        case "main_select":
            $printed_value = $this->print_main_select_input_form_value(
                $template_var,
                $value,
                $input_attrs,
                $values_info,
                $input_type_params["dependent_select_name"],
                $input_type_params["dependency_info"],
                $input_type_params["dependent_values_info"]
            );
            break;
        default:
            $printed_value = "";
        }

        return $printed_value;
    }

    function print_integer_form_value(
        $template_var,
        $value,
        $input_attrs,
        $nonset_value_caption_pair = null
    ) {
        $value_formatted = $this->get_app_integer_value($value);
        $this->print_hidden_input_form_value($template_var, $value_formatted);

        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((int) $value == (int) $nonset_value) {
                $nonset_caption = get_caption_from_value_caption_pair($nonset_value_caption_pair);
                $value_formatted = $nonset_caption;
            }
        }
        
        return $this->print_text_input_form_value(
            $template_var,
            $value_formatted,
            array_merge(
                array("class" => "integer"),
                $input_attrs
            )
        );
    }
    
    function print_double_form_value(
        $template_var,
        $value,
        $decimals,
        $input_attrs,
        $nonset_value_caption_pair = null
    ) {
        $value_formatted = $this->get_app_double_value($value, $decimals);
        $this->print_hidden_input_form_value($template_var, $value_formatted);
        
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((double) $value == (double) $nonset_value) {
                $nonset_caption = get_caption_from_value_caption_pair($nonset_value_caption_pair);
                $value_formatted = $nonset_caption;
            }
        }

        return $this->print_text_input_form_value(
            $template_var,
            $value_formatted,
            array_merge(
                array("class" => "double"),
                $input_attrs
            )
        );
    }

    function print_currency_form_value(
        $template_var,
        $value,
        $decimals,
        $input_attrs,
        $values_info
    ) {
        $values_data_info = get_param_value($values_info, "data", array());
        $sign = get_param_value($values_data_info, "sign", $this->get_currency_sign());
        $sign_at_start = get_param_value(
            $values_data_info,
            "sign_at_start",
            $this->is_currency_sign_at_start()
        );

        $value_formatted_without_sign = $this->get_app_currency_value($value, $decimals);
        $this->print_hidden_input_form_value($template_var, $value_formatted_without_sign);
        
        $printed_value = $this->print_text_input_form_value(
            $template_var,
            $value_formatted_without_sign,
            array_merge(
                array("class" => "currency"),
                $input_attrs
            )
        );
        $this->print_raw_value(
            "{$template_var}_input",
            $this->append_currency_sign($printed_value, $sign, $sign_at_start)
        );
        $this->print_text_input_form_value(
            "{$template_var}_without_sign",
            $value_formatted_without_sign,
            array_merge(
                array("class" => "currency"),
                $input_attrs
            )
        );
        return $printed_value;
    }

    function print_boolean_form_value(
        $template_var,
        $value,
        $input_attrs
    ) {
        $this->print_hidden_input_form_value($template_var, $value);
        return $this->print_checkbox_input_form_value($template_var, $value, $input_attrs);
    }

    function print_enum_form_value(
        $template_var,
        $enum_value,
        $input_type,
        $input_attrs,
        $values_info
    ) {
        $this->print_hidden_input_form_value($template_var, $enum_value);

        switch ($input_type) {
        case "radio":
            $printed_value = $this->print_radio_group_input_form_value(
                $template_var,
                $enum_value,
                $input_attrs,
                $values_info
            );
            break;
        case "select":
            $printed_value = $this->print_select_input_form_value(
                $template_var,
                $enum_value,
                $input_attrs,
                $values_info
            );
            break;
        default:
            $printed_value = "";
        }

        return $printed_value;
    }

    function print_varchar_form_value(
        $template_var,
        $value,
        $input_type,
        $input_attrs
    ) {
        $this->print_hidden_input_form_value($template_var, $value);
        
        switch ($input_type) {
        case "text":
        case "password":
            $printed_value = $this->print_input_form_value(
                $input_type,
                $template_var,
                $value,
                $input_attrs
            );
            break;
        default:
            $printed_value = "";
        }

        return $printed_value;
    }

    function print_text_form_value(
        $template_var,
        $value,
        $input_type,
        $input_attrs
    ) {
        $this->print_hidden_input_form_value($template_var, $value);
        
        switch ($input_type) {
        case "textarea":
            $printed_value = $this->print_textarea_input_form_value(
                $template_var,
                $value,
                $input_attrs
            );
            break;
        default:
            $printed_value = "";
        }

        return $printed_value;
    }

    function print_multilingual_form_value($template_var, $value) {
        $lang_inputs_with_captions_str = "";
        foreach ($this->avail_langs as $lang) {
            $lang_str = $this->get_message($lang);
            $lang_input = $this->page->get_filling_value("{$template_var}_{$lang}_input");
            $lang_inputs_with_captions_str .=
                "<tr>\n" .
                "<th>{$lang_str}:</th>\n" .
                "<td>{$lang_input}</td>\n" .
                "</tr>\n";
        }
        $printed_value = 
            "<table>\n" .
            $lang_inputs_with_captions_str .
            "</table>\n";
        $this->print_raw_values(array(
            "{$template_var}_input" => $printed_value,
            "{$template_var}_hidden" =>
                $this->page->get_filling_value("{$template_var}_{$this->lang}_hidden"),
        ));
        return $printed_value;
    }

    function print_datetime_form_value($template_var, $db_datetime, $input_attrs) {
        $app_datetime = $this->get_app_datetime($db_datetime);
        $this->print_hidden_input_form_value($template_var, $app_datetime);
        return $this->print_text_input_form_value(
            $template_var,
            $app_datetime,
            array_merge(
                array("class" => "datetime"),
                $input_attrs
            )
        );
    }

    function print_date_form_value($template_var, $db_date, $input_attrs) {
        $app_date = $this->get_app_date($db_date);
        $this->print_hidden_input_form_value($template_var, $app_date);
        return $this->print_text_input_form_value(
            $template_var,
            $app_date,
            array_merge(
                array("class" => "date"),
                $input_attrs
            )
        );
    }

    function print_time_form_value($template_var, $db_time, $input_attrs) {
        $app_time = $this->get_app_time($db_time);
        $this->print_hidden_input_form_value($template_var, $app_time);
        return $this->print_text_input_form_value(
            $template_var,
            $app_time,
            array_merge(
                array("class" => "time"),
                $input_attrs
            )
        );
    }
//
    function print_raw_input_form_value(
        $input_type,
        $template_var,
        $value,
        $input_attrs = array()
    ) {
        $printed_value = print_raw_html_input($input_type, $template_var, $value, $input_attrs);
        $this->print_raw_value("{$template_var}_input", $printed_value);
        return $printed_value;
    }

    function print_raw_hidden_form_value($template_var, $value) {
        return $this->print_raw_input_form_value("hidden", $template_var, $value);
    }

    function print_input_form_value(
        $input_type,
        $template_var,
        $value,
        $input_attrs = array()
    ) {
        $printed_value = print_html_input($input_type, $template_var, $value, $input_attrs);
        $this->print_raw_value("{$template_var}_input", $printed_value);
        return $printed_value;
    }

    function print_hidden_input_form_value($template_var, $value) {
        $printed_value = print_html_hidden($template_var, $value);
        $this->print_raw_value("{$template_var}_hidden", $printed_value);
        return $printed_value;
    }

    function print_text_input_form_value($template_var, $value, $input_attrs = array()) {
        return $this->print_input_form_value("text", $template_var, $value, $input_attrs);
    }

    function print_textarea_input_form_value($template_var, $value, $input_attrs = array()) {
        $printed_value = print_html_textarea($template_var, $value, $input_attrs);
        $this->print_raw_value("{$template_var}_input", $printed_value);
        return $printed_value;
    }

    function print_checkbox_input_form_value($template_var, $value, $input_attrs = array()) {
        $printed_value = print_html_checkbox($template_var, $value, null, $input_attrs);
        $this->print_raw_value("{$template_var}_input", $printed_value);
        return $printed_value;
    }

    function print_radio_group_input_form_value(
        $template_var,
        $value,
        $input_attrs,
        $values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info, $alt_values_info);

        $values_data_info = get_param_value($values_info, "data", array());
        $delimiter = get_param_value($values_data_info, "delimiter", "");

        $printed_value = print_html_radio_group(
            $template_var,
            $value_caption_pairs,
            $value,
            $input_attrs,
            $delimiter
        );
        $this->print_raw_value("{$template_var}_input", $printed_value);
        return $printed_value;
    }

    function print_select_input_form_value(
        $template_var,
        $value,
        $input_attrs,
        $values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info, $alt_values_info);

        $printed_value = print_html_select(
            $template_var,
            $value_caption_pairs,
            $value,
            $input_attrs
        );
        $this->print_raw_value("{$template_var}_input", $printed_value);
        return $printed_value;
    }

    function print_main_select_input_form_value(
        $template_var,
        $value,
        $input_attrs,
        $values_info,
        $dependent_select_name,
        $dependency_info,
        $dependent_values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info, $alt_values_info);

        $form_name = get_param_value($dependency_info, "form_name", "form");
        $main_select_name = $template_var;
        $dependency_key_field_name = $dependency_info["key_field_name"];
        $dependency_query_ex = get_param_value($dependency_info, "query_ex", array());

        $input_attrs["onchange"] =
            "updateDependentSelect(this, '{$dependent_select_name}'); " .
            "if (this.form.{$dependent_select_name}.onchange) { " .
                "this.form.{$dependent_select_name}.onchange(); " .
            "}";

        $dependency_array = $this->get_select_dependency_array(
            $value_caption_pairs,
            $dependent_values_info,
            $dependency_key_field_name,
            $dependency_query_ex
        );

        $dependency_js = create_select_dependency_js(
            $form_name,
            $main_select_name,
            $dependent_select_name,
            $dependency_array
        );

        $printed_value =
            print_html_select($template_var, $value_caption_pairs, $value, $input_attrs);
        $this->print_raw_values(array(
            "{$template_var}_input" => $printed_value,
            "{$template_var}_dependency_js" => $dependency_js,
        ));
        return $printed_value;
    }

    function get_value_caption_pairs($values_info, $alt_values_info = null) {
        $value_caption_pairs = $this->get_value_caption_pairs_from_source($values_info);
        $value_caption_pairs = $this->expand_value_caption_pairs_with_begin_end(
            $value_caption_pairs,
            $values_info["data"]
        );
        
        if (!is_null($alt_values_info)) {
            $values_info = $alt_values_info;
        }
        $value_caption_pairs = $this->expand_value_caption_pairs_with_nonset(
            $value_caption_pairs,
            $values_info["data"]
        );
        return $value_caption_pairs;
    }

    function get_value_caption_pairs_from_source($values_info) {
        switch ($values_info["source"]) {
        case "array":
            $value_caption_pairs = $values_info["data"]["array"];
            break;
        case "db_object":
            $data_info = $values_info["data"];
            
            $obj_name = $data_info["obj_name"];
            $values_field_name = get_param_value($data_info, "values_field_name", "id");
            $captions_field_name = get_param_value($data_info, "captions_field_name", "name");
            $query_ex = get_param_value($data_info, "query_ex", array());

            $value_caption_pairs = $this->get_value_caption_pairs_from_db_object(
                $obj_name,
                $values_field_name,
                $captions_field_name,
                $query_ex
            );
            break;
        case "query":
            $data_info = $values_info["data"];
            
            $query = $data_info["query"];
            $values_field_name = get_param_value($data_info, "values_field_name", "id");
            $captions_field_name = get_param_value($data_info, "captions_field_name", "name");

            $value_caption_pairs = $this->get_value_caption_pairs_from_query(
                $query,
                $values_field_name,
                $captions_field_name
            );
            break;
        }
        return $value_caption_pairs;
    }

    function get_value_caption_pairs_from_db_object(
        $obj_name,
        $values_field_name,
        $captions_field_name,
        $query_ex
    ) {
        $objects = $this->fetch_db_objects_list($obj_name, $query_ex);
        $value_caption_pairs = array();
        foreach ($objects as $obj) {
            $value_caption_pairs[] =
                array($obj->{$values_field_name}, $obj->{$captions_field_name});
        }
        return $value_caption_pairs;
    }

    function get_value_caption_pairs_from_query(
        $query,
        $values_field_name,
        $captions_field_name
    ) {
        $rows = $this->fetch_rows_list($query);
        $value_caption_pairs = array();
        foreach ($rows as $row) {
            $value_caption_pairs[] =
                array($row[$values_field_name], $row[$captions_field_name]);
        }
        return $value_caption_pairs;
    }

    function expand_value_caption_pairs_with_begin_end(
        $value_caption_pairs,
        $begin_end_values_info
    ) {
        if (isset($begin_end_values_info["begin_value_caption_pairs"])) {
            $value_caption_pairs = array_merge(
                $begin_end_values_info["begin_value_caption_pairs"],
                $value_caption_pairs
            );
        }
        if (isset($begin_end_values_info["end_value_caption_pairs"])) {
            $value_caption_pairs = array_merge(
                $value_caption_pairs,
                $begin_end_values_info["end_value_caption_pairs"]
            );
        }
        return $value_caption_pairs;
    }

    function expand_value_caption_pairs_with_nonset(
        $value_caption_pairs,
        $nonset_values_info
    ) {
        if (isset($nonset_values_info["nonset_value_caption_pair"])) {
            $value_caption_pairs = array_merge(
                array($nonset_values_info["nonset_value_caption_pair"]),
                $value_caption_pairs
            );
        }
        return $value_caption_pairs;
    }
    
    function get_select_dependency_array(
        $main_select_value_caption_pairs,
        $dependent_values_info,
        $dependency_key_field_name,
        $dependency_query_ex
    ) {
        $dependent_query_ex = get_param_value(
            $dependent_values_info["data"],
            "query_ex",
            array()
        );

        $query_ex = new SelectQuery();
        $query_ex->expand($dependent_query_ex);
        $query_ex->expand($dependency_query_ex);

        $main_select_values =
            get_values_from_value_caption_pairs($main_select_value_caption_pairs);
        
        if (isset($dependent_values_info["data"]["nonset_value_caption_pair"])) {
            $dependent_select_nonset_value = get_value_from_value_caption_pair(
                $dependent_values_info["data"]["nonset_value_caption_pair"]
            );
        } else {
            $dependent_select_nonset_value = 0;
        }

        $dependency_array = array();
        foreach ($main_select_values as $main_select_value) {
            if ((string) $main_select_value == (string) $dependent_select_nonset_value) {
                $dependency_array[] = array($dependent_select_nonset_value);
                continue;
            }

            $final_query_ex = $query_ex;
            $final_query_ex->expand(array(
                "where" => "{$dependency_key_field_name} = {$main_select_value}",
            ));
            $dependent_values_info["data"]["query_ex"] = $final_query_ex;
            
            $dependent_select_value_caption_pairs =
                $this->get_value_caption_pairs_from_source($dependent_values_info);
            $dependent_select_value_caption_pairs =
                $this->expand_value_caption_pairs_with_begin_end(
                    $dependent_select_value_caption_pairs, $dependent_values_info["data"]
                );
            $dependent_select_value_caption_pairs =
                $this->expand_value_caption_pairs_with_nonset(
                    $dependent_select_value_caption_pairs, $dependent_values_info["data"]
                );

            $dependency_array[] =
                get_values_from_value_caption_pairs($dependent_select_value_caption_pairs);
        }
        return $dependency_array;
    }
//
    function get_message($resource, $resource_params = null) {
        $message_text = $this->messages->get_value($resource);
        if (is_null($message_text)) {
            return null;        
        } else {
            if (!is_null($resource_params)) {
                $this->print_values($resource_params);
            }
            return $this->page->get_parsed_text($message_text);
        }
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
        $this->set_current_lang(trim(param("new_lang")));
        $cur_action = trim(param("cur_action"));
        $cur_page = trim(param("cur_page"));
        
        $suburl_params = array();
        if ($cur_action != "") {
            $suburl_params["action"] = $cur_action;
        }
        if ($cur_page != "") {
            $suburl_params["page"] = $cur_page;
        }
        $this->create_self_redirect_response($suburl_params);
    }

    function pg_static() {
        $page_name = trim(param("page"));
        if (preg_match('/^\w+$/i', $page_name)) {
            $this->print_static_page($page_name);
        }
    }

//  Page contruction helper functions
    function print_static_page($page_name) {
        $this->print_static_file($page_name, "body");
    }

    function print_static_file($filename, $template_var) {
        $file_path = "static/{$filename}_{$this->lang}.html";
        if (!$this->is_file_exist($file_path)) {
            $file_path = "static/{$filename}.html";
        }
        return $this->print_file_if_exists($file_path, $template_var);
    }
//
    function print_menu($params = array()) {
        $templates_dir = get_param_value($params, "templates_dir", null);
        if (is_null($templates_dir)) {
            $params["templates_dir"] = ".";
        }
        $template_var = get_param_value($params, "template_var", null);
        if (is_null($template_var)) {
            $params["template_var"] = "menu";
        }
        $context = get_param_value($params, "context", null);
        if (is_null($context)) {
            $params["context"] = $this->action;
        }
        $xml_filename = get_param_value($params, "xml_filename", "menu.xml");

        $menu = new Menu($this);
        $menu->parse($menu->load_file("{$templates_dir}/{$xml_filename}"));
        
        return $menu->print_menu($params);
    }
//
    function print_lang_menu() {
        $avail_langs = $this->get_avail_langs();
        $this->print_raw_value("lang_menu_items", "");
        foreach ($avail_langs as $lang) {
            if ($lang == $this->lang) {
                $this->print_raw_values(array(
                    "current_lang_name" => $this->get_message($lang),
                    "current_lang_image_url" =>
                        $this->config->get_value("lang_image_current_url_{$lang}"),
                ));
                $this->print_file("_lang_menu_item_current.html", "lang_menu_items");
            } else {
                $this->print_raw_values(array(
                    "new_lang" => $lang,
                    "new_lang_name" => $this->get_message($lang),
                    "new_lang_image_url" =>
                        $this->config->get_value("lang_image_url_{$lang}"),
                ));
                $this->print_file("_lang_menu_item.html", "lang_menu_items");
            }
        }
        $this->print_file_new("_lang_menu.html", "lang_menu");
    }

//  Object functions
    function print_many_objects_list_page($params = array()) {
        $obj = get_param_value($params, "obj", null);
        $obj_name = get_param_value($params, "obj_name", null);
        if (is_null($obj)) {
            if (is_null($obj_name)) {
                die("No obj or obj_name in print_many_objects_list_page()");
            } else {
                $obj = $this->create_db_object($obj_name);
            }
        } else {
            $obj_name = $obj->table_name;
        }
        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $templates_ext = get_param_value($params, "templates_ext", "html");
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", "body");
        $query = get_param_value($params, "query", $obj->get_select_query());
        $query_ex = get_param_value($params, "query_ex", array());
        $default_order_by = get_param_value($params, "default_order_by", "id ASC");
        $show_filter_form = get_param_value($params, "show_filter_form", false);
        $filter_form_template_name = get_param_value(
            $params,
            "filter_form_template_name",
            "filter_form.html"
        );
        $custom_params = get_param_value($params, "custom_params", array());

        $query->expand($query_ex);

        // Apply filters to query
        $obj->read_filters();
        $filters_params = $obj->get_filters_params();
        $query->expand($obj->get_filters_query_ex());

        // Apply ordering to query
        $obj->read_order_by($default_order_by);
        $order_by_params = $obj->get_order_by_params();
        $query->expand($obj->get_order_by_query_ex());
        
        // Make sub-URLs with all necessary parameters stored
        $action_suburl_param = array("action" => $this->action);
        $extra_suburl_params = $this->get_app_extra_suburl_params();

        $action_suburl_params =
            $action_suburl_param +
            $custom_params +
            $extra_suburl_params;
               
        $action_filters_suburl_params =
            $action_suburl_param +
            $filters_params +
            $custom_params +
            $extra_suburl_params;

        $action_filters_order_by_suburl_params =
            $action_suburl_param +
            $filters_params +
            $order_by_params +
            $custom_params +
            $extra_suburl_params;

        $this->print_suburl_values(array(
            "action" => $action_suburl_params,
            "action_filters" => $action_filters_suburl_params,
            "action_filters_order_by" => $action_filters_order_by_suburl_params,
        ));

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
            "obj" => $obj,
            "obj_name" => $obj_name,
            "query" => $query,
            "templates_dir" => $templates_dir,
            "templates_ext" => $templates_ext,
            "context" => $context,
            "objects" => $objects,
            "custom_params" => $custom_params,
        ));

        if ($n > 0) {
            $this->pager->print_nav_str($action_filters_order_by_suburl_params);
            $this->print_value("total", $obj->get_quantity_str($n));
        }

        if ($show_filter_form) {
            $this->print_values($filters_params);
            $obj->print_filter_form_values();
            $this->print_file_new(
                "{$templates_dir}/{$filter_form_template_name}",
                "{$obj_name}_filter_form"
            );
        }

        return $this->print_file("{$templates_dir}/list.{$templates_ext}", $template_var);
    }
//
    function print_many_objects_list($params) {
        $obj = get_param_value($params, "obj", null);
        $obj_name = get_param_value($params, "obj_name", null);
        if (is_null($obj)) {
            if (is_null($obj_name)) {
                die("No obj or obj_name in print_many_objects_list()");
            } else {
                $obj = $this->create_db_object($obj_name);
            }
        } else {
            $obj_name = $obj->table_name;
        }
        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $templates_ext = get_param_value($params, "templates_ext", "html");
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", "{$obj_name}_list");
        $custom_params = get_param_value($params, "custom_params", array());

        $objects = get_param_value($params, "objects", null);
        $objects_passed = !is_null($objects);

        if ($objects_passed) {
            $n = count($objects);
        } else {
            $query = get_param_value($params, "query", $obj->get_select_query());
            $query_ex = get_param_value($params, "query_ex", array());
            
            $query->expand($query_ex);
            $res = $this->db->run_select_query($query);
            $n = $res->get_num_rows();
        }

        $this->print_custom_params($custom_params);

        $no_items_template_name = "{$templates_dir}/list_no_items.{$templates_ext}";
        if ($n == 0 && $this->is_file_exist($no_items_template_name)) {
            return $this->print_file($no_items_template_name, $template_var);
        } else {
            $this->print_raw_value("{$obj_name}_items", "");

            for ($i = 0; $i < $n; $i++) {
                if ($objects_passed) {
                    $row = array();
                    $obj = $objects[$i];
                } else {
                    $row = $res->fetch();
                    $obj->fetch_row($row);
                }

                $list_item_parity = $i % 2;
                $list_item_class = ($list_item_parity == 0) ?
                    "list-item-even" :
                    "list-item-odd";

                $this->print_raw_values(array(
                    "list_item_parity" => $list_item_parity,
                    "list_item_class" => $list_item_class,
                ));

                $obj->print_values(array(
                     "templates_dir" => $templates_dir,
                     "context" => $context,
                     "list_item_number" => $i + 1,
                     "list_item_parity" => $list_item_parity,
                     "list_item_class" => $list_item_class,
                     "list_items_count" => $n,
                     "custom_params" => $custom_params,
                     "row" => $row,
                ));

                $this->print_file(
                    "{$templates_dir}/list_item.{$templates_ext}",
                    "{$obj_name}_items"
                );
            }

            return $this->print_file(
                "{$templates_dir}/list_items.{$templates_ext}",
                $template_var
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
                die("No obj or obj_name in print_object_view_page()");
            }
        } else {
            $obj_name = $obj->table_name;
        }

        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", "body");
        $custom_params = get_param_value($params, "custom_params", array());

        $this->print_custom_params($custom_params);

        $obj->print_values(array(
            "templates_dir" => $templates_dir,
            "context" => $context,
            "custom_params" => $custom_params,
        ));
        
        $this->print_file_new("{$templates_dir}/view_info.html", "{$obj_name}_info");
        return $this->print_file("{$templates_dir}/view.html", $template_var);
    }
//
    function print_object_edit_page($params) {
        $obj = get_param_value($params, "obj", null);
        if (is_null($obj)) {
            $obj_name = get_param_value($params, "obj_name", null);
            if (!is_null($obj_name)) {
                $obj = $this->read_id_fetch_db_object($obj_name);
            } else {
                die("No obj or obj_name in print_object_edit_page()");
            }
        } else {
            $obj_name = $obj->table_name;
        }

        $templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $context = get_param_value($params, "context", "");
        $template_var = get_param_value($params, "template_var", "body");
        $custom_params = get_param_value($params, "custom_params", array());

        $this->print_custom_params($custom_params);

        $obj->print_form_values(array(
            "templates_dir" => $templates_dir,
            "context" => $context,
            "custom_params" => $custom_params,
        ));
        $this->print_object_edit_page_titles($obj);
        
        $this->print_file_new("{$templates_dir}/edit_form.html", "{$obj_name}_form");
        return $this->print_file("{$templates_dir}/edit.html", $template_var);
    }
//
    function print_custom_params($custom_params) {
        foreach ($custom_params as $param_name => $param_value) {
            if (is_scalar($param_value)) {
                $this->print_custom_param($param_name, $param_value);
            }
        }
    }

    function print_custom_param($param_name, $param_value) {
        $this->print_value($param_name, $param_value);
        $this->print_raw_value("{$param_name}_orig", $param_value);
        $this->print_suburl_value($param_name, $param_value);
        $this->print_hidden_input_form_value($param_name, $param_value);
    }
//
    function delete_object($params = array()) {
        $obj = get_param_value($params, "obj", null);
        if (is_null($obj)) {
            $obj_name = get_param_value($params, "obj_name", null);
            if (!is_null($obj_name)) {
                $obj = $this->read_id_fetch_db_object($obj_name);
            } else {
                die("No obj or obj_name in delete_object()");
            }
        } else {
            $obj_name = $obj->table_name;
        }

        $default_url_params = array("action" => "pg_view_" . $obj->get_plural_resource_name());
        $error_url_params = get_param_value($params, "error_url_params", $default_url_params);
        $success_url_params = get_param_value($params, "success_url_params", $default_url_params);
        $cascade = get_param_value($params, "cascade", false);

        if ($cascade) {
            $obj->del_cascade();
        } else {
            $messages = $obj->check_restrict_relations_before_delete();
            
            if (count($messages) != 0) {
                $this->print_status_messages_cannot_delete_object($messages);
                $this->create_self_redirect_response($error_url_params);
                return;
            } else {
                $obj->del();
            }
        }
        $this->print_status_message_object_deleted($obj);
        $this->create_self_redirect_response($success_url_params);
    }
//
    function move_db_object($obj, $move_to, $where_str = "1") {
        if (!$obj->is_definite()) {
            return;
        }
        $neighbor_obj = $obj->fetch_neighbor_db_object($move_to, $where_str);
        if (is_null($neighbor_obj)) {
            return;
        }
        $tmp_position = $obj->position;
        $obj->position = $neighbor_obj->position;
        $neighbor_obj->position = $tmp_position;

        $obj->update(array("position"));
        $neighbor_obj->update(array("position"));
    }
//
//  Page titles and status messages
    function print_page_titles() {
        $this->print_head_and_page_title($this->create_page_title_resource());
    }

    function create_page_title_resource() {
        return "page_title_{$this->action}";
    }

    function print_head_and_page_title($resource) {
        $this->print_raw_value("page_title_resource", $resource);
        $this->print_page_title($resource);
        $this->print_head_page_title($resource);
    }

    function print_page_title($resource, $is_html = false) {
        $page_title_text = $this->get_message($resource);
        if (!is_null($page_title_text)) {
            if ($is_html) {
                $this->print_raw_value("page_title_text", $page_title_text);
            } else {
                $this->print_value("page_title_text", $page_title_text);
            }
            $this->print_file_new_if_exists("_page_title.html", "page_title");
        }
    }

    function print_head_page_title($resource) {
        $resource_text = $this->get_message("head_{$resource}");
        if (is_null($resource_text)) {
            // If have no head_page_title use page_title instead
            $resource_text = $this->get_message($resource);
        }
        if (!is_null($resource_text)) {
            $this->print_raw_value("head_page_title", $resource_text);
        }
    }

    function print_object_edit_page_titles($obj) {
        $resource = $this->create_page_title_resource();
        if (!$obj->is_definite()) {
            $resource .= "_new";
        }
        $this->print_head_and_page_title($resource);
    }

    function print_status_message($message) {
        $status_message_text = $this->get_message($message->resource, $message->resource_params);
        $this->print_raw_values(array("text" => $status_message_text, "type" => $message->type));
        return $this->print_file("_status_message.html", "status_messages");
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
                get_gmt_str_from_if_modified_since($_SERVER["HTTP_IF_MODIFIED_SINCE"]) :
                null;
            $this->response = new ImageResponse($image, $cached_gmt_str);
        } else {
            $this->response = new HttpResponse();
            $this->response->add_header(new HttpHeader("HTTP/1.0 404 Not Found"));
        }
    }

    function delete_obj_image(
        $obj,
        $image_id_field_name = "image_id",
        $delete_thumbnail = true
    ) {
        if ($obj->is_definite() && $obj->is_field_exist($image_id_field_name)) {
            $field_names_to_update = array($image_id_field_name);

            $obj->del_image($image_id_field_name);
            $obj->{$image_id_field_name} = 0;

            $thumbnail_image_id_field_name = "thumbnail_{$image_id_field_name}";
            if ($delete_thumbnail && $obj->is_field_exist($thumbnail_image_id_field_name)) {
                $field_names_to_update[] = $thumbnail_image_id_field_name;

                $obj->del_image($thumbnail_image_id_field_name);
                $obj->{$thumbnail_image_id_field_name} = 0;
            }

            $obj->update($field_names_to_update);
        }
    }
//
    function get_file() {
        $open_inline = (int) param("show");

        $file = $this->read_id_fetch_db_object("file");
        if ($file->is_definite()) {
            $this->response = new FileResponse($file, $open_inline);
        } else {
            $this->response = new HttpResponse();
            $this->response->add_header(new HttpHeader("HTTP/1.0 404 Not Found"));
        }
    }

}

?>