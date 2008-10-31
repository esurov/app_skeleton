<?php

class UserApp extends CustomApp {

    // Current logged in user
    var $user;

    function UserApp() {
        parent::CustomApp("UserApp", "user_app");

        $this->use_cur_lang_from_cgi = true;

        $e = array("roles" => array("guest", "user", "admin"));
        $u = array("roles" => array("user"));
        $a = array("roles" => array("admin"));
        $u_a = array("roles" => array("user", "admin"));

        $this->actions = array(
            // Common
            "change_lang" => $e,
            "static" => $e,
            "get_image" => $e,
            "get_file" => $e,

            // Index and Home pages
            "index" => $e,
            "home" => $u_a,
            "user_home" => $u,
            "admin_home" => $a,

            // Login/Signup/Confirm/RecoverPassword
            "login" => $e,
            "logout" => $e,
            "signup" => $e,
            "confirm_signup" => $e,
            "recover_password" => $e,

            // Contact form
            "contact_form" => $e,

            // Users management
            "my_account" => $u_a,
            "users_admin" => $a,
            "user_edit_admin" => $a,

            // News articles
            "news_articles" => $e,
            "news_articles_admin" => $a,
            "news_article_view" => $e,
            "news_article_edit_admin" => $a,
            "news_articles_rss" => $e,

            // Newsletters
            "newsletters" => $a,
            "newsletter_view" => $a,
            "newsletter_edit" => $a,

            // Newsletter categories
            "newsletter_categories" => $a,
            "newsletter_category_edit" => $a,

            // User subscription
            "user_subscription" => $u,
            "user_subscription_edit" => $u,

            // Categories management
            "categories" => $a,
            "category1_edit" => $a,
            "category2_edit" => $a,
            "category3_edit" => $a,

            // Products management
            "products" => $a,
            "product_edit" => $a,
        );
    }
//
    function create_session() {
        $this->session =& $this->create_object("LoginSession");
    }

    function create_current_user() {
        $this->user =& $this->create_db_object("User");

        $login_state =& $this->session->get_login_state();
        if (is_null($login_state)) {
            // User checked "remember me" checkbox during login
            if ($this->was_user_remembered()) {
                $user_id = (int) $_COOKIE["user_id"];
                $user_password_hash = (string) $_COOKIE["user_password_hash"];

                if ($this->user->fetch("user.id = {$user_id}")) {
                    if (sha1($this->user->password) == $user_password_hash) {
                        $this->create_login_state($this->user);
                    } else {
                        $this->user->reset_field_values();
                    }
                }
            }
        } else {
            if (!$login_state->is_expired()) {
                $login_state->update();

                $user_id = (int) $login_state->get_login_id();
                $this->user->fetch("user.id = {$user_id}");
            }
        }
    }

    function was_user_remembered() {
        return (isset($_COOKIE["user_id"]) && isset($_COOKIE["user_password_hash"]));
    }

    function get_user_role($user = null) {
        if (is_null($user)) {
            $user =& $this->user;
        }
        if (
            $user->is_definite() &&
            $user->is_confirmed &&
            $user->is_active &&
            $user->role != ""
        ) {
            $user_role = $user->role;
        } else {
            $user_role = "guest";
        }
        return $user_role;
    }

    function on_before_run_action() {
        parent::on_before_run_action();
        
        $this->print_lang_menu();
        $this->print_login_state();
        $this->init_yui_core();
    }

    function print_login_state() {
        $user_role = $this->get_user_role();
        if ($user_role == "guest") {
            $template_name = "_not_logged_in.html";
        } else {
            $this->user->print_values();
            $template_name = "_logged_in.html";
        }
        $this->print_file_new("_login_state/{$template_name}", "login_state");
    }

    function init_yui_core() {
        $templates_dir = "_global/yui/init_core";

        $this->init_sys_var("yui_debug", ($this->get_log_debug_level() >= DL_DEBUG));
        $this->init_sys_var("yui_url", create_self_url() . "yui/");

        if ($this->yui_debug) {
            $this->print_file(
                "{$templates_dir}/_core_js_debug.html",
                "_core_js"
            );
        } else {
            $this->print_file(
                "{$templates_dir}/_core_js.html",
                "_core_js"
            );
        }
        $this->print_raw_value(
            "sys:yui_debug_suffix",
            $this->yui_debug ? "" : "-min"
        );

        $this->print_file("{$templates_dir}/init_yui_js.html", "init_yui_core_js");
    }

    function run_access_denied_action() {
        $this->session->save_request_params();

        if ($this->action != "home") {
            $this->add_session_status_message(new ErrorStatusMsg("resource_access_denied"));
        }
        
        $this->create_self_redirect_response(array("action" => "login"));
    }

    function print_menu($params = array()) {
        $user_role = $this->get_user_role();
        $params["xml_filename"] = "_menu_{$user_role}.xml";

        return parent::print_menu($params);
    }

