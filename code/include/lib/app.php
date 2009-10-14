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
    var $page;
    var $page_template_name;
    var $html_charset;
    
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
    var $use_cur_lang_from_cgi; // True if app reads current language from CGI with mod_rewrite

    // Action vars
    var $actions;
    var $action;
    var $action_params;

    function App($app_class_name, $app_name) {
        parent::AppObject();

        $this->set_class_name($app_class_name); 
        $this->app_name = $app_name;

        $this->use_cur_lang_from_cgi = false;

        // One action defined, but nobody can access it
        $this->actions = array(
            "index" => array("roles" => array())
        );
    }
//
    function init() {
        $this->set_app();
        $this->create_core_objects();
        
        $this->html_charset = $this->get_config_value("html_charset");
        $this->init_lang_vars();
    }

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
        $sql_config =& new Config();
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
        $this->page =& $this->create_object(
            "Template",
            array(
                "templates_dir" => $this->get_page_templates_dir(),
                "is_verbose" => $this->get_config_value("template.is_verbose"),
            )
        );
        $this->page_template_name = "";
    }

    function get_page_templates_dir() {
        return "templates";
    }

    function init_sys_var($name, $value) {
        $this->{$name} = $value;
        $this->print_raw_value("sys:{$name}", $value);
    }

    function init_lang_vars() {
        $this->avail_langs = $this->get_avail_langs();
        $this->dlang = $this->get_config_value("default_language");
        $this->lang = $this->get_current_lang();

        // Sentry redirect
        if ($this->use_cur_lang_from_cgi) {
            if (is_null($this->get_current_lang_from_cgi())) {
                $redirect_response =& new RedirectResponse(
                    create_self_full_url(
                        array(),
                        $this->lang
                    )
                );
                $redirect_response->send();
                exit;
            }
        }

        $this->init_lang_resources();
        $this->init_page_template_lang_resources();
    }

    function init_lang_resources() {
        $this->lang_resources = array_merge(
            require("lang/default.php"),
            require("lang/{$this->lang}.php")
        );
    }

    function init_page_template_lang_resources() {
        $this->page->init_fillings();
        $this->print_raw_value("sys:html_charset", $this->html_charset);
        $this->print_raw_value("sys:lang", $this->lang);
        $this->print_raw_value("sys:self_path", create_self_path());
        $this->print_raw_value("sys:self_url", create_self_url());
        $this->print_raw_value("sys:self_full_url", create_self_full_url());
        $this->print_raw_value(
            "sys:self_full_lang_url",
            create_self_full_url(array(), $this->use_cur_lang_from_cgi ? $this->lang : null)
        );
    }
