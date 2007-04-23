<?php

class App extends AppObject {

    // App name
    var $app_name;

    // App core objects
    var $config;
    var $log;
    var $session;
    var $db;

    // Html page template vars
    var $html_charset;
    var $page;
    var $page_template_name;
    
    // App specific custom CGI vars
    var $popup = 0;
    var $report = 0;
    var $printable = 0;

    // Http response
    var $response = null;

    // Language vars
    var $lang; // Current language
    var $dlang; // Default language
    var $avail_langs; // Available languages
    var $lang_resources; // Language resources

    // Action vars
    var $actions;
    var $action;
    var $action_params;

    function App($app_class_name, $app_name) {
        parent::AppObject();

        $this->set_class_name($app_class_name); 
        $this->app_name = $app_name;

        $this->create_core_objects();
        $this->init_app();

        // One action defined, but nobody can access it
        $this->actions = array(
            "pg_index" => array("roles" => array())
        );

        $this->write_log(
            "App '{$this->app_name}' started",
            DL_INFO
        );
    }
//
    function create_core_objects() {
        $this->create_config();
        $this->create_logger();
        $this->create_session();
        $this->create_db();
        $this->create_page_template();
    }

    function create_config() {
        $this->config =& new Config();
        $this->config->read("config/app.cfg");
    }

    function create_logger() {
        $this->log =& $this->create_object("Logger");
    }

    function create_session() {
        $this->session =& $this->create_object("Session");
    }

    function create_db() {
        $sql_config = new Config();
        $sql_config->read("config/sql.cfg");

        $this->db =& $this->create_object(
            "MySqlDb",
            array(
                "host"     => $sql_config->get_value("host"),
                "database" => $sql_config->get_value("database"),
                "username" => $sql_config->get_value("username"),
                "password" => $sql_config->get_value("password"),
                "table_prefix" => $sql_config->get_value("table_prefix"),
            )
        );
    }

    function create_page_template() {
        $print_template_name = $this->get_config_value("print_template_name");
        $this->page =& new Template(
            $this->get_page_templates_dir(),
            $print_template_name
        );
        $this->page_template_name = "";
    }

    function get_page_templates_dir() {
        return "templates";
    }
//
    function init_app() {
        $this->html_charset = $this->get_config_value("html_charset");

        $this->init_lang_vars();
    }

    function init_lang_vars() {
        $this->avail_langs = $this->get_avail_langs();
        $this->dlang = $this->get_config_value("default_language");
        $this->lang = $this->get_current_lang();

        $this->init_lang_resources();
    }

    function init_lang_resources() {
        $this->lang_resources =& new Config();
        $this->lang_resources->read("lang/default.txt");
        $this->lang_resources->read("lang/{$this->lang}.txt");
    }
//
    function get_config_value($name, $default_value = null) {
        return $this->config->get_value($name, $default_value);
    }
//
    function get_log_debug_level() {
        return $this->log->get_debug_level();
    }

    function write_log($message, $debug_level, $class_name = null) {
        $this->log->write(
            is_null($class_name) ? $this->get_class_name() : $class_name,
            $message,
            $debug_level
        );
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
        $this->write_log(
            "Validating action '{$this->action}'",
            DL_INFO
        );

        $this->on_before_validate_action();
        
        if ($this->is_valid_action_name()) {
            // Ensure that current user is allowed to run this action
            // Validate user permission level
            $user_role = $this->get_user_role();
            if (in_array($user_role, $this->actions[$this->action]["roles"])) {
                $this->run_action();
            } else {
                $this->write_log(
                    "User in role '{$user_role}' is denied to run action '{$this->action}'",
                    DL_WARNING
                );
                $this->run_access_denied_action();
            }
        } else {
            $this->write_log(
                "Invalid action!",
                DL_WARNING
            );
            $this->run_invalid_action_name_action();
        }

        if (is_null($this->response)) {
            $this->create_html_document_response();
        }

        $this->response->send();

        $exec_time_str = number_format(
            get_microtime_as_double() - get_microtime_as_double(_APP_START_MICROTIME),
            6
        );
        $this->write_log(
            "App '{$this->app_name}' finished (in {$exec_time_str} sec)",
            DL_INFO
        );
    }

    function create_current_user() {
        return null;
    }

    function is_valid_action_name() {
        return isset($this->actions[$this->action]);
    }

    function get_user_role($user = null) {
        // Return user role (string) for selecting allowed actions
        // for previously created user by function create_current_user()
        return "guest";
    }

    function get_default_action_name($user = null) {
        return "pg_index";
    }

    function on_before_validate_action() {
        $this->print_raw_value("global:html_charset", $this->html_charset);

        $this->print_lang_resources();
    }

    function print_lang_resources() {
        foreach ($this->lang_resources->_params as $resource_name => $resource_value) {
            $this->print_raw_value("lang:{$resource_name}", $resource_value);
        }
        $this->print_raw_value("global:lang", $this->lang);
    }
    