    function create_html_page_template_name() {
        if ($this->page_template_name == "") {
            $user_role = $this->get_user_role();
            if ($this->popup != 0) {
                $popup_page_template_name = "page_{$user_role}_popup.html";
                if ($this->is_file_exist($popup_page_template_name)) {
                    $this->page_template_name = $popup_page_template_name;
                } else {
                    $popup_page_template_name = "page_popup.html";
                    if ($this->is_file_exist($popup_page_template_name)) {
                        $this->page_template_name = $popup_page_template_name;
                    }
                }
            } else if ($this->report != 0) {
                $report_page_template_name = "page_{$user_role}_report.html";
                if ($this->is_file_exist($report_page_template_name)) {
                    $this->page_template_name = $report_page_template_name;
                } else {
                    $report_page_template_name = "page_report.html";
                    if ($this->is_file_exist($report_page_template_name)) {
                        $this->page_template_name = $report_page_template_name;
                    }
                }
            } else if ($this->printable != 0) {
                $printable_page_template_name = "page_{$user_role}_printable.html";
                if ($this->is_file_exist($printable_page_template_name)) {
                    $this->page_template_name = $printable_page_template_name;
                } else {
                    $printable_page_template_name = "page_printable.html";
                    if ($this->is_file_exist($printable_page_template_name)) {
                        $this->page_template_name = $printable_page_template_name;
                    }
                }
            }

            if ($this->page_template_name == "") {
                $page_template_name = "page_{$user_role}.html";
                if ($this->is_file_exist($page_template_name)) {
                    $this->page_template_name = $page_template_name;
                } else {
                    $this->page_template_name = "page.html";
                }
            }
        }
    }

    function print_static_page($page_name, $template_var) {
        $user_role = $this->get_user_role();
        $full_page_name = "{$user_role}_{$page_name}_{$this->lang}";
        if (!$this->is_static_page_file_exist($full_page_name)) {
            $full_page_name = "{$user_role}_{$page_name}";
            if (!$this->is_static_page_file_exist($full_page_name)) {
                return parent::print_static_page($page_name, $template_var);
            }
        }
        return $this->print_static_page_file($full_page_name, $template_var);
    }

    function init_page_template_lang_resources() {
        parent::init_page_template_lang_resources();

        $this->print_file("_global/required_field/body.html", "global:_required_field");
        $this->print_raw_value("sys:current_year", date("Y"));
    }
//
    function action_index() {
        $templates_dir = "index";

        $news_article =& $this->create_db_object("NewsArticle");
        $n_recent_news_articles = $this->get_config_value("recent_news_articles_number");
        $recent_news_articles_list =& $this->create_object(
            "QueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/recent_news_articles",
                "template_var" => "recent_news_articles",
                "obj" => $news_article,
                "query_ex" => array(
                    "order_by" => "created_date DESC, id DESC",
                    "limit" => "0, {$n_recent_news_articles}",
                ),
                "context" => "index_list_item",
            )
        );
        $recent_news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }
//
    function action_home() {
        $user_role = $this->get_user_role();
        switch ($user_role) {
        case "user":
        case "admin":
            $this->create_self_redirect_response(array("action" => "{$user_role}_home"));
            break;
        }
    }

    function action_user_home() {
        $this->create_self_redirect_response(array("action" => "my_account"));
    }

    function action_admin_home() {
        $this->create_self_redirect_response(array("action" => "users_admin"));
    }
//
    function action_login() {
        $templates_dir = "login";

        $user =& $this->create_db_object("User");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "login":
            $context = "login_form";
            
            $user->insert_login_form_extra_fields();
            if ($this->was_user_remembered()) {
                $user->should_remember = 1;
            }

            if ($command == "login") {
                $user->read($context);

                $should_remember = $user->should_remember;

                $messages = $user->validate($context);
                if (count($messages) == 0) {
                    $login = $user->login;
                    $password = $user->password;

                    $user->fetch("user.login = ". qw($login));
                    $context_params = array(
                        "login" => $login,
                        "password" => $password,
                    );
                    $messages = $user->validate("login", $context_params);
                }
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);

                    $user->login = "";
                    $user->password = "";
                    $user->should_remember = $should_remember;
                } else {
                    $saved_request_params = $this->session->get_saved_request_params();
                    if (is_null(get_param_value($saved_request_params, "action", null))) {
                        $saved_request_params = array("action" => "home") + $saved_request_params;
                    }
                    if (!is_null(get_param_value($saved_request_params, "_current_lang", null))) {
                        unset($saved_request_params["_current_lang"]);
                    }
                    $this->session->destroy_saved_request_params();
                    
                    $this->create_self_redirect_response($saved_request_params);

                    $this->create_login_state($user);
                    if ($should_remember) {
                        $this->add_remember_user_id_and_password_cookies($user);
                    } else {
                        $this->remove_remember_user_id_and_password_cookies();
                    }
                    break;
                }
            }
            
            $login_form =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/login_form",
                    "template_var" => "login_form",
                    "obj" => $user,
                    "context" => $context,
                )
            );
            $login_form->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        }
    }

    function &create_login_state($user) {
        return $this->session->create_login_state(
            $this->get_config_value("login_state_idle_timeout"),
            $this->get_config_value("login_state_max_timeout"),
            $user->id
        );
    }

    function destroy_login_state() {
        $this->session->clear();
    }

    function add_remember_user_id_and_password_cookies($user) {
        $cookie_expiration_ts = $this->create_remember_user_cookie_expiration_ts();
        $this->response->add_cookie(new Cookie(
            "user_id",
            $user->id,
            $cookie_expiration_ts
        ));
        $this->response->add_cookie(new Cookie(
            "user_password_hash",
            sha1($user->password),
            $cookie_expiration_ts
        ));
    }

    function remove_remember_user_id_and_password_cookies() {
        if ($this->was_user_remembered()) {
            $this->response->add_cookie(new Cookie(
                "user_id",
                false,
                0
            ));
            $this->response->add_cookie(new Cookie(
                "user_password_hash",
                false,
                0
            ));
        }
    }

    function create_remember_user_cookie_expiration_ts() {
        $cookie_expiration_period_in_days = $this->get_config_value(
            "remember_user_cookie_expiration_period"
        );
        return time() + 60 * 60 * 24 * $cookie_expiration_period_in_days;
    }

    function action_logout() {
        $this->create_self_redirect_response(array("action" => "login"));
        
        $this->destroy_login_state();
        $this->remove_remember_user_id_and_password_cookies();

        $this->add_session_status_message(new OkStatusMsg("logged_out"));
    }