//
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
        $with_suffix_str = ($class_name_suffix == "") ? "" : " with suffix '{$class_name_suffix}'";
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
                $obj->set_app();
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
//
    // App main entry point
    function run() {
        $this->write_log(
            "App '{$this->app_name}' started",
            DL_INFO
        );

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
        return "index";
    }

    function get_action_lang_resource() {
        $resource = $this->action;
        if ($this->action == "static") {
            $page_name = $this->read_static_page_name();
            if ($page_name != "") {
                $resource = "{$this->action}.{$page_name}";
            }
        }
        return $resource;
    }

    function on_before_validate_action() {
    }

    function run_action($action_name = null, $action_params = array()) {
        // If optional action name was given then use it else default one is used
        if (!is_null($action_name)) {
            $this->action = $action_name;
        }
        $this->action_params = $action_params;

        $this->print_value("action", $this->action);

        $this->on_before_run_action();

        $this->write_log(
            "Running action '{$this->action}'",
            DL_INFO
        );

        $this->{"action_{$this->action}"}();  // NB! Variable function
        
        $this->on_after_run_action();
    }

    function on_before_run_action() {
        $this->init_sys_var("popup", (int) param("popup"));
        $this->init_sys_var("report", (int) param("report"));
        $this->init_sys_var("printable", (int) param("printable"));

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

    function create_self_redirect_response(
        $suburl_params = array(),
        $lang = null,
        $protocol = "http"
    ) {
        if ($this->use_cur_lang_from_cgi) {
            if (is_null($lang)) {
                $lang = $this->lang;
            }
        }

        $this->create_redirect_response(
            create_self_full_url(
                $suburl_params + $this->get_app_extra_suburl_params(),
                $lang,
                $protocol
            )
        );
    }

    function create_self_action_redirect_response(
        $suburl_params = array(),
        $lang = null,
        $protocol = "http"
    ) {
        $this->create_self_redirect_response(
            $this->get_self_action_suburl_params() + $suburl_params,
            $lang,
            $protocol
        );
    }

    function get_self_action_suburl_params() {
        $self_action_suburl_params = array();
        if ($this->action != $this->get_default_action_name()) {
            $self_action_suburl_params["action"] = $this->action;
            if ($this->action == "static") {
                $self_action_suburl_params["page"] = $this->read_static_page_name();
            }
        }
        return $self_action_suburl_params;
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

        $this->page->verbose_turn_off();
        $html_document_body_content = $this->print_file($this->page_template_name);
        $this->page->verbose_restore();

        return $html_document_body_content;
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
        $this->print_head_and_page_titles("access_denied");
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

    function create_rss_document_response($content) {
        $this->response = new RssDocumentResponse($content);
    }

    function create_plain_text_document_response(
        $content,
        $filename = null,
        $is_attachment = false
    ) {
        $this->response = new PlainTextDocumentResponse($content, $filename, $is_attachment);
    }

    function create_csv_document_response(
        $content,
        $filename = null,
        $is_attachment = false
    ) {
        $this->response = new CsvDocumentResponse($content, $filename, $is_attachment);
    }

    function create_pdf_document_response(
        $content,
        $filename = null,
        $is_attachment = false
    ) {
        $this->response = new PdfDocumentResponse($content, $filename, $is_attachment);
    }
//
    // Values formatting configuration
    function get_app_number_thousands_separator() {
        return $this->get_config_value("app_number_thousands_separator_{$this->lang}");
    }

    function get_app_number_decimal_point() {
        return $this->get_config_value("app_number_decimal_point_{$this->lang}");
    }

    function get_app_currency_decimal_point() {
        return $this->get_config_value("app_currency_decimal_point_{$this->lang}");
    }

    function get_app_currency_thousands_separator() {
        return $this->get_config_value("app_currency_thousands_separator_{$this->lang}");
    }

    function get_app_datetime_format() {
        return $this->get_config_value("app_datetime_format_{$this->lang}");
    }

    function get_app_short_datetime_format() {
        return $this->get_config_value("app_short_datetime_format_{$this->lang}");
    }

    function get_app_date_format() {
        return $this->get_config_value("app_date_format_{$this->lang}");
    }

    function get_app_time_format() {
        return $this->get_config_value("app_time_format_{$this->lang}");
    }

    function get_app_short_time_format() {
        return $this->get_config_value("app_short_time_format_{$this->lang}");
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

    // Euro currency sign utf-8 codepoint
    function get_currency_sign() {
        return "\xE2\x82\xAC ";
    }

    function is_currency_sign_at_start() {
        return true;
    }

    function get_currency_nonset_value_caption_pair() {
        return array(0.0, $this->get_lang_str("not_specified"));
    }
//
    // Db to App values conversion
    function get_app_value_by_type(
        $db_value,
        $type,
        $type_params
    ) {
        switch ($type) {
        case "primary_key":
        case "foreign_key":
            $app_value = $this->get_app_key_value($db_value);
            break;
        
        case "integer":
            $app_value = $this->get_app_integer_value($db_value);
            break;
        
        case "double":
            $app_value = $this->get_app_double_value($db_value, $type_params["prec"]);
            break;
        
        case "currency":
            $app_value = $this->get_app_currency_value($db_value, $type_params["prec"]);
            break;
        
        case "boolean":
            $app_value = $this->get_app_boolean_value($db_value);
            break;
        
        case "enum":
            $app_value = $this->get_app_enum_value($db_value);
            break;
        
        case "varchar":
            $app_value = $this->get_app_varchar_value($db_value);
            break;
        
        case "text":
            $app_value = $this->get_app_text_value($db_value);
            break;
        
        case "blob":
            $app_value = $this->get_app_blob_value($db_value);
            break;
        
        case "datetime":
            $app_value = $this->get_app_datetime_value($db_value);
            break;
        
        case "date":
            $app_value = $this->get_app_date_value($db_value);
            break;
        
        case "time":
            $app_value = $this->get_app_time_value($db_value);
            break;
        }
        return $app_value;
    }

    function get_app_key_value($db_value) {
        return $db_value;
    }

    function get_app_integer_value($db_value) {
        return format_integer_value(
            $db_value,
            $this->get_app_number_thousands_separator()
        );
    }

    function get_app_double_value($db_value, $decimals) {
        return format_double_value(
            $db_value,
            $decimals,
            $this->get_app_number_decimal_point(),
            $this->get_app_number_thousands_separator()
        );
    }

    function get_app_currency_value($db_value, $decimals) {
        return format_double_value(
            $db_value,
            $decimals,
            $this->get_app_currency_decimal_point(),
            $this->get_app_currency_thousands_separator()
        );
    }

    function get_app_currency_with_sign_value(
        $db_value,
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
            if ((double) $db_value == (double) $nonset_value) {
                return get_html_safe_string(
                    get_caption_from_value_caption_pair($nonset_value_caption_pair)
                );
            }
        }
        
        return append_currency_sign(
            $this->get_app_currency_value($db_value, $decimals),
            $sign,
            $sign_at_start
        );
    }

    function get_app_boolean_value($db_value) {
        return $db_value;
    }

    function get_app_enum_value($db_value) {
        return $db_value;
    }

    function get_app_varchar_value($db_value) {
        return $db_value;
    }

    function get_app_text_value($db_value) {
        return $db_value;
    }

    function get_app_blob_value($db_value) {
        return $db_value;
    }

    function get_app_datetime_value($db_value, $value_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_datetime_format(), $db_value);
        return create_date_by_format(
            $this->get_app_datetime_format(),
            $date_parts,
            $value_if_unknown
        );
    }

    function get_app_short_datetime_value($db_value, $value_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_datetime_format(), $db_value);
        return create_date_by_format(
            $this->get_app_short_datetime_format(),
            $date_parts,
            $value_if_unknown
        );
    }

    function get_app_date_value($db_value, $value_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_date_format(), $db_value);
        return create_date_by_format(
            $this->get_app_date_format(),
            $date_parts,
            $value_if_unknown
        );
    }

    function get_app_time_value($db_value, $value_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_time_format(), $db_value);
        return create_date_by_format(
            $this->get_app_time_format(),
            $date_parts,
            $value_if_unknown
        );
    }

    function get_app_short_time_value($db_value, $value_if_unknown = "") {
        $date_parts = parse_date_by_format($this->get_db_time_format(), $db_value);
        return create_date_by_format(
            $this->get_app_short_time_format(),
            $date_parts,
            $value_if_unknown
        );
    }
//
    // App to Db values conversion
    function get_db_value_by_type($app_value, $type) {
        switch ($type) {
        case "primary_key":
        case "foreign_key":
            $db_value = $this->get_db_key_value($app_value);
            break;
        
        case "integer":
            $db_value = $this->get_db_integer_value($app_value);
            break;
        
        case "double":
            $db_value = $this->get_db_double_value($app_value);
            break;
        
        case "currency":
            $db_value = $this->get_db_currency_value($app_value);
            break;
        
        case "boolean":
            $db_value = $this->get_db_boolean_value($app_value);
            break;
        
        case "enum":
            $db_value = $this->get_db_enum_value($app_value);
            break;
        
        case "varchar":
            $db_value = $this->get_db_varchar_value($app_value);
            break;
        
        case "text":
            $db_value = $this->get_db_text_value($app_value);
            break;
        
        case "blob":
            $db_value = $this->get_db_blob_value($app_value);
            break;
        
        case "datetime":
            $db_value = $this->get_db_datetime_value($app_value);
            break;
        
        case "date":
            $db_value = $this->get_db_date_value($app_value);
            break;
        
        case "time":
            $db_value = $this->get_db_time_value($app_value);
            break;
        }
        return $db_value;
    }

    function get_db_key_value($app_value) {
        return (int) $app_value;
    }

    function get_db_integer_value($app_value) {
        return (int) $this->get_db_double_value($app_value);
    }
    
    function get_db_double_value($app_value) {
        $result = str_replace($this->get_app_number_thousands_separator(), "", $app_value);
        $result = str_replace($this->get_app_number_decimal_point(), ".", $result);
        return (is_db_number($result)) ? (double) $result : 0.0;
    }

    function get_db_currency_value($app_value) {
        $result = str_replace($this->get_app_currency_thousands_separator(), "", $app_value);
        $result = str_replace($this->get_app_currency_decimal_point(), ".", $result);
        return (is_db_number($result)) ? (double) $result : 0.0;
    }

    function get_db_boolean_value($app_value) {
        return ((int) $app_value == 0) ? 0 : 1;
    }

    function get_db_enum_value($app_value) {
        return (string) $app_value;
    }

    function get_db_varchar_value($app_value) {
        return (string) $app_value;
    }

    function get_db_text_value($app_value) {
        return (string) convert_crlf2lf($app_value);
    }

    function get_db_blob_value($app_value) {
        return (string) $app_value;
    }

    function get_db_datetime_value($app_value, $value_if_unknown = "0000-00-00 00:00:00") {
        $date_parts = parse_date_by_format($this->get_app_datetime_format(), $app_value);
        return create_date_by_format(
            $this->get_db_datetime_format(),
            $date_parts,
            $value_if_unknown
        );
    }

    function get_db_date_value($app_value, $value_if_unknown = "0000-00-00") {
        $date_parts = parse_date_by_format($this->get_app_date_format(), $app_value);
        return create_date_by_format(
            $this->get_db_date_format(),
            $date_parts,
            $value_if_unknown
        );
    }

    function get_db_time_value($app_value, $value_if_unknown = "00:00:00") {
        $date_parts = parse_date_by_format($this->get_app_time_format(), $app_value);
        return create_date_by_format(
            $this->get_db_time_format(),
            $date_parts,
            $value_if_unknown
        );
    }