    function run_action($action_name = null, $action_params = array()) {
        // Run action and return its response
        if (!is_null($action_name)) {
            $this->action = $action_name;
        }
        $this->action_params = $action_params;
        $page_name = get_param_value($action_params, "page", null);
        if (is_null($page_name)) {
            $page_name = trim(param("page"));
        }

        $action_func_name = "action_{$this->action}";
        $action_name_expanded = ($page_name == "") ?
            $this->action :
            "{$this->action}_{$page_name}";
        
        $this->print_values(array(
            "action" => $this->action,
            "action_expanded" => $action_name_expanded,
            "page" => $page_name,
        ));
        $this->action = $action_name_expanded;

        $this->on_before_run_action();

        $this->write_log(
            "Running action '{$this->action}'",
            DL_INFO
        );

        $this->{$action_func_name}();  // NB! Variable function
        
        $this->on_after_run_action();
    }

    function on_before_run_action() {
        $this->popup = (int) param("popup");
        $this->print_custom_param("popup", $this->popup);

        $this->report = (int) param("report");
        $this->print_custom_param("report", $this->report);
        
        $this->printable = (int) param("printable");
        $this->print_custom_param("printable", $this->printable);

        if (!$this->report && !$this->printable) {
            $this->print_session_status_messages();
        }

        $this->print_page_titles();
    }

    function on_after_run_action() {
    }

    function run_access_denied_action() {
        $this->create_access_denied_html_document_response();
    }

    function run_invalid_action_name_action() {
        $this->action = $this->get_default_action_name();
        $this->write_log(
            "Validating default action '{$this->action}'",
            DL_INFO
        );
        if ($this->is_valid_action_name()) {
            $this->create_self_redirect_response(array("action" => $this->action));
        } else {
            $this->process_fatal_error(
                "Default action '{$this->action}' is invalid!"
            );
        }
    }
//
    function get_http_auth_user_role() {
        $login = $this->get_config_value("admin_login");
        $password = $this->get_config_value("admin_password");
        return ($this->is_valid_http_auth_user($login, $password)) ? "admin" : "guest";
    }

    function is_valid_http_auth_user($login, $password) {
        return
            (isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])) &&
            $_SERVER["PHP_AUTH_USER"] == $login &&
            $_SERVER["PHP_AUTH_PW"] == $password;
    }
//
    // HTTP response creation helpers
    function create_redirect_response($url) {
        $this->response = new RedirectResponse($url);
        $this->write_log(
            "Redirecting to {$url}",
            DL_INFO
        );
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

    function create_binary_content_response($content, $filename = null) {
        $this->response = new BinaryContentResponse(
            "application/octet-stream",
            $content
        );
        if (!is_null($filename)) {
            $this->response->add_content_disposition_header($filename, true);
        }
    }

    function create_binary_stream_response($stream, $stream_type, $filename = null) {
        $this->response = new BinaryStreamResponse(
            "application/octet-stream",
            $stream,
            $stream_type
        );
        if (!is_null($filename)) {
            $this->response->add_content_disposition_header($filename, true);
        }
    }

    function create_html_document_response() {
        $this->create_html_page_template_name();
        $this->response = new HtmlDocumentResponse(
            $this->create_html_document_body_content(),
            $this->html_charset
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

    function create_http_not_found_response() {
        $this->response = new HttpResponse();
        $this->response->add_header(new HttpHeader("HTTP/1.0 404 Not Found"));
    }

    function create_http_not_modified_response() {
        $this->response = new HttpResponse();
        $this->response->add_header(new HttpHeader("HTTP/1.1 304 Not Modified"));
    }

    function create_xml_document_response($content) {
        $this->response = new XmlDocumentResponse($content);
    }

    function create_plain_text_document_response(
        $content,
        $filename = null,
        $open_inline = true
    ) {
        $this->response = new PlainTextDocumentResponse($content, $filename, $open_inline);
    }

    function create_pdf_document_response(
        $content,
        $filename = null,
        $open_inline = true
    ) {
        $this->response = new PdfDocumentResponse($content, $filename, $open_inline);
    }
//
    // Date & Time format and formatting
    function get_app_datetime_format() {
        return $this->get_config_value("app_datetime_format");
    }

    function get_app_short_datetime_format() {
        return $this->get_config_value("app_short_datetime_format");
    }

    function get_app_date_format() {
        return $this->get_config_value("app_date_format");
    }

    function get_app_time_format() {
        return $this->get_config_value("app_time_format");
    }

    function get_app_short_time_format() {
        return $this->get_config_value("app_short_time_format");
    }

    function get_db_datetime_format() {
        return $this->get_config_value("db_datetime_format");
    }

    function get_db_date_format() {
        return $this->get_config_value("db_date_format");
    }

    function get_db_time_format() {
        return $this->get_config_value("db_time_format");
    }
//
    function get_app_datetime($db_datetime, $date_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_datetime_format(), $db_datetime);
        return create_date_by_format(
            $this->get_app_datetime_format(),
            $date_parts,
            $date_if_unknown
        );
    }

    function get_app_short_datetime($db_datetime, $date_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_datetime_format(), $db_datetime);
        return create_date_by_format(
            $this->get_app_short_datetime_format(),
            $date_parts,
            $date_if_unknown
        );
    }

    function get_app_date($db_date, $date_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_date_format(), $db_date);
        return create_date_by_format(
            $this->get_app_date_format(),
            $date_parts,
            $date_if_unknown
        );
    }

    function get_app_time($db_time, $date_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_time_format(), $db_time);
        return create_date_by_format(
            $this->get_app_time_format(),
            $date_parts,
            $date_if_unknown
        );
    }

    function get_app_short_time($db_time, $date_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_time_format(), $db_time);
        return create_date_by_format(
            $this->get_app_short_time_format(),
            $date_parts,
            $date_if_unknown
        );
    }

    function get_db_datetime($app_datetime, $date_if_unknown = "0000-00-00 00:00:00") {
        $date_parts = parse_date_by_format($this->get_app_datetime_format(), $app_datetime);
        return create_date_by_format(
            $this->get_db_datetime_format(),
            $date_parts,
            $date_if_unknown
        );
    }

    function get_db_date($app_date, $date_if_unknown = "0000-00-00") {
        $date_parts = parse_date_by_format($this->get_app_date_format(), $app_date);
        return create_date_by_format(
            $this->get_db_date_format(),
            $date_parts,
            $date_if_unknown
        );
    }

    function get_db_time($app_time, $date_if_unknown = "00:00:00") {
        $date_parts = parse_date_by_format($this->get_app_time_format(), $app_time);
        return create_date_by_format(
            $this->get_db_time_format(),
            $date_parts,
            $date_if_unknown
        );
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
            parse_date_by_format($this->get_db_datetime_format(), $db_datetime)
        );
    }

    function get_timestamp_from_db_date($db_date) {
        return get_timestamp_from_date_parts(
            parse_date_by_format($this->get_db_date_format(), $db_date)
        );
    }