//
    function action_signup() {
        $templates_dir = "signup";
        
        $user =& $this->create_db_object("User");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "signup":
            $context = "signup_form";
            
            $user->insert_signup_form_extra_fields();
            $user->password = "";
            $user->password_confirm = "";

            if ($command == "signup") {
                $user->read($context);
                
                $messages = $user->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $user->is_active = 1;

                    $user->save(true);

                    $this->send_email_signup_form_processed_to_user($user);
                    
                    $this->create_self_action_redirect_response(array(
                        "command" => "signup_almost_completed",
                    ));
                    break;
                }
            }   
        
            $user_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/signup_form",
                    "template_var" => "signup_form",
                    "obj" => $user,
                    "context" => "signup_form",
                )
            );
            $user_editor->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        
        case "signup_almost_completed":
            $this->print_file("signup/body_signup_almost_completed.html", "body");
            break;
        }
    }

    function send_email_signup_form_processed_to_user($user) {
        $email_from = $this->get_config_value("website_email_from");
        $name_from = $this->get_config_value("website_name_from");
        $email_to = $this->get_actual_email_to($user->email);
        $name_to = $user->get_full_name();
        $subject = $this->get_lang_str("email_signup_form_processed_subject");

        $user->print_values();
        $url = create_self_full_url(array(
            "action" => "confirm_signup",
            "user_id" => $user->id,
        ));
        $this->print_value("confirmation_link", $url);
        
        $body = $this->print_file("signup/email_signup_confirmation_sent_to_user.html");

        $email_sender =& $this->create_email_sender();
        $email_sender->From = $email_from;
        $email_sender->Sender = $email_from;
        $email_sender->FromName = trim($name_from);
        $email_sender->AddAddress($email_to, trim($name_to));
        $email_sender->Subject = $subject;
        $email_sender->Body = $body;
        $email_sender->Send();
    }

    function action_confirm_signup() {
        $user =& $this->read_id_fetch_db_object("User");
        if ($user->is_definite() && !$user->is_confirmed) {
            $user->confirm(1);
        }
        $this->print_file("signup/body_signup_confirmed.html", "body");
    }
        
    function action_recover_password() {
        $templates_dir = "recover_password";

        $user =& $this->create_db_object("User");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "recover":
            $context = "recover_password_form";
            
            if ($command == "recover") {
                $user->read($context);

                $messages = $user->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $this->send_email_recover_password_form_processed_to_user($user);
                    
                    $this->create_self_action_redirect_response(array(
                        "command" => "password_sent",
                    ));
                    break;
                }
            }

            $recover_password_form =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/recover_password_form",
                    "template_var" => "recover_password_form",
                    "obj" => $user,
                    "context" => "recover_password_form",
                )
            );
            $recover_password_form->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        
        case "password_sent":
            $this->print_file("recover_password/body_password_sent.html", "body");
            break;
        }
    }

    function send_email_recover_password_form_processed_to_user($user) {
        $email_from = $this->get_config_value("website_email_from");
        $name_from = $this->get_config_value("website_name_from");
        $email_to = $this->get_actual_email_to($user->email);
        $name_to = $user->get_full_name();
        $subject = $this->get_lang_str("email_recover_password_form_processed_subject");
        
        $user->print_values();
        $body = $this->print_file("recover_password/email_password_sent_to_user.html");

        $email_sender =& $this->create_email_sender();
        $email_sender->From = $email_from;
        $email_sender->Sender = $email_from;
        $email_sender->FromName = trim($name_from);
        $email_sender->AddAddress($email_to, trim($name_to));
        $email_sender->Subject = $subject;
        $email_sender->Body = $body;
        $email_sender->Send();
    }
//
    function action_contact_form() {
        $templates_dir = "contact_form";

        $contact_info =& $this->create_db_object("ContactInfo");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "process":
            if ($command == "process") {
                $contact_info->read();

                $messages = $contact_info->validate();
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $this->add_session_status_message(new OkStatusMsg("contact_info.processed"));

                    $this->send_email_contact_form_processed_to_admin($contact_info);

                    $this->create_self_action_redirect_response();
                    break;
                }
            }

            $contact_form =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/contact_form",
                    "template_var" => "contact_form",
                    "obj" => $contact_info,
                )
            );
            $contact_form->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        }
    }

    function send_email_contact_form_processed_to_admin($contact_info) {
        $email_from = $contact_info->email;
        $name_from = "{$contact_info->first_name} {$contact_info->last_name}";
        $email_to = $this->get_actual_email_to($this->get_config_value("contact_form_email_to"));
        $name_to = $this->get_config_value("contact_form_name_to");
        $subject = $this->get_lang_str("email_contact_form_processed_subject");

        $contact_info->print_values();
        $body = $this->print_file("contact_form/email_contact_info_sent_to_admin.html");

        $email_sender =& $this->create_email_sender();
        $email_sender->From = $email_from;
        $email_sender->Sender = $email_from;
        $email_sender->FromName = trim($name_from);
        $email_sender->AddAddress($email_to, trim($name_to));
        $email_sender->Subject = $subject;
        $email_sender->Body = $body;
        $email_sender->Send();
    }