//
    function get_db_now_datetime() {
        return $this->get_db_datetime_from_timestamp(time());
    }

    function get_db_now_date() {
        return $this->get_db_date_from_timestamp(time());
    }

    function get_db_datetime_from_timestamp($ts) {
        $date_parts = get_date_parts_from_timestamp($ts);
        return create_date_by_format($this->get_db_datetime_format(), $date_parts, "");
    }

    function get_db_date_from_timestamp($ts) {
        $date_parts = get_date_parts_from_timestamp($ts);
        return create_date_by_format($this->get_db_date_format(), $date_parts, "");
    }

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
    // CGI functions
    function read_value_by_type($input_name, $type, $type_params) {
        switch ($type) {
        case "primary_key":
        case "foreign_key":
            $db_value = $this->read_key_value($input_name, true);
            break;
        
        case "integer":
            $db_value = $this->read_integer_value($input_name, true);
            break;
        
        case "double":
            $db_value = $this->read_double_value($input_name, true);
            break;
        
        case "currency":
            $db_value = $this->read_currency_value($input_name, true);
            break;
        
        case "boolean":
            $db_value = $this->read_boolean_value($input_name, true);
            break;
        
        case "enum":
            $db_value = $this->read_enum_value(
                $input_name,
                $type_params["input"]["values"]["data"]["array"],
                true
            );
            break;
        
        case "varchar":
            $db_value = $this->read_varchar_value($input_name, true);
            break;
        
        case "text":
            $db_value = $this->read_text_value($input_name, true);
            break;
        
        case "blob":
            $db_value = $this->read_blob_value($input_name, true);
            break;
        
        case "datetime":
            $db_value = $this->read_datetime_value($input_name, true);
            break;
        
        case "date":
            $db_value = $this->read_date_value($input_name, true);
            break;
        
        case "time":
            $db_value = $this->read_time_value($input_name, true);
            break;
        }

        return $db_value;
    }

    function read_key_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_key_value($param_value);
    }

    function read_integer_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_integer_value($param_value);
    }

    function read_double_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_double_value($param_value);
    }

    function read_currency_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_currency_value($param_value);
    }

    function read_boolean_value($param_name, $null_if_not_found = false) {
        // Skip reading field value for boolean fields (checkboxes only)
        // if no hidden value with '__sent_' prefix was sent via CGI
        if (is_null(param("__sent_{$param_name}")) && $null_if_not_found) {
            return null;
        }
        $param_value = param($param_name);
        return $this->get_db_boolean_value($param_value);
    }

    function read_enum_value($param_name, $value_caption_pairs, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_enum_value(
                get_actual_current_value($value_caption_pairs, $param_value)
            );
    }

    function read_varchar_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_varchar_value($param_value);
    }

    function read_text_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_text_value($param_value);
    }

    function read_blob_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_blob_value($param_value);
    }

    function read_datetime_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_datetime_value($param_value);
    }

    function read_date_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_date_value($param_value);
    }
    
    function read_time_value($param_name, $null_if_not_found = false) {
        $param_value = param($param_name);
        return (is_null($param_value) && $null_if_not_found) ?
            null :
            $this->get_db_time_value($param_value);
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
        $this->print_raw_value("{$param_name}.orig", $param_value);
        $this->print_value(
            "{$param_name}.suburl",
            create_suburl(array($param_name => $param_value))
        );
        $this->print_hidden_input_form_value($param_name, $param_name, $param_value);
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
        return $this->page->is_file_exist($template_name);
    }