//
    // Integer, double and currency formatting
    function get_app_integer_value($php_integer_value) {
        return format_integer_value($php_integer_value, ",");
    }

    function get_php_integer_value($app_integer_value) {
        $result = str_replace(",", "", $app_integer_value);
        return (is_php_number($result)) ? (int) $result : 0;
    }

    function get_app_double_value($php_double_value, $decimals) {
        return format_double_value($php_double_value, $decimals, ".", ",");
    }

    function get_php_double_value($app_double_value) {
        $result = str_replace(",", "", $app_double_value);
        return (is_php_number($result)) ? (double) $result : 0.0;
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
    // Template printing functions
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

    function print_custom_params($custom_params) {
        if (!is_null($custom_params)) {
            foreach ($custom_params as $param_name => $param_value) {
                if (is_scalar($param_value)) {
                    $this->print_custom_param($param_name, $param_value);
                }
            }
        }
    }

    function print_custom_param($param_name, $param_value) {
        $this->print_value($param_name, $param_value);
        $this->print_raw_value("{$param_name}_orig", $param_value);
        $this->print_value(
            "{$param_name}_suburl",
            create_suburl(array($param_name => $param_value))
        );
        $this->print_hidden_input_form_value($param_name, $param_value);
    }

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
    // Complex types template printing functions
    function print_primary_key_value($template_var, $value) {
        $this->print_raw_value($template_var, $value);
    }

    function print_foreign_key_value($template_var, $value) {
        $this->print_raw_value($template_var, $value);
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
            $template_var => $value_formatted,
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
            $template_var => $value_formatted,
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
            $template_var => $value_formatted,
            "{$template_var}_without_sign" => $value_formatted_without_sign,
            "{$template_var}_orig" => $value,
            "{$template_var}_orig_with_nonset" => $value_with_nonset,
        ));
    }

    function print_boolean_value($template_var, $value, $value_caption_pairs = null) {
        if (is_null($value_caption_pairs)) {
            $value_caption_pairs = array(
                array(0, $this->get_lang_str("no")),
                array(1, $this->get_lang_str("yes")),
            );
        }
        $caption = get_caption_by_value_from_value_caption_pairs(
            $value_caption_pairs,
            ((int) $value != 0) ? 1 : 0
        );
        $this->print_value($template_var, $caption);
        $this->print_raw_value("{$template_var}_orig", $value);
    }

    function print_enum_value($template_var, $enum_value, $value_caption_pairs) {
        $enum_value = get_actual_current_value($value_caption_pairs, $enum_value);
        $enum_caption = get_caption_by_value_from_value_caption_pairs(
            $value_caption_pairs,
            $enum_value
        );
        $enum_caption = is_null($enum_caption) ? "" : $enum_caption;
        $this->print_value($template_var, $enum_caption);
        $this->print_raw_value("{$template_var}_caption_orig", $enum_caption);
        $this->print_raw_value("{$template_var}_orig", $enum_value);
    }

    function print_varchar_value($template_var, $value) {
        $this->print_value($template_var, $value);
        $this->print_raw_value("{$template_var}_orig", $value);
    }

    function print_text_value($template_var, $value) {
        $this->print_varchar_value($template_var, $value);
        $safe_value = get_html_safe_string($value);
        $this->print_raw_value("{$template_var}_lf2br", convert_lf2br($safe_value));
    }

    function print_datetime_value($template_var, $db_datetime) {
        $this->print_values(array(
            $template_var => $this->get_app_datetime($db_datetime),
            "{$template_var}_short" => $this->get_app_short_datetime($db_datetime),
            "{$template_var}_orig" => $db_datetime,
        ));
    }

    function print_date_value($template_var, $db_date) {
        $this->print_values(array(
            $template_var => $this->get_app_date($db_date),
            "{$template_var}_orig" => $db_date,
        ));
    }

    function print_time_value($template_var, $db_time) {
        $this->print_values(array(
            $template_var => $this->get_app_time($db_time),
            "{$template_var}_short" => $this->get_app_short_time($db_time),
            "{$template_var}_orig" => $db_time,
        ));
    }