//
    function action_my_account() {
        $templates_dir = "my_account";

        $user_role = $this->get_user_role();

        $user =& $this->user;

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "update":
            $context = "my_account";

            $user->insert_edit_form_extra_fields();

            $field_info =& $user->get_field_info("login");
            $field_info["input"]["type_attrs"]["disabled"] = "disabled";

            $user->password = "";
            $user->password_confirm = "";

            if ($command == "update") {
                $user->read($context);

                $messages = $user->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);

                    $user->password = "";
                    $user->password_confirm = "";
                } else {
                    $this->add_session_status_message(new OkStatusMsg("user.account_updated"));

                    $user->save(
                        false,
                        is_value_empty($user->password) ? "skip_password" : null
                    );

                    $this->create_self_redirect_response(array(
                        "action" => "my_account", 
                    ));
                    break;
                }
            }
            
            $user_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/user_editor",
                    "template_var" => "user_editor",
                    "obj" => $user,
                    "context" => $context,
                )
            );
            $user_editor->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        }
    }

    function action_users_admin() {
        $templates_dir = "users_admin";
        
        $user =& $this->create_db_object("User");
        $user->insert_filters();
        $users_list =& $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/users",
                "template_var" => "users",
                "obj" => $user,
                "default_order_by" => array("created DESC", "id DESC"),
                "filter_form.visible" => true,
                "context" => "users_admin_list_item",
            )
        );
        $users_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_user_edit_admin() {
        $templates_dir = "user_edit_admin";

        $user =& $this->read_id_fetch_db_object("User");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "update":
            $context = "user_edit_admin";

            $user->insert_edit_form_extra_fields();
            if (!$user->is_definite()) {
                // Set initial values for user which will be created by admin
                $user->is_confirmed = 1;
                $user->is_active = 1;
            }
            $user->password = "";
            $user->password_confirm = "";

            if ($command == "update") {
                $user->read($context);

                $messages = $user->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);

                    $user->password = "";
                    $user->password_confirm = "";
                } else {
                    $this->print_status_message_db_object_updated($user);

                    $user->save(
                        false,
                        is_value_empty($user->password) ? "skip_password" : null
                    );

                    $this->create_self_redirect_response(array(
                        "action" => "users_admin",
                    ));
                    break;
                }
            }
            
            $user_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/user_editor",
                    "template_var" => "user_editor",
                    "obj" => $user,
                    "context" => $context,
                )
            );
            $user_editor->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        
        case "delete":
            if ($user->role == "admin" && $user->get_num_admins() <= 1) {
                $this->add_session_status_message(
                    new ErrorStatusMsg("user.cannot_delete_main_admin")
                );

                $this->create_self_redirect_response(array("action" => "users_admin"));
                break;
            }
            $redirect_url_params = array("action" => "users_admin");
            $this->delete_db_object(array(
                "obj" => $user,
                "success_url_params" => $redirect_url_params,
                "error_url_params" => $redirect_url_params,
            ));
            break;
        }
    }