//
    // Complex types template printing functions
    function print_primary_key_value($template_var, $db_value) {
        $this->print_raw_value($template_var, $db_value);
    }

    function print_foreign_key_value($template_var, $db_value) {
        $this->print_raw_value($template_var, $db_value);
    }

    function print_integer_value(
        $template_var,
        $db_value,
        $nonset_value_caption_pair = null
    ) {
        $app_value_with_nonset = $db_value;
        $app_value_formatted = $this->get_app_integer_value($db_value);
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((int) $db_value == (int) $nonset_value) {
                $nonset_caption = get_caption_from_value_caption_pair($nonset_value_caption_pair);
                $app_value_formatted = get_html_safe_string($nonset_caption);
                $app_value_with_nonset = $nonset_caption;
            }
        }
        $this->print_raw_values(array(
            $template_var => $app_value_formatted,
            "{$template_var}.orig" => $db_value,
            "{$template_var}.orig_with_nonset" => $app_value_with_nonset,
        ));
    }

    function print_double_value(
        $template_var,
        $db_value,
        $decimals,
        $nonset_value_caption_pair = null
    ) {
        $app_value_with_nonset = $db_value;
        $app_value_formatted = $this->get_app_double_value($db_value, $decimals);
        $app_value_formatted_2 = $this->get_app_double_value($db_value, 2);
        $app_value_formatted_5 = $this->get_app_double_value($db_value, 5);
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((double) $db_value == (double) $nonset_value) {
                $nonset_caption = get_caption_from_value_caption_pair($nonset_value_caption_pair);
                $nonset_caption_safe = get_html_safe_string($nonset_caption);
                $app_value_formatted = $nonset_caption_safe;
                $app_value_formatted_2 = $nonset_caption_safe;
                $app_value_formatted_5 = $nonset_caption_safe;
                $app_value_with_nonset = $nonset_caption;
            }
        }
        $this->print_raw_values(array(
            $template_var => $app_value_formatted,
            "{$template_var}.2" => $app_value_formatted_2,
            "{$template_var}.5" => $app_value_formatted_5,
            "{$template_var}.orig" => $db_value,
            "{$template_var}.orig_with_nonset" => $app_value_with_nonset,
        ));
    }

    function print_currency_value(
        $template_var,
        $db_value,
        $decimals,
        $sign = null,
        $sign_at_start = null,
        $nonset_value_caption_pair = null
    ) {
        $app_value_with_nonset = $db_value;
        $app_value_formatted = $this->get_app_currency_with_sign_value(
            $db_value,
            $decimals,
            $sign,
            $sign_at_start,
            $nonset_value_caption_pair
        );
        $app_value_formatted_without_sign = $this->get_app_currency_value($db_value, $decimals);
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((double) $db_value == (double) $nonset_value) {
                $nonset_caption = get_caption_from_value_caption_pair($nonset_value_caption_pair);
                $app_value_formatted_without_sign = get_html_safe_string($nonset_caption);
                $app_value_with_nonset = $nonset_caption;
            }
        }
        $this->print_raw_values(array(
            $template_var => $app_value_formatted,
            "{$template_var}.without_sign" => $app_value_formatted_without_sign,
            "{$template_var}.orig" => $db_value,
            "{$template_var}.orig_with_nonset" => $app_value_with_nonset,
        ));
    }

    function print_boolean_value(
        $template_var,
        $db_value,
        $value_caption_pairs = null
    ) {
        if (is_null($value_caption_pairs)) {
            $value_caption_pairs = array(
                array(0, $this->get_lang_str("no")),
                array(1, $this->get_lang_str("yes")),
            );
        }
        $caption = get_caption_by_value_from_value_caption_pairs(
            $value_caption_pairs,
            ((int) $db_value == 0) ? 0 : 1
        );
        $this->print_value($template_var, $caption);
        $this->print_raw_value("{$template_var}.orig", $db_value);
    }

    function print_enum_value(
        $template_var,
        $db_value,
        $value_caption_pairs
    ) {
        $db_value = get_actual_current_value($value_caption_pairs, $db_value);
        $caption = get_caption_by_value_from_value_caption_pairs($value_caption_pairs, $db_value);
        if (is_null($caption)) {
            $caption = "";
        }
        $this->print_value($template_var, $caption);
        $this->print_raw_value("{$template_var}.caption_orig", $caption);
        $this->print_raw_value("{$template_var}.orig", $db_value);
    }

    function print_varchar_value($template_var, $db_value) {
        $this->print_value($template_var, $db_value);
        $this->print_raw_value("{$template_var}.orig", $db_value);
    }

    function print_text_value($template_var, $db_value) {
        $this->print_varchar_value($template_var, $db_value);
        $this->print_raw_value(
            "{$template_var}.lf2br",
            convert_lf2br(get_html_safe_string($db_value))
        );
    }

    function print_datetime_value($template_var, $db_value) {
        $this->print_values(array(
            $template_var => $this->get_app_datetime_value($db_value),
            "{$template_var}.short" => $this->get_app_short_datetime_value($db_value),
            "{$template_var}.orig" => $db_value,
        ));
    }

    function print_date_value($template_var, $db_value) {
        $this->print_values(array(
            $template_var => $this->get_app_date_value($db_value),
            "{$template_var}.orig" => $db_value,
        ));
    }

    function print_time_value($template_var, $db_value) {
        $this->print_values(array(
            $template_var => $this->get_app_time_value($db_value),
            "{$template_var}.short" => $this->get_app_short_time_value($db_value),
            "{$template_var}.orig" => $db_value,
        ));
    }