//
    // Form values template printing functions
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
        return $this->print_checkbox_input_form_value(
            $template_var,
            1,
            ($value != 0),
            $input_attrs
        );
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
                array_merge(
                    array("class" => "varchar_normal"),
                    $input_attrs
                )
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
            $lang_str = $this->get_lang_str($lang);
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

    function print_checkbox_input_form_value(
        $template_var,
        $value,
        $checked = null,
        $input_attrs = array()
    ) {
        $printed_value =
            print_html_hidden("{$template_var}__sent", 1) .
            print_html_checkbox($template_var, $value, $checked, $input_attrs);
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

        $printed_value = print_html_select(
            $template_var,
            $value_caption_pairs,
            $value,
            $input_attrs
        );
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
        case "db_object_query":
            $data_info = $values_info["data"];
            
            $obj = $data_info["obj"];
            $query_ex = get_param_value($data_info, "query_ex", array());
            $values_field_name = get_param_value($data_info, "values_field_name", "id");
            $captions_field_name = get_param_value($data_info, "captions_field_name", "name");

            $value_caption_pairs = $this->get_value_caption_pairs_from_db_object_query(
                $obj,
                $query_ex,
                $values_field_name,
                $captions_field_name
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

    function get_value_caption_pairs_from_db_object_query(
        $obj,
        $query_ex,
        $values_field_name,
        $captions_field_name
    ) {
        if (is_string($obj)) {
            $obj =& $this->create_db_object($obj);
        }
        $query = $obj->get_expanded_select_query(
            $query_ex,
            array(
                $values_field_name,
                $captions_field_name,
            )
        );
        return $this->get_value_caption_pairs_from_query(
            $query,
            $values_field_name,
            $captions_field_name
        );
    }

    function get_value_caption_pairs_from_query(
        $query,
        $values_field_name,
        $captions_field_name
    ) {
        $rows = $this->fetch_rows_list($query);
        $value_caption_pairs = array();
        foreach ($rows as $row) {
            $value_caption_pairs[] = array(
                $row[$values_field_name],
                $row[$captions_field_name]
            );
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

        $query_ex = new SelectQueryEx();
        $query_ex->expand($dependent_query_ex);
        $query_ex->expand($dependency_query_ex);

        $main_select_values = get_values_from_value_caption_pairs($main_select_value_caption_pairs);
        
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
            
            $dependent_select_value_caption_pairs = $this->get_value_caption_pairs_from_source(
                $dependent_values_info
            );
            $dependent_select_value_caption_pairs = $this->expand_value_caption_pairs_with_begin_end(
                $dependent_select_value_caption_pairs,
                $dependent_values_info["data"]
            );
            $dependent_select_value_caption_pairs = $this->expand_value_caption_pairs_with_nonset(
                $dependent_select_value_caption_pairs,
                $dependent_values_info["data"]
            );

            $dependency_array[] = get_values_from_value_caption_pairs(
                $dependent_select_value_caption_pairs
            );
        }
        return $dependency_array;
    }
//
    // Language resources functions
    function get_lang_str($resource, $resource_params = null) {
        $lang_str = $this->lang_resources->get_value($resource);
        if (is_null($lang_str)) {
            return null;        
        }
        if (!is_null($resource_params)) {
            $this->print_values($resource_params);
            $lang_str = $this->page->get_parsed_text($lang_str);
        }
        return $lang_str;
    }

    function get_current_lang() {
        $cur_lang = $this->get_current_lang_from_session();
        if (!$this->is_valid_lang($cur_lang)) {
            $cur_lang = $this->dlang;
        }
        return $cur_lang;
    }

    function get_current_lang_from_session() {
        return (is_null($this->session)) ?
            null :
            $this->session->get_param("current_lang");
    }

    function set_current_lang($new_lang) {
        if ($this->is_valid_lang($new_lang)) {
            $this->session->set_param("current_lang", $new_lang);
            $this->lang = $new_lang;
        }
    }

    function get_avail_langs() {
        return explode(",", $this->get_config_value("languages"));
    }

    function is_valid_lang($lang) {
        if (is_null($lang) || !in_array($lang, $this->avail_langs)) {
            return false;
        } else {
            return true;
        }
    }
//
    // Page construction helper functions

    // Page titles
    function print_page_titles() {
        $this->print_head_and_page_titles($this->get_default_page_title_lang_resource());
    }

    function print_head_and_page_titles($resource) {
        $this->print_raw_value("page_title_lang_resource", $resource);
        $this->print_page_title($resource);
        $this->print_head_page_title($resource);
    }

    function get_default_page_title_lang_resource() {
        return "page_title_{$this->action}";
    }

    function print_page_title($resource, $is_html = false) {
        $page_title_text = $this->get_lang_str($resource);
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
        $resource_text = $this->get_lang_str("head_{$resource}");
        if (is_null($resource_text)) {
            // If have no head_page_title use page_title instead
            $resource_text = $this->get_lang_str($resource);
        }
        if (!is_null($resource_text)) {
            $this->print_raw_value("head_page_title", $resource_text);
        }
    }

    // Status messages
    function print_status_message($message) {
        $status_messages =& $this->create_object(
            "StatusMessages",
            array(
                "templates_dir" => "_status_messages",
                "template_var" => "status_messages",
            )
        );
        if (is_array($message)) {
            $messages = $message;
            foreach ($messages as $message) {
                $status_messages->add($message);
            }
        } else {
            $status_messages->add($message);
        }
        $status_messages->print_values();
    }

    function print_status_messages($messages) {
        $this->print_status_message($messages);
    }

    function print_status_message_db_object_updated($obj) {
        $action_done = ($obj->is_definite()) ? "updated" : "added";
        $this->add_session_status_message(new OkStatusMsg("{$obj->_table_name}_{$action_done}"));
    }
        
    function print_status_message_db_object_deleted($obj) {
        $this->add_session_status_message(new OkStatusMsg("{$obj->_table_name}_deleted"));
    }

    function print_status_messages_cannot_delete_db_object($messages) {
        foreach ($messages as $message) {
            $this->add_session_status_message($message);
        }
    }

    function print_session_status_messages() {
        $messages = $this->get_and_delete_session_status_messages();
        if (count($messages) != 0) {
            $this->print_status_messages($messages);
        }
    }

    function get_and_delete_session_status_messages() {
        if (!$this->session->has_param("status_messages")) {
            return array();
        } else {
            $messages = $this->session->get_param("status_messages");
            $this->session->unset_param("status_messages");
            return $messages;
        }
    }

    function add_session_status_message($message) {
        if ($this->session->has_param("status_messages")) {
            $messages = $this->session->get_param("status_messages");
        } else {
            $messages = array();
        }
        $messages = array_merge($messages, array($message));
        $this->session->set_param("status_messages", $messages);
    }

    // Static page
    function print_static_page($page_name, $template_var) {
        $full_page_name = "{$page_name}_{$this->lang}";
        if (!$this->is_static_page_file_exist($full_page_name)) {
            $full_page_name = $page_name;
            if (!$this->is_static_page_file_exist($full_page_name)) {
                $this->print_raw_value($template_var, "");
                return "";
            }
        }
        return $this->print_static_page_file($full_page_name, $template_var);
    }

    function print_static_page_file($page_name, $template_var) {
        return $this->print_file("static/{$page_name}.html", $template_var);
    }

    function is_static_page_file_exist($page_name) {
        return $this->is_file_exist("static/{$page_name}.html");
    }

    // Main menu
    function print_menu($params = array()) {
        $menu =& $this->create_menu($params);
        
        $menu->load_from_xml(get_param_value($params, "xml_filename", "menu.xml"));
        $menu->select_items_by_context(get_param_value($params, "context", $this->action));
        
        return $menu->print_values();
    }

    function &create_menu($params = array()) {
        return $this->create_object(
            "Menu",
            array(
                "templates_dir" => get_param_value($params, "templates_dir", "_menu"),
                "template_var" => get_param_value($params, "template_var", "menu"),
            )
        );
    }

    // Lang menu
    function print_lang_menu($params = array()) {
        $lang_menu = $this->create_lang_menu($params);

        $lang_menu->avail_langs = $this->get_avail_langs();
        $lang_menu->current_lang = $this->lang;

        return $lang_menu->print_values();
    }

    function &create_lang_menu($params = array()) {
        return $this->create_object(
            "LangMenu",
            array(
                "templates_dir" => get_param_value($params, "templates_dir", "_lang_menu"),
                "template_var" => get_param_value($params, "template_var", "lang_menu"),
            )
        );
    }
//
    // Common actions
    function action_change_lang() {
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

    function action_pg_static() {
        $page_name = trim(param("page"));
        if (preg_match('/^\w+$/i', $page_name)) {
            $this->print_static_page($page_name, "body");
        }
    }

    function action_get_image() {
        $image = $this->read_id_fetch_db_object("Image");
        if ($image->is_definite()) {
            $is_attachment = (bool) param("attachment");
            if ($image->was_updated_since_last_browser_request()) {
                $this->response = new FileResponse(
                    $image->create_in_memory_image(),
                    $image->filename,
                    $image->get_updated_as_gmt_str(),
                    $is_attachment
                );
            } else {
                $this->create_http_not_modified_response();
            }
        } else {
            $this->create_http_not_found_response();
        }
    }

    function action_get_file() {
        $file = $this->read_id_fetch_db_object("File");
        if ($file->is_definite()) {
            $is_attachment = (bool) param("attachment");
            if ($file->was_updated_since_last_browser_request()) {
                $this->response = new FileResponse(
                    $file->create_in_memory_file(),
                    $file->filename,
                    $file->get_updated_as_gmt_str(),
                    $is_attachment
                );
            } else {
                $this->create_http_not_modified_response();
            }
        } else {
            $this->create_http_not_found_response();
        }
    }

    // Setup app actions
    function action_create_update_tables() {
        $this->process_create_update_tables();
        $this->add_session_status_message(new OkStatusMsg("tables_updated"));
        $this->create_self_redirect_response();
    }

    function action_delete_tables() {
        $this->process_delete_tables();
        $this->add_session_status_message(new OkStatusMsg("tables_deleted"));
        $this->create_self_redirect_response();
    }
//
    function action_pg_tables_dump() {
        $actual_table_names = $this->db->get_actual_table_names(true, false);
        foreach ($actual_table_names as $actual_table_name) {
            $this->print_varchar_value("table_name", $actual_table_name);
            $this->print_file("tables_dump/tables_list/form_item.html", "form_items");
        }
        $this->print_file("tables_dump/tables_list/form.html", "body");
    }

    function action_pg_tables_dump_url() {
        $table_names = param("table_names");
        $table_names_str = join(" ", $table_names);
        
        $this->print_value("table_names_str", $table_names_str);

        $url = create_self_full_url(array(
            "action" => "pg_tables_dump_view",
            "table_names" => $table_names_str,
        ));
        $this->print_value("view_dump_url", $url);

        $url = create_self_full_url(array(
            "action" => "download_tables_dump",
            "table_names" => $table_names_str,
        ));
        $this->print_value("download_dump_url", $url);

        $this->print_file("tables_dump/url/body.html", "body");
    }

    function action_pg_tables_dump_view() {
        $stream = $this->_create_tables_dump_stream(param("table_names"), false);
        if ($stream === false) {
            $this->create_self_redirect_response(array("action" => "pg_tables_dump"));
        } else {
            $dump_text = stream_get_contents($stream);
            pclose($stream);
            if ($dump_text === false) {
                $dump_text = $this->get_lang_str("dump_creation_failed");
            }
            $n_dump_lines = substr_count($dump_text, "\n") + 1;
            $this->print_value("dump_text", $dump_text);
            $this->print_integer_value("n_dump_lines", $n_dump_lines);
            $this->print_file("tables_dump/dump_text/body.html", "body");
        }
    }

    function action_download_tables_dump() {
        $stream = $this->_create_tables_dump_stream(param("table_names"), true);
        if ($stream === false) {
            $this->create_self_redirect_response(array("action" => "pg_tables_dump"));
        } else {
            $now_date_str = $this->get_db_now_date();
            $this->create_binary_stream_response(
                $stream,
                "process",
                "dump-{$now_date_str}.sql.gz"
            );
        }
    }

    function _create_tables_dump_stream($table_names_str, $should_compress) {
        $host = $this->db->get_host();
        $database = $this->db->get_database();
        $username = $this->db->get_username();
        $password = $this->db->get_password();
        $compress_subcmdline = ($should_compress) ? " | gzip" : "";
        $cmdline =
            "mysqldump --add-drop-table -u{$username} -p{$password} -h{$host} {$database} " .
            "{$table_names_str}{$compress_subcmdline}";
        $this->write_log(
            "Creating DB dump:\n" .
            "Commandline: {$cmdline}",
            DL_INFO
        );
        $stream = popen($cmdline, "rb");
        if ($stream === false) {
            $this->write_log(
                "DB dump stream creation failed! Couldn't run commandline:\n" .
                "{$cmdline}\n",
                DL_ERROR
            );
        }
        return $stream;
    }

    // App objects creation functions
    function &create_object($obj_class_name, $obj_params = array()) {
        global $app_classes;

        return $this->_create_object(
            $obj_class_name,
            "",
            $app_classes["classes"],
            $app_classes["class_paths"],
            $obj_params
        );
    }

    function &create_db_object($obj_class_name, $obj_params = array()) {
        global $db_classes;

        return $this->_create_object(
            $obj_class_name,
            "Table",
            $db_classes["classes"],
            $db_classes["class_paths"],
            $obj_params
        );
    }

    function &_create_object(
        $class_name_without_suffix,
        $class_name_suffix,
        $classes_info,
        $class_paths,
        $obj_params = array()
    ) {
        $class_info = get_param_value($classes_info, $class_name_without_suffix, null);
        $class_name = "{$class_name_without_suffix}{$class_name_suffix}";
        if ($class_name_suffix != "") {
            $with_suffix_str = " with suffix '{$class_name_suffix}'";
        }
        if (is_null($class_info)) {
            $this->process_fatal_error(
                "Cannot find info about class '{$class_name_without_suffix}'{$with_suffix_str}!"
            );
        }
        
        if (!class_exists($class_name)) {
            if (!$this->_load_class($class_name_without_suffix, $classes_info, $class_paths)) {
                $this->process_fatal_error(
                    "Cannot load class '{$class_name_without_suffix}'{$with_suffix_str}!"
                );
            }
        }
        
        $obj =& new $class_name();
        if (is_subclass_of($obj, "Object")) {
            $obj->set_class_name($class_name_without_suffix, $class_name_suffix);
            $init_obj_params = get_param_value($class_info, "params", null);
            if (!is_null($init_obj_params)) {
                $obj_params = $init_obj_params + $obj_params;
            }
            if (is_subclass_of($obj, "AppObject")) {
                $obj->set_app(&$this);
            }
        }
        if (method_exists($obj, "_init")) {
            $obj->_init($obj_params);
        }
        
        return $obj;
    }

    function _load_class(
        $class_name,
        $classes_info,
        $class_paths
    ) {
        $class_info = get_param_value($classes_info, $class_name, null);
        if (is_null($class_info)) {
            $this->process_fatal_error(
                "Cannot find info about class '{$class_name}'!"
            );
        }

        $required_classes = get_param_value($class_info, "required_classes", array());
        foreach ($required_classes as $required_class_name) {
            if (!$this->_load_class(
                $required_class_name,
                $classes_info,
                $class_paths
            )) {
                return false;
            }
        }

        foreach ($class_paths as $class_dir) {
            $class_filename = $class_info["filename"];
            $class_full_filename = "{$class_dir}/{$class_filename}";
            if (is_file($class_full_filename)) {
                require_once($class_full_filename);
                return true;
            }
        }
        
        return false;
    }
    
    // Action helper functions
    function fetch_db_object(
        $obj,
        $obj_id,
        $where_str = "1",
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        // If object is given by its class name then create instance here
        // If no then instance should be created somewhere outside and passed to this function
        // In that case object could be expanded with new fields (using insert_field())
        if (is_string($obj)) {
            $obj =& $this->create_db_object($obj);
        }
        $obj->fetch(
            "{$obj->_table_name}.id = {$obj_id} AND {$where_str}",
            $field_names_to_select,
            $field_names_to_not_select
        );
        return $obj;
    }

    // Create new object, read it's PRIMARY KEY from CGI
    // (using CGI variable with name $id_param_name),
    // then fetch object from db table
    function read_id_fetch_db_object(
        $obj_class_name,
        $where_str = "1",
        $id_param_name = null,
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        $obj =& $this->create_db_object($obj_class_name);
        $obj->read(array("id"));
        return $this->fetch_db_object(
            $obj,
            $obj->id,
            $where_str,
            $field_names_to_select,
            $field_names_to_not_select
        );
    }

    function fetch_db_objects_list(
        $obj,
        $query_ex,
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        if (is_string($obj)) {
            $obj =& $this->create_db_object($obj);
        }
        $res = $obj->run_expanded_select_query(
            $query_ex,
            $field_names_to_select,
            $field_names_to_not_select
        );
        $objects = array();
        while ($row = $res->fetch_next_row_to_db_object($obj)) {
            $objects[] = $obj;
        }
        return $objects;
    }

    function fetch_rows_list($query) {
        $res = $this->db->run_select_query($query);
        $rows = array();
        while ($row = $res->fetch_next_row()) {
            $rows[] = $row;
        }
        return $rows;
    }
//
    function process_create_update_tables() {
        $actual_table_names = $this->db->get_actual_table_names(false, false);
        $all_creatable_db_objects_info = $this->get_all_creatable_db_objects_info();
        $all_table_names_to_create = array_keys($all_creatable_db_objects_info);

        $table_names_to_create = array_diff($all_table_names_to_create, $actual_table_names);
        $table_names_to_update = array_intersect($all_table_names_to_create, $actual_table_names);
        $table_names_to_drop = array_diff($actual_table_names, $all_table_names_to_create);

        foreach ($table_names_to_create as $table_name) {
            $obj =& $this->create_db_object($all_creatable_db_objects_info[$table_name]);
            $obj->create_table();
        }

        foreach ($table_names_to_update as $table_name) {
            $obj =& $this->create_db_object($all_creatable_db_objects_info[$table_name]);
            $obj->update_table();
        }

        $this->process_delete_tables($table_names_to_drop);
    }

    function process_delete_tables($table_names_to_drop = null) {
        if (is_null($table_names_to_drop)) {
            $table_names_to_drop = $this->db->get_actual_table_names(false, false);
        }
        foreach ($table_names_to_drop as $table_name) {
            $this->db->run_drop_table_query($table_name);
        }
    }

    function get_all_creatable_db_objects_info() {
        global $db_classes;

        $db_objects_info = array();
        foreach ($db_classes["classes"] as $obj_class_name => $obj_class_info) {
            $obj_class_params = get_param_value($obj_class_info, "params", null);
            if (is_null($obj_class_params)) {
                continue;
            }
            $should_create = get_param_value($obj_class_params, "create", null);
            if (is_null($should_create) || !$should_create) {
                continue;
            }
            $obj_table_name = get_param_value($obj_class_params, "table_name", null);
            if (is_null($obj_table_name)) {
                continue;
            }
            $db_objects_info[$obj_table_name] = $obj_class_name;
        }
        return $db_objects_info;
    }

    // Uploaded image
    function process_uploaded_image(
        &$obj,
        $image_id_field_name,
        $input_name,
        $params = array()
    ) {
        if (!was_file_uploaded($input_name)) {
            return true;
        }

        $uploaded_image =& $this->create_object(
            "UploadedImage", 
            array(
                "input_name" => $input_name,
            )
        );
        
        $obj_image = $obj->fetch_image_without_content($image_id_field_name);

        $image_processor_class_name = get_param_value($params, "image_processor.class", null);
        if (!is_null($image_processor_class_name)) {
            $image_processor_params = array(
                "actions" => get_param_value($params, "image_processor.actions", array()),
            );
            $image_processor =& $this->create_object(
                $image_processor_class_name,
                $image_processor_params
            );
            if (!$image_processor->process($uploaded_image)) {
                return false;
            }
        }

        $obj_image->filename = $uploaded_image->get_orig_filename();
        $obj_image->set_image_fields_from($uploaded_image);
        
        if (!$obj_image->is_definite()) {
            $obj_image->is_thumbnail = get_param_value($params, "is_thumbnail", 0);
        }

        $obj_image->save();

        $obj->set_field_value($image_id_field_name, $obj_image->id);
        
        if (!is_null($image_processor_class_name)) {
            $image_processor->cleanup();
        }
        
        return true;
    }

    // Uploaded file
    function process_uploaded_file(
        &$obj,
        $file_id_field_name,
        $input_name,
        $params = array()
    ) {
        if (!was_file_uploaded($input_name)) {
            return true;
        }
    
        $uploaded_file =& $this->create_object(
            "UploadedFile", 
            array(
                "input_name" => $input_name,
            )
        );
        
        $obj_file = $obj->fetch_file_without_content($file_id_field_name);

        $obj_file->filename = $uploaded_file->get_orig_filename();
        $obj_file->set_file_fields_from($uploaded_file);
        
        $obj_file->save();

        $obj->set_field_value($file_id_field_name, $obj_file->id);
        
        return true;

//    function process_file_upload($file_id_field_name, $input_name) {
//        $file = $this->fetch_file_without_content($file_id_field_name);
//
//        $file->read_uploaded_info($input_name);
//        $file->save();
//
//        $this->set_field_value($file_id_field_name, $file->id);
//    }
    }

    function delete_db_object($params = array()) {
        $obj = get_param_value($params, "obj", null);
        if (is_null($obj)) {
            $this->process_fatal_error_required_param_not_found("obj", "delete_db_object()");
        }

        $success_url_params = get_param_value($params, "success_url_params", null);
        if (is_null($success_url_params)) {
            $this->process_fatal_error_required_param_not_found(
                "success_url_params",
                "delete_db_object()"
            );
        }

        $error_url_params = get_param_value($params, "error_url_params", null);
        if (is_null($error_url_params)) {
            $this->process_fatal_error_required_param_not_found(
                "error_url_params",
                "delete_db_object()"
            );
        }
        $del_cascade = get_param_value($params, "cascade", false);

        if ($del_cascade) {
            $obj->del_cascade();
        } else {
            $messages = $obj->check_restrict_relations_before_delete();
            
            if (count($messages) != 0) {
                $this->print_status_messages_cannot_delete_db_object($messages);
                $this->create_self_redirect_response($error_url_params);
                return;
            } else {
                $obj->del();
            }
        }
        $this->print_status_message_db_object_deleted($obj);
        $this->create_self_redirect_response($success_url_params);
    }

    function delete_db_object_image($obj, $image_id_field_name, $delete_thumbnail = true) {
        if ($obj->is_definite() && $obj->is_field_exist($image_id_field_name)) {
            $field_names_to_update = array($image_id_field_name);

            $obj->del_image($image_id_field_name);
            $obj->set_field_value($image_id_field_name, 0);

            $thumbnail_image_id_field_name = "thumbnail_{$image_id_field_name}";
            if ($delete_thumbnail && $obj->is_field_exist($thumbnail_image_id_field_name)) {
                $field_names_to_update[] = $thumbnail_image_id_field_name;

                $obj->del_image($thumbnail_image_id_field_name);
                $obj->set_field_value($thumbnail_image_id_field_name, 0);
            }

            $obj->update($field_names_to_update);
        }
    }

    function delete_db_object_file($obj, $file_id_field_name) {
        if ($obj->is_definite() && $obj->is_field_exist($file_id_field_name)) {
            $field_names_to_update = array($file_id_field_name);

            $obj->del_file($file_id_field_name);
            $obj->{$file_id_field_name} = 0;

            $obj->update($field_names_to_update);
        }
    }

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

    // Email sender
    function &create_email_sender() {
        $email_sender =& $this->create_object("PHPMailer");
        $email_sender->IsSendmail();
        $email_sender->IsHTML($this->get_config_value("email_is_html"));
        $email_sender->CharSet = $this->get_config_value("email_charset");
        return $email_sender;
    }

    function get_actual_email_to($email_to) {
        return $this->get_config_value("email_debug_mode") ?
            $this->get_config_value("admin_email_to") :
            $email_to;
    }

}

?>