//
    function action_news_articles() {
        $templates_dir = "news_articles";

        $news_article =& $this->create_db_object("NewsArticle");
        $news_article->insert_filters();
        $news_articles_list =& $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/news_articles",
                "template_var" => "news_articles",
                "obj" => $news_article,
                "query_ex" => array(
                    "order_by" => "created_date DESC, id DESC",
                ),    
                "filter_form.visible" => true,
                "context" => "news_articles_list_item",
            )
        );
        $news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_news_articles_admin() {
        $templates_dir = "news_articles_admin";

        $news_article =& $this->create_db_object("NewsArticle");
        $news_article->insert_filters();
        $news_articles_list =& $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/news_articles",
                "template_var" => "news_articles",
                "obj" => $news_article,
                "default_order_by" => array("created_date DESC", "id DESC"),    
                "filter_form.visible" => true,
                "context" => "news_articles_admin_list_item",
            )
        );
        $news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_news_article_view() {
        $templates_dir = "news_article_view";

        $news_article =& $this->read_id_fetch_db_object("NewsArticle");
        $news_article_viewer =& $this->create_object(
            "ObjectViewer",
            array(
                "templates_dir" => "{$templates_dir}/news_article_viewer",
                "template_var" => "news_article_viewer",
                "obj" => $news_article,
                "context" => "news_article_view",
            )
        );
        $news_article_viewer->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_news_article_edit_admin() {
        $templates_dir = "news_article_edit_admin";

        $news_article =& $this->read_id_fetch_db_object("NewsArticle");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "update":
            $context = "news_article_edit_admin";

            if ($command == "update") {
                $news_article->read($context);

                $messages = $news_article->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $this->print_status_message_db_object_updated($news_article);

                    $this->process_uploaded_image(
                        $news_article,
                        "image_id",
                        "image_file",
                        array(
                            "image_processor.class" => $this->get_config_value("image_processor"),
                            "image_processor.actions" => array(
                                array(
                                    "name" => "crop_and_resize",
                                    "width" => $this->get_config_value(
                                        "news_article_image_width"
                                    ),
                                    "height" => $this->get_config_value(
                                        "news_article_image_height"
                                    ),
                                ),
                            ),
                            "is_thumbnail" => 0,
                        )
                    );
                    $this->process_uploaded_image(
                        $news_article,
                        "thumbnail_image_id",
                        "image_file",
                        array(
                            "image_processor.class" => $this->get_config_value("image_processor"),
                            "image_processor.actions" => array(
                                array(
                                    "name" => "crop_and_resize",
                                    "width" => $this->get_config_value(
                                        "news_article_thumbnail_image_width"
                                    ),
                                    "height" => $this->get_config_value(
                                        "news_article_thumbnail_image_height"
                                    ),
                                ),
                                // This is example of second image processor action
                                // Remove if grayscale is not needed (almost always ;) )
                                array(
                                    "name" => "convert_to_grayscale",
                                ),
                            ),
                            "is_thumbnail" => 1,
                        )
                    );

                    $this->process_uploaded_file($news_article, "file_id", "file");

                    $news_article->save();
                    
                    $this->create_self_redirect_response(array("action" => "news_articles_admin"));
                    break;
                }
            }
                    
            $news_article_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/news_article_editor",
                    "template_var" => "news_article_editor",
                    "obj" => $news_article,
                    "context" => "news_article_edit_admin",
                )
            );
            $news_article_editor->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        
        case "delete":
            $this->delete_db_object(array(
                "obj" => $news_article,
                "success_url_params" => array("action" => "news_articles"),
                "error_url_params" => array("action" => "news_articles"),
            ));
            break;
        case "delete_image":
            $this->delete_db_object_image($news_article, "image_id");
            
            $this->add_session_status_message(new OkStatusMsg("news_article.image_deleted"));

            $this->create_self_action_redirect_response(array(
                "news_article_id" => $news_article->id,
            ));
            break;
        case "delete_file":
            $this->delete_db_object_file($news_article, "file_id");
            
            $this->add_session_status_message(new OkStatusMsg("news_article.file_deleted"));

            $this->create_self_action_redirect_response(array(
                "news_article_id" => $news_article->id,
            ));
            break;
        }
    }

    function action_news_articles_rss() {
        $templates_dir = "news_articles_rss";

        $feed_creator =& $this->create_object("UniversalFeedCreator");

        $feed_creator->title = $this->get_lang_str("news_article.rss_feed.title");
        $feed_creator->description = $this->get_lang_str("news_article.rss_feed.description");
        $feed_creator->link = create_self_full_url(
            array(
                "action" => "news_articles",
            ),
            $this->lang
        ); 
//        $feed_creator->feedURL = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}"; 
//        $feed_creator->syndicationURL = "http://www.dailyphp.net/".$PHP_SELF;

        $n_recent_rss_news_articles = $this->get_config_value("recent_rss_news_articles_number");
        $news_articles = $this->fetch_db_objects_list(
            "NewsArticle",
            array(
                "order_by" => "created_date DESC, id DESC",
                "limit" => "0, {$n_recent_rss_news_articles}",
            )
        );
        foreach ($news_articles as $news_article) {
            $news_article->print_values(array(
                "templates_dir" => "news_articles_rss",
                "context" => "news_articles_rss_list_item",
            ));
            
            $feed_item = new FeedItem();
            $feed_item->title = $news_article->title;
            $feed_item->link = create_self_full_url(
                array(
                    "action" => "news_article_view",
                    "news_article_id" => $news_article->id,
                ),
                $this->lang
            );
            $feed_item->description = $this->print_file(
                "{$templates_dir}/feed_item_description.html"
            );
            $feed_item->date = $this->get_timestamp_from_db_date($news_article->created_date);
            $feed_item->source = create_self_full_url();
            $feed_item->author = "admin";
            
            $feed_creator->addItem($feed_item);
        }

        $rss_content = $feed_creator->createFeed("RSS2.0");
        $this->create_rss_document_response($rss_content);
    }