//
    // Form values template printing functions
    function print_primary_key_form_value(
        $template_var,
        $input_name,
        $db_value
    ) {
        $this->print_hidden_input_form_value($template_var, $input_name, $db_value);
        $this->print_text_input_form_value($template_var, $input_name, $db_value);
    }

    function print_foreign_key_form_value(
        $template_var,
        $input_name,
        $db_value,
        $input_type,
        $input_attrs,
        $values_info,
        $input_type_params,
        $alt_values_info = null
    ) {
        $this->print_hidden_input_form_value($template_var, $input_name, $db_value);

        switch ($input_type) {
        case "text":
            $printed_value = $this->print_text_input_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs
            );
            break;
        
        case "radio":
            $printed_value = $this->print_radio_group_input_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs,
                $values_info,
                $alt_values_info
            );
            break;
        
        case "select":
            $printed_value = $this->print_select_input_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs,
                $values_info,
                $alt_values_info
            );
            break;
        
        case "main_select":
            $printed_value = $this->print_main_select_input_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs,
                $values_info,
                $input_type_params["dependent_select_name"],
                $input_type_params["dependency_info"],
                $input_type_params["dependent_values_info"],
                $alt_values_info
            );
            break;
        
        default:
            $printed_value = "";
        }

        return $printed_value;
    }

    function print_integer_form_value(
        $template_var,
        $input_name,
        $db_value,
        $input_attrs,
        $nonset_value_caption_pair = null
    ) {
        $app_value_formatted = $this->get_app_integer_value($db_value);
        $this->print_hidden_input_form_value($template_var, $input_name, $app_value_formatted);

        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((int) $db_value == (int) $nonset_value) {
                $nonset_caption = get_caption_from_value_caption_pair($nonset_value_caption_pair);
                $app_value_formatted = $nonset_caption;
            }
        }
        
        return $this->print_text_input_form_value(
            $template_var,
            $input_name,
            $app_value_formatted,
            array_merge(
                array("class" => "integer"),
                $input_attrs
            )
        );
    }
    
    function print_double_form_value(
        $template_var,
        $input_name,
        $db_value,
        $decimals,
        $input_attrs,
        $nonset_value_caption_pair = null
    ) {
        $app_value_formatted = $this->get_app_double_value($db_value, $decimals);
        $this->print_hidden_input_form_value($template_var, $input_name, $app_value_formatted);
        
        if (!is_null($nonset_value_caption_pair)) {
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
            if ((double) $db_value == (double) $nonset_value) {
                $nonset_caption = get_caption_from_value_caption_pair($nonset_value_caption_pair);
                $app_value_formatted = $nonset_caption;
            }
        }

        return $this->print_text_input_form_value(
            $template_var,
            $input_name,
            $app_value_formatted,
            array_merge(
                array("class" => "double"),
                $input_attrs
            )
        );
    }

    function print_currency_form_value(
        $template_var,
        $input_name,
        $db_value,
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

        $app_value_formatted_without_sign = $this->get_app_currency_value($db_value, $decimals);
        $this->print_hidden_input_form_value(
            $template_var,
            $input_name,
            $app_value_formatted_without_sign
        );
        
        $printed_value = $this->print_text_input_form_value(
            $template_var,
            $input_name,
            $app_value_formatted_without_sign,
            array_merge(
                array("class" => "currency"),
                $input_attrs
            )
        );
        $this->print_raw_value(
            "{$template_var}.input",
            append_currency_sign($printed_value, $sign, $sign_at_start)
        );

        $this->print_text_input_form_value(
            "{$template_var}.without_sign",
            $input_name,
            $app_value_formatted_without_sign,
            array_merge(
                array("class" => "currency"),
                $input_attrs
            )
        );
        
        return $printed_value;
    }

    function print_boolean_form_value(
        $template_var,
        $input_name,
        $db_value,
        $input_attrs
    ) {
        $this->print_hidden_input_form_value($template_var, $input_name, $db_value);
        
        return $this->print_checkbox_input_form_value(
            $template_var,
            $input_name,
            1,
            ($db_value != 0),
            $input_attrs
        );
    }

    function print_enum_form_value(
        $template_var,
        $input_name,
        $db_value,
        $input_type,
        $input_attrs,
        $values_info
    ) {
        $this->print_hidden_input_form_value($template_var, $input_name, $db_value);

        switch ($input_type) {
        case "radio":
            $printed_value = $this->print_radio_group_input_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs,
                $values_info
            );
            break;
        
        case "select":
            $printed_value = $this->print_select_input_form_value(
                $template_var,
                $input_name,
                $db_value,
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
        $input_name,
        $db_value,
        $input_type,
        $input_attrs
    ) {
        $this->print_hidden_input_form_value($template_var, $input_name, $db_value);

        switch ($input_type) {
        case "text":
        case "password":
            $printed_value = $this->print_input_form_value(
                $input_type,
                $template_var,
                $input_name,
                $db_value,
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
        $input_name,
        $db_value,
        $input_type,
        $input_attrs
    ) {
        $this->print_hidden_input_form_value($template_var, $input_name, $db_value);
        
        switch ($input_type) {
        case "textarea":
            $printed_value = $this->print_textarea_input_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs
            );
            break;
        
        default:
            $printed_value = "";
        }

        return $printed_value;
    }

    function print_datetime_form_value(
        $template_var,
        $input_name,
        $db_value,
        $input_attrs
    ) {
        $app_value = $this->get_app_datetime_value($db_value);
        $this->print_hidden_input_form_value($template_var, $input_name, $app_value);

        return $this->print_text_input_form_value(
            $template_var,
            $input_name,
            $app_value,
            array_merge(
                array("class" => "datetime"),
                $input_attrs
            )
        );
    }

    function print_date_form_value(
        $template_var,
        $input_name,
        $db_value,
        $input_attrs
    ) {
        $app_value = $this->get_app_date_value($db_value);
        $this->print_hidden_input_form_value($template_var, $input_name, $app_value);

        return $this->print_text_input_form_value(
            $template_var,
            $input_name,
            $app_value,
            array_merge(
                array("class" => "date"),
                $input_attrs
            )
        );
    }

    function print_time_form_value(
        $template_var,
        $input_name,
        $db_value,
        $input_attrs
    ) {
        $app_value = $this->get_app_time_value($db_value);
        $this->print_hidden_input_form_value($template_var, $input_name, $app_value);

        return $this->print_text_input_form_value(
            $template_var,
            $input_name,
            $app_value,
            array_merge(
                array("class" => "time"),
                $input_attrs
            )
        );
    }
//
    function print_input_name($template_var, $input_name) {
        $this->print_raw_value("{$template_var}.input_name", $input_name);
    }

    function print_raw_input_form_value(
        $input_type,
        $template_var,
        $input_name,
        $input_value,
        $input_attrs = array()
    ) {
        $printed_value = print_raw_html_input($input_type, $input_name, $input_value, $input_attrs);

        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }

    function print_raw_hidden_input_form_value(
        $template_var,
        $input_name,
        $input_value
    ) {
        $printed_value = print_raw_html_input("hidden", $input_name, $input_value);
        
        $this->print_raw_value("{$template_var}.hidden", $printed_value);
        
        return $printed_value;
    }

    function print_input_form_value(
        $input_type,
        $template_var,
        $input_name,
        $input_value,
        $input_attrs = array()
    ) {
        $printed_value = print_html_input($input_type, $input_name, $input_value, $input_attrs);
        
        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }

    function print_hidden_input_form_value(
        $template_var,
        $input_name,
        $input_value
    ) {
        $printed_value = print_html_hidden($input_name, $input_value);
        
        $this->print_raw_value("{$template_var}.hidden", $printed_value);
        
        return $printed_value;
    }

    function print_text_input_form_value(
        $template_var,
        $input_name,
        $input_value,
        $input_attrs = array()
    ) {
        $this->print_input_name($template_var, $input_name);
        
        return $this->print_input_form_value(
            "text",
            $template_var,
            $input_name,
            $input_value,
            $input_attrs
        );
    }

    function print_textarea_input_form_value(
        $template_var,
        $input_name,
        $input_value,
        $input_attrs = array()
    ) {
        $printed_value = print_html_textarea($input_name, $input_value, $input_attrs);
        
        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }

    function print_checkbox_input_form_value(
        $template_var,
        $input_name,
        $input_value,
        $checked = null,
        $input_attrs = array()
    ) {
        $printed_value =
            print_html_hidden("__sent_{$input_name}", 1) .
            print_html_checkbox($input_name, $input_value, $checked, $input_attrs);
        
        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }

    function print_checkboxes_group_input_form_value(
        $template_var,
        $input_name,
        $input_value,
        $input_attrs,
        $values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info, $alt_values_info);

        $values_data_info = get_param_value($values_info, "data", array());
        $delimiter = get_param_value($values_data_info, "delimiter", "");

        $printed_value = print_html_checkboxes_group(
            $input_name,
            $value_caption_pairs,
            $input_value,
            $input_attrs,
            $delimiter
        );
        
        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }

    function print_radio_group_input_form_value(
        $template_var,
        $input_name,
        $input_value,
        $input_attrs,
        $values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info, $alt_values_info);

        $values_data_info = get_param_value($values_info, "data", array());
        $delimiter = get_param_value($values_data_info, "delimiter", "");

        $printed_value = print_html_radio_group(
            $input_name,
            $value_caption_pairs,
            $input_value,
            $input_attrs,
            $delimiter
        );
        
        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }

    function print_select_input_form_value(
        $template_var,
        $input_name,
        $input_value,
        $input_attrs,
        $values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info, $alt_values_info);

        $printed_value = print_html_select(
            $input_name,
            $value_caption_pairs,
            $input_value,
            $input_attrs
        );
        
        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }

    function print_main_select_input_form_value(
        $template_var,
        $input_name,
        $input_value,
        $input_attrs,
        $values_info,
        $dependent_select_name,
        $dependency_info,
        $dependent_values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info, $alt_values_info);

        $form_name = get_param_value($dependency_info, "form_name", "form");
        $main_select_name = $input_name;
        $dependency_key_field_name = $dependency_info["key_field_name"];
        $dependency_query_ex = get_param_value($dependency_info, "query_ex", array());
        $dependency_name = get_param_value($dependency_info, "name", $template_var);

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
            $input_name,
            $value_caption_pairs,
            $input_value,
            $input_attrs
        );
        
        $this->print_input_name($template_var, $input_name);
        $this->print_raw_value("{$template_var}.input", $printed_value);
        $this->print_raw_value("{$dependency_name}.dependency_js", $dependency_js);
        
        return $printed_value;
    }

    function print_multilingual_form_value($template_var) {
        $lang_inputs_with_captions_str = "";
        foreach ($this->avail_langs as $lang) {
            $lang_str = $this->get_lang_str($lang);
            $lang_input = $this->page->get_filling_value("{$template_var}_{$lang}.input");
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

        $this->print_raw_value(
            "{$template_var}.hidden",
            $this->page->get_filling_value("{$template_var}_{$this->lang}.hidden")
        );
        $this->print_raw_value("{$template_var}.input", $printed_value);
        
        return $printed_value;
    }
//
    function get_value_caption_pairs($values_info, $alt_values_info = null) {
        $values_data_info = get_param_value($values_info, "data", array());

        $value_caption_pairs = $this->get_value_caption_pairs_from_source($values_info);
        $value_caption_pairs = $this->expand_value_caption_pairs_with_begin_end(
            $value_caption_pairs,
            $values_data_info
        );
        
        if (!is_null($alt_values_info)) {
            $values_data_info = get_param_value($alt_values_info, "data", array());
        }
        $value_caption_pairs = $this->expand_value_caption_pairs_with_nonset(
            $value_caption_pairs,
            $values_data_info
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

    // New implementation.
    // Note:
    // $captions_field_name may be string (field name) or array("func" => "func_name")
    function get_value_caption_pairs_from_db_object_query(
        $obj,
        $query_ex,
        $values_field_name,
        $captions_field_name
    ) {
        return $this->get_value_caption_pairs_from_db_objects_list(
            $this->fetch_db_objects_list($obj, $query_ex),
            $values_field_name,
            $captions_field_name
        );
    }

    function get_value_caption_pairs_from_db_objects_list(
        $db_objects_list,
        $values_field_name,
        $captions_field_name
    ) {
        if (is_string($captions_field_name)) {
            $get_captions_by_func = false;
        } else {
            $get_captions_by_func = true;
            $captions_func_name = $captions_field_name["func"];
        }

        $value_caption_pairs = array();
        foreach ($db_objects_list as $obj) {
            $value = $obj->{$values_field_name};
            
            if ($get_captions_by_func) {
                $caption = $obj->{$captions_func_name}();
            } else {
                $caption = $obj->{$captions_field_name};
            }
            
            $value_caption_pairs[] = array($value, $caption);
        }
        return $value_caption_pairs;
    }

// Old implementation.
// Works faster but adds more troubles.
// Redefine function with code below if necessary. 
//    function get_value_caption_pairs_from_db_object_query(
//        $obj,
//        $query_ex,
//        $values_field_name,
//        $captions_field_name
//    ) {
//        if (is_string($obj)) {
//            $obj =& $this->create_db_object($obj);
//        }
//        $query = $obj->get_expanded_select_query(
//            $query_ex,
//            array(
//                $values_field_name,
//                $captions_field_name,
//            )
//        );
//        return $this->get_value_caption_pairs_from_query(
//            $query,
//            $values_field_name,
//            $captions_field_name
//        );
//    }

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
//    
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
    function get_lang_str($resource, $resource_params = array()) {
        $lang_str = get_param_value($this->lang_resources, $resource, null);
        if (is_null($lang_str)) {
            return null;        
        } else {
            return $this->page->get_parsed_text($lang_str, null, $resource_params);
        }
    }

    function get_current_lang() {
        if ($this->use_cur_lang_from_cgi) {
            $cur_lang = $this->get_current_lang_from_cgi();
            if (is_null($cur_lang)) {
                $cur_lang = $this->get_current_lang_from_cookie();
            }
        } else {
            $cur_lang = $this->get_current_lang_from_session();
            if (is_null($cur_lang)) {
                $cur_lang = $this->get_current_lang_from_cookie();
            }
        }
        if (!$this->is_valid_lang($cur_lang)) {
            $cur_lang = $this->dlang;
        }
        return $cur_lang;
    }

    function get_current_lang_from_cgi() {
        return param("_current_lang");
    }

    function get_current_lang_from_cookie() {
        return get_param_value($_COOKIE, "current_lang", null);
    }

    function get_current_lang_from_session() {
        return (is_null($this->session)) ?
            null :
            $this->session->get_param("current_lang");
    }

    function set_current_lang($new_lang) {
        if ($this->is_valid_lang($new_lang)) {
            if (!$this->use_cur_lang_from_cgi) {
                $this->session->set_param("current_lang", $new_lang);
            }
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
        $this->print_head_and_page_titles($this->get_action_lang_resource());
    }

    function print_head_and_page_titles($resource) {
        $this->print_page_title($resource);
        $this->print_head_page_title($resource);
    }

    function print_page_title($resource, $is_html = true) {
        $page_title_resource = "page_title.{$resource}";
        $this->print_raw_value("page_title.resource", $resource);
        $this->print_raw_value("page_title.lang_resource", $page_title_resource);
        $page_title_text = $this->get_lang_str($page_title_resource);
        if (!is_null($page_title_text)) {
            if ($is_html) {
                $this->print_raw_value("page_title_text", $page_title_text);
            } else {
                $this->print_value("page_title_text", $page_title_text);
            }
            $this->print_file_new_if_exists("_page_title.html", "page_title");
        }
    }

    function print_head_page_title($resource, $is_html = true) {
        $head_page_title_resource = "head_page_title.{$resource}";
        $head_page_title_text = $this->get_lang_str($head_page_title_resource);
        if (is_null($head_page_title_text)) {
            // If have no head_page_title found in lang files then use page_title instead
            $head_page_title_resource = "page_title.{$resource}";
            $head_page_title_text = $this->get_lang_str($head_page_title_resource);
        }
        if (!is_null($head_page_title_text)) {
            if ($is_html) {
                $this->print_raw_value("head_page_title", $head_page_title_text);
            } else {
                $this->print_value("head_page_title", $head_page_title_text);
            }
        }
        $this->print_raw_value("head_page_title.resource", $resource);
        $this->print_raw_value("head_page_title.lang_resource", $head_page_title_resource);
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

    function print_status_message_db_object_updated(&$obj) {
        $action_done = ($obj->was_definite()) ? "updated" : "added";
        $this->add_session_status_message(new OkStatusMsg("{$obj->_table_name}.{$action_done}"));
    }
        
    function print_status_message_db_object_deleted(&$obj) {
        $this->add_session_status_message(new OkStatusMsg("{$obj->_table_name}.deleted"));
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

    // Main menu
    function print_menu($params = array()) {
        $menu =& $this->create_menu($params);
        
        $menu->load_from_xml(get_param_value($params, "xml_filename", "_menu.xml"));

        $menu->hide_items_by_names(get_param_value($params, "item_names_to_hide", array()));

        $menu->select_items_by_context(
            get_param_value($params, "context", $this->get_action_lang_resource())
        );

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
        $lang_menu->use_lang_in_redirect_url = $this->use_cur_lang_from_cgi;
        $lang_menu->redirect_url_params = $this->get_self_action_suburl_params();

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

        $this->create_redirect_response((string) param("redirect_url"));

        $this->add_current_lang_cookie();
    }

    function add_current_lang_cookie() {
        $cookie_expiration_ts = $this->create_current_lang_cookie_expiration_ts();
        $this->response->add_cookie(new Cookie(
            "current_lang",
            $this->lang,
            $cookie_expiration_ts,
            create_self_url()
        ));
    }

    function create_current_lang_cookie_expiration_ts() {
        return time() + 60 * 60 * 24 * 365;
    }
//
    function action_static() {
        $page_name = $this->read_static_page_name();
        if ($page_name != "") {
            $this->print_static_page($page_name, "body");
        }
    }

    function read_static_page_name() {
        $page_name = trim(param("page"));
        if (!preg_match('/^\w+$/i', $page_name)) {
            $page_name = "";
        }
        return $page_name;
    }

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
//
    function action_get_image() {
        $image =& $this->read_id_fetch_db_object("Image");
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
//
    function action_get_file() {
        $file =& $this->read_id_fetch_db_object("File");
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
//
    // Setup app actions
    function action_create_update_tables() {
        $command = (string) param("command");

        if ($command != "create_update") {
            $this->process_create_update_tables(true, $run_info);

            $this->print_varchar_value("db_name", $this->db->get_database());

            $draw_delimiter = false;
            $sql_script_text = "";
            $table_types = array("create", "update", "drop");
            foreach ($table_types as $table_type) {
                $table_names = $run_info["table_names_to_{$table_type}"];
                $this->print_varchar_value(
                    "table_names_to_{$table_type}",
                    (count($table_names) == 0) ?
                        $this->get_lang_str("nothing_to_{$table_type}") :
                        join(", ", $table_names)
                );

                foreach ($run_info["{$table_type}_table_queries"] as $query) {
                    if ($draw_delimiter) {
                        $sql_script_text .= str_repeat("-", 80) . "\n";
                    }
                    $sql_script_text .= "  {$query}\n";
                    $draw_delimiter = true;
                }
            }
            $this->print_varchar_value("sql_script_text", $sql_script_text);
            $this->print_file("tables_create_update/body.html", "body");
        } else {
            $this->process_create_update_tables(false, $run_info);
            
            $this->add_session_status_message(new OkStatusMsg("tables_updated"));
            
            $this->create_self_redirect_response();
        }
    }

    function action_delete_tables() {
        $this->process_delete_tables(null, false, $run_info);

        $this->add_session_status_message(new OkStatusMsg("tables_deleted"));
        
        $this->create_self_redirect_response();
    }
//
    function action_tables_dump() {
        $actual_table_names = $this->db->get_actual_table_names(true, false);
        foreach ($actual_table_names as $actual_table_name) {
            $this->print_varchar_value("table_name", $actual_table_name);
            $this->print_file("tables_dump/tables_list/form_item.html", "form_items");
        }
        $this->print_file("tables_dump/tables_list/form.html", "body");
    }

    function action_tables_dump_url() {
        $table_names = param_array("table_names");
        $table_names_str = join(" ", $table_names);
        
        $this->print_value("table_names_str", $table_names_str);

        $url = create_self_full_url(array(
            "action" => "tables_dump_view",
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

    function action_tables_dump_view() {
        $stream = $this->_create_tables_dump_stream(param("table_names"), false);
        if ($stream === false) {
            $this->create_self_redirect_response(array("action" => "tables_dump"));
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
            $this->create_self_redirect_response(array("action" => "tables_dump"));
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
        //'b' parameter removed temporarily to work with php 4.3.9-centos4
        // Doesn't work on windows
        // Was: $stream = popen($cmdline, "rb");
        $stream = popen($cmdline, "r");
        if ($stream === false) {
            $this->write_log(
                "DB dump stream creation failed! Couldn't run commandline:\n" .
                "{$cmdline}\n",
                DL_ERROR
            );
        }
        return $stream;
    }
//
    function process_create_update_tables($fake_run, &$run_info) {
        if ($fake_run) {
            $run_info = array(
                "table_names_to_create" => array(),
                "create_table_queries" => array(),
                "table_names_to_update" => array(),
                "update_table_queries" => array(),
                "table_names_to_drop" => array(),
                "drop_table_queries" => array(),
            );
        }

        $actual_table_names = $this->db->get_actual_table_names(false, false);

        $all_creatable_db_objects_info = $this->get_all_creatable_db_objects_info();
        $all_table_names_to_create = array_keys($all_creatable_db_objects_info);

        $table_names_to_create = array_diff($all_table_names_to_create, $actual_table_names);
        $table_names_to_update = array_intersect($all_table_names_to_create, $actual_table_names);
        $table_names_to_drop = array_diff($actual_table_names, $all_table_names_to_create);

        foreach ($table_names_to_create as $table_name) {
            $obj =& $this->create_db_object($all_creatable_db_objects_info[$table_name]);
            $obj->create_table($fake_run, $run_info);
        }

        foreach ($table_names_to_update as $table_name) {
            $obj =& $this->create_db_object($all_creatable_db_objects_info[$table_name]);
            $obj->update_table($fake_run, $run_info);
        }

        $this->process_delete_tables($table_names_to_drop, $fake_run, $run_info);
    }

    function process_delete_tables($table_names_to_drop, $fake_run, &$run_info) {
        if (is_null($table_names_to_drop)) {
            $table_names_to_drop = $this->db->get_actual_table_names(false, false);
        }
        foreach ($table_names_to_drop as $table_name) {
            $drop_table_query = $this->db->get_drop_table_query($table_name);
            if ($fake_run) {
                $run_info["table_names_to_drop"][] = $table_name;
                $run_info["drop_table_queries"][$table_name] =
                    $this->db->subst_table_prefix($drop_table_query);
            } else {
                $this->db->run_query($drop_table_query);
            }
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
//
    // Action helper functions
    
    // Create new object and fetch its values from db table by id
    function &fetch_db_object(
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
        if ($obj_id != 0) {
            $obj_id_name = $obj->get_primary_key_name();
            $obj->fetch(
                "{$obj->_table_name}.{$obj_id_name} = {$obj_id} AND {$where_str}",
                $field_names_to_select,
                $field_names_to_not_select
            );
        }
        return $obj;
    }

    // Create new object, read it's PRIMARY KEY from CGI
    // (using CGI variable with name $id_param_name),
    // then fetch object from db table
    function &read_id_fetch_db_object(
        $obj_class_name,
        $where_str = "1",
        $id_param_name = null,
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        $obj =& $this->create_db_object($obj_class_name);

        if (is_null($id_param_name)) {
            $id_param_name = "{$obj->_table_name}_id";
        }
        $id_param_value = $this->read_key_value($id_param_name);
        return $this->fetch_db_object(
            $obj,
            $id_param_value,
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
            $objects[] = clone($obj);
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
    function delete_db_object($params = array()) {
        $obj = get_param_value($params, "obj", null);
        if (is_null($obj)) {
            $this->process_fatal_error_required_param_not_found("obj", "delete_db_object()");
        }

        $should_redirect = get_param_value($params, "should_redirect", true);

        if ($should_redirect) {
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
        }

        $del_cascade = get_param_value($params, "cascade", false);

        if ($del_cascade) {
            $obj->del_cascade();
        } else {
            $messages = $obj->check_restrict_relations_before_delete();
            
            if (count($messages) != 0) {
                $this->print_status_messages_cannot_delete_db_object($messages);
                if ($should_redirect) {
                    $this->create_self_redirect_response($error_url_params);
                }
                return false;
            } else {
                $obj->del();
            }
        }
        
        $this->print_status_message_db_object_deleted($obj);
        
        if ($should_redirect) {
            $this->create_self_redirect_response($success_url_params);
        }
        
        return true;
    }
//
    // Uploaded image
    function &fetch_image($image_id) {
        return $this->fetch_db_object("Image", $image_id);
    }

    function &fetch_image_without_content($image_id) {
        return $this->fetch_db_object("Image", $image_id, "1", null, array("content"));
    }

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
        
        $obj_image = $this->fetch_image_without_content($obj->{$image_id_field_name});

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

    function delete_db_object_image(
        &$obj,
        $image_id_field_name,
        $delete_thumbnail = true
    ) {
        if ($obj->is_definite() && $obj->is_field_exist($image_id_field_name)) {
            $obj->del_image($image_id_field_name);
            $obj->set_field_value($image_id_field_name, 0);

            $thumbnail_image_id_field_name = "thumbnail_{$image_id_field_name}";
            if ($delete_thumbnail && $obj->is_field_exist($thumbnail_image_id_field_name)) {
                $obj->del_image($thumbnail_image_id_field_name);
                $obj->set_field_value($thumbnail_image_id_field_name, 0);
            }

            $obj->update();
        }
    }
//
    // Uploaded file
    function &fetch_file($file_id) {
        return $this->fetch_db_object("File", $file_id);
    }

    function &fetch_file_without_content($file_id) {
        return $this->fetch_db_object("File", $file_id, "1", null, array("content"));
    }

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
        
        $obj_file = $this->fetch_file_without_content($obj->{$file_id_field_name});

        $obj_file->filename = $uploaded_file->get_orig_filename();
        $obj_file->set_file_fields_from($uploaded_file);
        
        $obj_file->save();

        $obj->set_field_value($file_id_field_name, $obj_file->id);
        
        return true;
    }

    function delete_db_object_file(
        &$obj,
        $file_id_field_name
    ) {
        if ($obj->is_definite() && $obj->is_field_exist($file_id_field_name)) {
            $obj->del_file($file_id_field_name);
            $obj->set_field_value($file_id_field_name, 0);

            $obj->update();
        }
    }
//
    // Print DbObject values, wrap with appropriate
    // info template file and put result to template var
    function print_db_object_info(
        &$obj,
        $templates_dir,
        $template_var,
        $obj_info_template_name,
        $obj_info_empty_template_name = null,
        $params = array()
    ) {
        $obj->print_values($params);
        $template_name = ($obj->is_definite() || is_null($obj_info_empty_template_name)) ?
            $obj_info_template_name :
            $obj_info_empty_template_name;
        return $this->print_file_new_if_exists("{$templates_dir}/{$template_name}", $template_var);
    }

    // Print DbObject form values, wrap with appropriate
    // subform template file and put result to template var
    function print_db_object_subform(
        &$obj,
        $templates_dir,
        $template_var,
        $obj_subform_template_name,
        $obj_empty_template_name = null,
        $params = array()
    ) {
        $obj->print_form_values($params);
        $template_name = ($obj->is_definite() || is_null($obj_subform_empty_template_name)) ?
            $obj_subform_template_name :
            $obj_subform_empty_template_name;
        return $this->print_file_new_if_exists("{$templates_dir}/{$template_name}", $template_var);
    }
//
    // Email sender
    function &create_email_sender() {
        $email_sender =& $this->create_object("CustomPHPMailer");
        $email_sender->IsSendmail();
        $sendmail_path = $this->get_config_value("sendmail_path", null);
        if (!is_null($sendmail_path)) {
            $email_sender->Sendmail = $sendmail_path;
        }
        $email_sender->IsHTML($this->get_config_value("email_is_html"));
        $email_sender->CharSet = $this->get_config_value("email_charset");
        return $email_sender;
    }

    function get_actual_email_to($email_to) {
        return $this->get_config_value("email_debug_mode") ?
            $this->get_config_value("admin_email_to") :
            $email_to;
    }
//
    // Popup windows helpers
    function init_popup($width, $height) {
        $templates_dir = "_global/popup";

        $this->init_sys_var("popup", 1);

        $this->print_raw_value("sys:popup.width", $width);
        $this->print_raw_value("sys:popup.height", $height);

        if (param("new_popup") == 1) {
            $this->print_file(
                "{$templates_dir}/_popup_center_js.html",
                "_popup_center_js"
            );
        }

        if (param("reload_opener") == 1) {
            $this->print_file(
                "{$templates_dir}/_reload_opener_js.html",
                "_reload_opener_js"
            );
        }

        $this->print_file("{$templates_dir}/init_popup_js.html", "init_popup_js");
    }

    function create_reload_opener_self_redirect_response(
        $suburl_params = array(),
        $protocol = "http"
    ) {
        $this->create_self_redirect_response(
            $suburl_params + array("reload_opener" => 1),
            null,
            $protocol
        );
    }

    function create_close_popup_and_reload_opener_html_response($opener_suburl_params = null) {
        if (is_null($opener_suburl_params)) {
            $location_js = "reload()";
        } else {
            $url = create_self_full_url(
                $opener_suburl_params,
                $this->lang
            );
            $location_js = "href = '{$url}'";
        }
        $this->print_raw_value("location_js", $location_js);
        $this->print_file("_global/popup/close_and_reload_opener_js.html", "body");
    }

}

?>