//
    function action_newsletters() {
        $templates_dir = "newsletters";

        $newsletter =& $this->create_db_object("Newsletter");
        $newsletters_list =& $this->create_object(
            "PagedQueryObjectsList",
             array(
                 "templates_dir" => "{$templates_dir}/newsletters",
                 "template_var" => "newsletters",
                 "obj" => $newsletter,
                 "default_order_by" => array("sent_date DESC"),    
                 "filter_form.visible" => true,
                 "context" => "newsletters_list_item",
             )
        );
        $newsletters_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_newsletter_view() {
        $templates_dir = "newsletter_view";

        $newsletter =& $this->read_id_fetch_db_object("Newsletter");
        $newsletter_viewer =& $this->create_object(
            "ObjectViewer",
            array(
                "templates_dir" => "{$templates_dir}/newsletter_viewer",
                "template_var" => "newsletter_viewer",
                "obj" => $newsletter,
                "context" => "newsletter_view",
            )
        );
        $newsletter_viewer->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_newsletter_edit() {
        $templates_dir = "newsletter_edit";

        $newsletter =& $this->read_id_fetch_db_object("Newsletter");
        if ($newsletter->is_definite()) {
            $this->create_self_redirect_response(array(
                "action" => "newsletters",
            ));
            return;
        }

        $command = (string) param("command");

        switch ($command) {
        case "":
        case "update":
            $context = "newsletter_edit";

            if ($command == "update") {
                $newsletter->read($context);

                $messages = $newsletter->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $this->print_status_message_db_object_updated($newsletter);

                    $this->process_uploaded_image(
                        $newsletter,
                        "image_id",
                        "image_file",
                        array(
                            "image_processor.class" => $this->get_config_value("image_processor"),
                            "image_processor.actions" => array(
                                array(
                                    "name" => "crop_and_resize",
                                    "width" => $this->get_config_value(
                                        "newsletter_image_width"
                                    ),
                                    "height" => $this->get_config_value(
                                        "newsletter_image_height"
                                    ),
                                ),
                            ),
                            "is_thumbnail" => 0,
                        )
                    );
                    $this->process_uploaded_image(
                        $newsletter,
                        "thumbnail_image_id",
                        "image_file",
                        array(
                            "image_processor.class" => $this->get_config_value("image_processor"),
                            "image_processor.actions" => array(
                                array(
                                    "name" => "crop_and_resize",
                                    "width" => $this->get_config_value(
                                        "newsletter_thumbnail_image_width"
                                    ),
                                    "height" => $this->get_config_value(
                                        "newsletter_thumbnail_image_height"
                                    ),
                                ),
                            ),
                            "is_thumbnail" => 1,
                        )
                    );

                    $this->process_uploaded_file($newsletter, "file_id", "file");
                    
                    $newsletter->save();

                    $this->send_email_newsletter_to_subscribed_users(
                        $newsletter,
                        "{$templates_dir}/email_sent_to_user"
                    );

                    $this->create_self_action_redirect_response(array(
                        "action" => "newsletters",
                    ));
                }
            }
                    
            $newsletter_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/newsletter_editor",
                    "template_var" => "newsletter_editor",
                    "obj" => $newsletter,
                    "context" => $context,
                )
            );
            $newsletter_editor->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        }
    }

    function send_email_newsletter_to_subscribed_users($newsletter, $templates_dir) {
        $subscribed_users =& $this->fetch_db_objects_list(
            "User", 
            array(
                "from" =>
                    "INNER JOIN {%user_subscription_table%} AS user_subscription " .
                        "ON user_subscription.user_id = user.id",
                "where" =>
                    "user_subscription.newsletter_category_id = " .
                        "{$newsletter->newsletter_category_id}",
            )
        );
        
        $email_from = $this->get_config_value("website_email_from");
        $name_from = $this->get_config_value("website_name_from");
        $subject = $newsletter->title;

        $attachment_file = $this->fetch_db_object("File", $newsletter->file_id);
        $attachment_image = $this->fetch_db_object("Image", $newsletter->image_id);
        $newsletter->print_values(array(
            "templates_dir" => $templates_dir,
            "context" => "newsletter_view",
        ));
        $body = $this->print_file("{$templates_dir}/email_sent_to_user.html");
        
        $email_sender =& $this->create_email_sender();
        $email_sender->From = $email_from;
        $email_sender->Sender = $email_from;
        $email_sender->FromName = trim($name_from);
        if ($attachment_image->id != 0) {
            $email_sender->AddStringImageAttachment(
                $attachment_image->content,
                "image.jpg",
                "image.jpg",
                "base64",
                "image/jpeg"
            );
        }
        if ($attachment_file->id != 0) {
            $email_sender->AddStringAttachment(
                $attachment_file->content, 
                $attachment_file->filename
            );
        }
        $email_sender->Subject = $subject;
        $email_sender->Body = $body;
        
        foreach ($subscribed_users as $subscribed_user) {
            $name_to = $subscribed_user->get_full_name();
            $email_to = $this->get_actual_email_to($subscribed_user->email);

            $email_sender->ClearAddresses();
            $email_sender->AddAddress($email_to, $name_to);
            $email_sender->Send();
        }
    }
// 
    function action_newsletter_categories() {
        $templates_dir = "newsletter_categories";

        $newsletter_category =& $this->create_db_object("NewsletterCategory");
        $newsletter_categories_list =& $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/newsletter_categories",
                "template_var" => "newsletter_categories",
                "obj" => $newsletter_category,
                "query_ex" => array(
                    "order_by" => "name ASC",
                ),
                "context" => "newsletter_categories_list_item",
            )
        );
        $newsletter_categories_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_newsletter_category_edit() {
        $templates_dir = "newsletter_category_edit";

        $newsletter_category =& $this->read_id_fetch_db_object("NewsletterCategory");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "update":
            $context = "newsletter_category_edit";

            if ($command == "update") {
                $newsletter_category->read($context);

                $messages = $newsletter_category->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $this->print_status_message_db_object_updated($newsletter_category);
                    $newsletter_category->save();
                    $this->create_self_redirect_response(array(
                        "action" => "newsletter_categories",
                    ));
                    break;
                }
            }
                    
            $newsletter_category_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/newsletter_category_editor",
                    "template_var" => "newsletter_category_editor",
                    "obj" => $newsletter_category,
                    "context" => $context,
                )
            );
            $newsletter_category_editor->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
            break;

        case "activate_deactivate":
            $newsletter_category->activate_deactivate();

            $this->add_session_status_message(new OkStatusMsg("newsletter_category.updated"));
            
            $this->create_self_redirect_response(array("action" => "newsletter_categories"));
            break;
        }
    }

    function action_user_subscription() {
        $templates_dir = "user_subscription";

        $newsletter_category =& $this->create_db_object("NewsletterCategory");
        $newsletter_category->insert_list_extra_fields($this->user->id);
        $categories_to_subscribe_list =& $this->create_object(
            "PagedQueryObjectsList",
             array(
                 "templates_dir" => "{$templates_dir}/categories_to_subscribe",
                 "template_var" => "categories_to_subscribe",
                 "obj" => $newsletter_category,
                 "query_ex" => array(
                    "where" => "newsletter_category.is_active = 1",
                    "order_by" => "name ASC",
                 ), 
                 "pager.visible" => false,
                 "context" => "user_subscription_list_item",
             )
        );
        $categories_to_subscribe_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    // !!NB: Check inactive categories!
    function action_user_subscription_edit() {
        $command = (string) param("command");
        switch ($command) {
        case "update":
            $user_id = $this->user->id;
            $newsletter_categories_is_checked = param_array("newsletter_category_is_checked");

            $user_subscription =& $this->create_db_object("UserSubscription");
            $user_subscription->del_where("user_id = {$user_id}");

            $newsletter_category =& $this->create_db_object("NewsletterCategory");
            foreach ($newsletter_categories_is_checked as
                $newsletter_category_id => $newsletter_category_checked_status
            ) {
                if (
                    $newsletter_category->fetch(
                        "newsletter_category.id = {$newsletter_category_id}"
                    )
                ) {
                    $user_subscription->user_id = $user_id;
                    $user_subscription->newsletter_category_id = $newsletter_category_id;
                    $user_subscription->store();
                    $user_subscription->reset_field_values();
                }
            }
            break;
        }

        $this->add_session_status_message(new OkStatusMsg("user_subscription.updated"));
        
        $this->create_self_redirect_response(array("action" => "user_subscription"));
    }
//
    function action_categories() {
        $templates_dir = "categories";

        $current_category_ids = $this->_read_and_print_current_category_ids();

        $avail_obj_names = array(
            "category1" => "Category1",
            "category2" => "Category2",
            "category3" => "Category3",
        );
        $obj_name = (string) param("obj");
        
        $command = (string) param("command");
        switch ($command) {
        case "edit":
        case "update":
            if (!in_array($obj_name, array_keys($avail_obj_names))) {
                break;
            }
            $obj =& $this->read_id_fetch_db_object($avail_obj_names[$obj_name], "1", "obj_id");
            if (!$obj->is_definite()) {
                switch ($obj_name) {
                case "category1":
                    $parent_id_field_name = null;
                    break;
                case "category2":
                    $parent_id_field_name = "category1_id";
                    break;
                case "category3":
                    $parent_id_field_name = "category2_id";
                    break;
                }
                if (!is_null($parent_id_field_name)) {
                    $obj->set_field_value(
                        $parent_id_field_name,
                        $current_category_ids["current_{$parent_id_field_name}"]
                    );
                }
            }

            if ($command == "update") {
                $obj->read();

                $messages = $obj->validate();
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $this->print_status_message_db_object_updated($obj);

                    $was_definite = $obj->save();

                    if ($was_definite) {
                        $suburl_params = $current_category_ids;
                    } else {
                        $suburl_params = array();
                        switch ($obj_name) {
                        case "category1":
                            $suburl_params["current_category1_id"] = $obj->id;
                            break;
                        case "category2":
                            $suburl_params["current_category1_id"] =
                                $current_category_ids["current_category1_id"];
                            $suburl_params["current_category2_id"] = $obj->id;
                            break;
                        case "category3":
                            $suburl_params["current_category1_id"] =
                                $current_category_ids["current_category1_id"];
                            $suburl_params["current_category2_id"] =
                                $current_category_ids["current_category2_id"];
                            $suburl_params["current_category3_id"] = $obj->id;
                            break;
                        }
                    }

                    $this->create_self_action_redirect_response($suburl_params);
                    return;
                }
            }

            $category_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/{$obj_name}_editor",
                    "template_var" => "category_editor",
                    "obj" => $obj,
                    "page_title_resource" => "categories_{$obj_name}_edit",
                )
            );
            $category_editor->print_values();
            break;
        
        case "delete":
            if (!in_array($obj_name, array_keys($avail_obj_names))) {
                break;
            }
            $obj =& $this->read_id_fetch_db_object($avail_obj_names[$obj_name], "1", "obj_id");

            $prev_obj =& $obj->fetch_neighbor_db_object(
                "prev",
                $obj->get_position_where_str()
            );
            $was_deleted = $this->delete_db_object(array(
                "obj" => $obj,
                "should_redirect" => false,
            ));

            if ($was_deleted) {
                $suburl_params = array();
                switch ($obj_name) {
                case "category1":
                    $suburl_params["current_category1_id"] = $prev_obj->id;
                    break;
                
                case "category2":
                    $suburl_params["current_category1_id"] =
                        $current_category_ids["current_category1_id"];
                    if (!is_null($prev_obj)) {
                        $suburl_params["current_category2_id"] = $prev_obj->id;
                    }
                    break;
                
                case "category3":
                    $suburl_params["current_category1_id"] =
                        $current_category_ids["current_category1_id"];
                    $suburl_params["current_category2_id"] =
                        $current_category_ids["current_category2_id"];
                    if (!is_null($prev_obj)) {
                        $suburl_params["current_category3_id"] = $prev_obj->id;
                    }
                    break;
                }
            } else {
                $suburl_params = $current_category_ids;
            }
            
            $this->create_self_action_redirect_response($suburl_params);
            return;
        
        case "move":
            if (!in_array($obj_name, array_keys($avail_obj_names))) {
                break;
            }
            
            $obj =& $this->read_id_fetch_db_object($avail_obj_names[$obj_name], "1", "obj_id");
            $obj->update_position((string) param("to"));

            $this->create_self_action_redirect_response($current_category_ids);
            return;
        }

        $category_browser =& $this->create_object(
            "CategoryBrowser",
            array(
                "templates_dir" => "{$templates_dir}/category_browser",
                "template_var" => "category_browser",
            )
        );
        $category_browser->set_current_ids($current_category_ids);
        $category_browser->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function _read_and_print_current_category_ids() {
        $category_ids = array(
            "current_category1_id" => (int) param("current_category1_id"),
            "current_category2_id" => (int) param("current_category2_id"),
            "current_category3_id" => (int) param("current_category3_id"),
        );
        foreach ($category_ids as $category_id_name => $category_id) {
            $this->print_primary_key_value($category_id_name, $category_id);
        }
        $this->print_value("current_category_ids_suburl", create_suburl($category_ids));
        return $category_ids;
    }
//
    function action_products() {
        $templates_dir = "products";

        $product =& $this->create_db_object("Product");
        $product->insert_filters();
        $products_list =& $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/products",
                "template_var" => "products",
                "obj" => $product,
                "default_order_by" => array(
                    "category1_position ASC",
                    "category2_position ASC",
                    "category3_position ASC",
                ),
                "filter_form.visible" => true,
                "context" => "products_list_item",
            )
        );
        $products_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_product_edit() {
        $templates_dir = "product_edit";

        $product =& $this->read_id_fetch_db_object("Product");

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "update":
            $context = "product_edit";
                
            if ($command == "update") {
                $product->read($context);

                $messages = $product->validate($context);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $this->print_status_message_db_object_updated($product);

                    $this->process_uploaded_image(
                        $product,
                        "primary_image_id",
                        "primary_image_file",
                        array(
                            "image_processor.class" => $this->get_config_value("image_processor"),
                            "image_processor.actions" => array(
                                array(
                                    "name" => "crop_and_resize",
                                    "width" => $this->get_config_value(
                                        "product_primary_image_width"
                                    ),
                                    "height" => $this->get_config_value(
                                        "product_primary_image_height"
                                    ),
                                ),
                            ),
                            "is_thumbnail" => 0,
                        )
                    );
                    $this->process_uploaded_image(
                        $product,
                        "primary_thumbnail_image_id",
                        "primary_image_file",
                        array(
                            "image_processor.class" => $this->get_config_value("image_processor"),
                            "image_processor.actions" => array(
                                array(
                                    "name" => "crop_and_resize",
                                    "width" => $this->get_config_value(
                                        "product_primary_thumbnail_image_width"
                                    ),
                                    "height" => $this->get_config_value(
                                        "product_primary_thumbnail_image_height"
                                    ),
                                ),
                            ),
                            "is_thumbnail" => 1,
                        )
                    );
                    
                    $product->save();

                    $product_image =& $this->create_db_object("ProductImage");
                    $product_image->product_id = $product->id;

                    if (was_file_uploaded("image_file")) {
                        $this->process_uploaded_image(
                            $product_image,
                            "image_id",
                            "image_file",
                            array(
                                "image_processor.class" => $this->get_config_value(
                                    "image_processor"
                                ),
                                "image_processor.actions" => array(
                                    array(
                                        "name" => "crop_and_resize",
                                        "width" => $this->get_config_value(
                                            "product_image_width"
                                        ),
                                        "height" => $this->get_config_value(
                                            "product_image_height"
                                        ),
                                    ),
                                ),
                                "is_thumbnail" => 0,
                            )
                        );
                        $this->process_uploaded_image(
                            $product_image,
                            "thumbnail_image_id",
                            "image_file",
                            array(
                                "image_processor.class" => $this->get_config_value(
                                    "image_processor"
                                ),
                                "image_processor.actions" => array(
                                    array(
                                        "name" => "crop_and_resize",
                                        "width" => $this->get_config_value(
                                            "product_thumbnail_image_width"
                                        ),
                                        "height" => $this->get_config_value(
                                            "product_thumbnail_image_height"
                                        ),
                                    ),
                                ),
                                "is_thumbnail" => 1,
                            )
                        );
                        $product_image->save();
                    }

                    $next_page = (string) param("next_page");
                    if ($next_page == "products") {
                        $this->create_self_redirect_response(array(
                            "action" => "products",
                        ));
                    } else {
                        $this->create_self_action_redirect_response(array(
                            "action" => "product_edit",
                            "product_id" => $product->id,
                        ));
                    }
                    break;
                }
            }

            $product_editor =& $this->create_object(
                "ObjectEditor",
                array(
                    "templates_dir" => "{$templates_dir}/product_editor",
                    "template_var" => "product_editor",
                    "obj" => $product,
                    "context" => $context,
                )
            );
            $product_editor->print_values();
  
            $this->print_file("{$templates_dir}/body.html", "body");
            break;
  
        case "delete":
            $this->delete_db_object(array(
                "obj" => $product,
                "success_url_params" => array("action" => "products"),
                "error_url_params" => array("action" => "products"),
            ));
            break;

        case "delete_primary_image":
            $this->delete_db_object_image($product, "primary_image_id");
            
            $this->add_session_status_message(new OkStatusMsg("product.primary_image_deleted"));

            $this->create_self_action_redirect_response(array(
                "product_id" => $product->id,
            ));
            break;

        case "delete_image":
            $product_image =& $this->read_id_fetch_db_object("ProductImage");
            
            if (
                !$product->is_definite() ||
                !$product_image->is_definite() ||
                $product_image->product_id != $product->id
            ) {
                $this->create_self_redirect_response(array(
                    "action" => "products",
                ));
                break;
            }
            
            $product_image->del();
            
            $this->add_session_status_message(new OkStatusMsg("product.image_deleted"));

            $this->create_self_action_redirect_response(array(
                "product_id" => $product->id,
            ));
            break;

        case "move_image":
            $product_image =& $this->read_id_fetch_db_object("ProductImage");
            
            if (
                !$product->is_definite() ||
                !$product_image->is_definite() ||
                $product_image->product_id != $product->id
            ) {
                $this->create_self_redirect_response(array(
                    "action" => "products",
                ));
                break;
            }

            $product_image->update_position((string) param("to"));

            $this->create_self_action_redirect_response(array(
                "product_id" => $product->id,
            ));
            break;
        }
    }

}

?>