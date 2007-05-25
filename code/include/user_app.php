<?php

class UserApp extends CustomApp {

    // Current logged in user
    var $user;

    function UserApp() {
        parent::CustomApp("UserApp", "user");

        $e = array("roles" => array("guest", "user", "admin"));
        $u = array("roles" => array("user"));
        $a = array("roles" => array("admin"));
        $u_a = array("roles" => array("user", "admin"));

        $this->actions = array(
            // Common
            "change_lang" => $e,
            "pg_static" => $e,
            "get_image" => $e,
            "get_file" => $e,

            // Index and Home pages
            "pg_index" => $e,
            "pg_home" => $u_a,

            // Login/Signup/Confirm/RecoverPassword
            "pg_login" => $e,
            "login" => $e,
            "logout" => $e,
            "pg_signup" => $e,
            "signup" => $e,
            "pg_signup_almost_completed" => $e,
            "confirm_signup" => $e,
            "pg_recover_password" => $e,
            "recover_password" => $e,
            "pg_recover_password_sent" => $e,

            // Contact form
            "pg_contact_form" => $e,
            "process_contact_form" => $e,

            // User management
            "pg_users" => $a,
            "pg_user_view" => $u_a,
            "pg_user_edit" => $u_a,
            "update_user" => $u_a,
            "delete_user" => $a,

            // News articles
            "pg_news_articles" => $e,
            "pg_news_article_view" => $e,
            "pg_news_article_edit" => $a,
            "update_news_article" => $a,
            "delete_news_article" => $a,
            "delete_news_article_image" => $a,
            "delete_news_article_file" => $a,

            // Categories management
            "pg_categories" => $a,
            "pg_category1_edit" => $a,
            "update_category1" => $a,
            "delete_category1" => $a,
            "move_category1" => $a,
            "pg_category2_edit" => $a,
            "update_category2" => $a,
            "delete_category2" => $a,
            "move_category2" => $a,
            "pg_category3_edit" => $a,
            "update_category3" => $a,
            "delete_category3" => $a,
            "move_category3" => $a,

            "pg_products" => $a,
            "pg_product_edit" => $a,
            "delete_product" => $a,
        );
    }
//
    function get_current_lang() {
        $cur_lang = $this->get_current_lang_from_session();
        if (is_null($cur_lang)) {
            $cur_lang = $this->get_current_lang_from_cookie();
        }
        if (!$this->is_valid_lang($cur_lang)) {
            $cur_lang = $this->dlang;
        }
        return $cur_lang;
    }

    function get_current_lang_from_cookie() {
        return get_param_value($_COOKIE, "current_lang", null);
    }

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
                        $this->user->set_indefinite();
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
            $user = $this->user;
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

    function run_access_denied_action() {
        $this->session->save_request_params();

        if ($this->action != "pg_home") {
            $this->add_session_status_message(new ErrorStatusMsg("resource_access_denied"));
        }
        
        $this->create_self_redirect_response(array("action" => "pg_login"));
    }

    function print_menu($params = array()) {
        $user_role = $this->get_user_role();
        $params["xml_filename"] = "menu_{$user_role}.xml";

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

    function action_change_lang() {
        parent::action_change_lang();

        $this->add_current_lang_cookie();
    }
//
    function action_pg_index() {
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
                    "order_by" => "created DESC, id DESC",
                    "limit" => "0, {$n_recent_news_articles}",
                ),
                "context" => "list_item",
            )
        );
        $recent_news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }
//
    function action_pg_home() {
        $user_role = $this->get_user_role();
        switch ($user_role) {
        case "user":
        case "admin":
            $this->{"pg_{$user_role}_home_page"}();
            break;
        }
    }

    function pg_user_home_page() {
        $this->create_self_redirect_response(array("action" => "pg_user_view"));
    }

    function pg_admin_home_page() {
        $this->create_self_redirect_response(array("action" => "pg_users"));
    }
//
    function action_pg_login() {
        $templates_dir = "login";

        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user =& $this->create_db_object("User");
            $user->insert_login_form_extra_fields();
            if ($this->was_user_remembered()) {
                $user->should_remember = 1;
            }
        } else {
            $user->password = "";
        }
        $user_edit =& $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/login_form",
                "template_var" => "login_form",
                "obj" => $user,
                "context" => "login_form",
            )
        );
        $user_edit->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_login() {
        $context = "login_form";

        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user =& $this->create_db_object("User");
            $user->insert_login_form_extra_fields();
            $user->read($context);
        }
        $should_remember = $user->should_remember;

        $messages = $user->validate(null, $context);
        if (count($messages) == 0) {
            $login = $user->login;
            $password = $user->password;

            $user->fetch("user.login = ". qw($login));
            $context_params = array(
                "login" => $login,
                "password" => $password,
            );
            $messages = $user->validate(null, "login", $context_params);
        }
        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_login", array("user" => $user));
        } else {
            $saved_request_params = $this->session->get_saved_request_params();
            if (is_null(get_param_value($saved_request_params, "action", null))) {
                $saved_request_params = array("action" => "pg_home") + $saved_request_params;
            }
            $this->session->destroy_saved_request_params();
            
            $this->create_self_redirect_response($saved_request_params);

            $this->create_login_state($user);
            if ($should_remember) {
                $this->add_remember_user_id_and_password_cookies($user);
            } else {
                $this->remove_remember_user_id_and_password_cookies();
            }
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
        $this->session->destroy_login_state();
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

    function add_current_lang_cookie() {
        $cookie_expiration_ts = $this->create_remember_user_cookie_expiration_ts();
        $this->response->add_cookie(new Cookie(
            "current_lang",
            $this->lang,
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
        $this->add_session_status_message(new OkStatusMsg("logged_out"));
        $this->create_self_redirect_response(array("action" => "pg_login"));
        $this->destroy_login_state();
        $this->remove_remember_user_id_and_password_cookies();
    }
//
    function action_pg_signup() {
        $templates_dir = "signup";

        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user =& $this->create_db_object("User");
            $user->insert_signup_form_extra_fields();
        } else {
            $user->password = "";
            $user->password_confirm = "";
        }
        $user_edit =& $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/signup_form",
                "template_var" => "signup_form",
                "obj" => $user,
                "context" => "signup_form",
            )
        );
        $user_edit->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_signup() {
        $context = "signup_form";

        $user =& $this->create_db_object("User");
        $user->insert_signup_form_extra_fields();
        $user->read($context);

        $messages = $user->validate(null, $context);
        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_signup", array("user" => $user));
        } else {
            $user->is_active = 1;
            $user->save(true);

            $this->send_email_signup_form_processed_to_user($user);
            $this->create_self_redirect_response(array("action" => "pg_signup_almost_completed"));
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

    function action_pg_signup_almost_completed() {
        $this->print_file("signup/body_signup_almost_completed.html", "body");
    }

    function action_confirm_signup() {
        $user =& $this->read_id_fetch_db_object("User");
        if ($user->is_definite() && !$user->is_confirmed) {
            $user->confirm(1);
        }
        $this->print_file("signup/body_signup_confirmed.html", "body");
    }
        
    function action_pg_recover_password() {
        $templates_dir = "recover_password";

        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user =& $this->create_db_object("User");
        }
        $recover_password_form =& $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/recover_password_form",
                "template_var" => "recover_password_form",
                "obj" => $user,
                "context" => "recover_password_form",
            )
        );
        $recover_password_form->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_recover_password() {
        $context = "recover_password_form";

        $user =& $this->create_db_object("User");
        $user->read($context);

        $messages = $user->validate(null, $context);
        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_recover_password", array("user" => $user));
        } else {
            $this->send_email_recover_password_form_processed_to_user($user);
            $this->create_self_redirect_response(array("action" => "pg_recover_password_sent"));
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

    function action_pg_recover_password_sent() {
        $this->print_file("recover_password/body_password_sent.html", "body");
    }
//
    function action_pg_contact_form() {
        $templates_dir = "contact_form";

        $contact_info = get_param_value($this->action_params, "contact_info", null);
        if (is_null($contact_info)) {
            $contact_info =& $this->create_db_object("ContactInfo");
        }
        $contact_form =& $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/contact_form",
                "template_var" => "contact_form",
                "obj" => $contact_info,
            )
        );
        $contact_form->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_process_contact_form() {
        $contact_info =& $this->create_db_object("ContactInfo");
        $contact_info_old = $contact_info;
        $contact_info->read();

        $messages = $contact_info->validate($contact_info_old);
        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_contact_form", array("contact_info" => $contact_info));
        } else {
            $this->send_email_contact_form_processed_to_admin($contact_info);
            
            $this->add_session_status_message(new OkStatusMsg("contact_info.processed"));
            $this->create_self_redirect_response(array("action" => "pg_contact_form"));
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
    function action_pg_users() {
        $templates_dir = "users";
        
        $user =& $this->create_db_object("User");
        $users_list =& $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/users",
                "template_var" => "users",
                "obj" => $user,
                "default_order_by" => array("created DESC", "id DESC"),
                "filter_form.visible" => true,
                "context" => "list_item",
            )
        );
        $users_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function read_id_fetch_user() {
        $user_role = $this->get_user_role();
        if ($user_role == "user") {
            $user =& $this->user;
        } else {
            $user =& $this->read_id_fetch_db_object("User");
        }
        return $user;
    }

    function action_pg_user_view() {
        $templates_dir = "user_view";

        $user_role = $this->get_user_role();
        if ($user_role == "admin") {
            $context = "view_by_admin";
        } else {
            $context = "view_by_user";
        }

        $user = $this->read_id_fetch_user();
        $user_view =& $this->create_object(
            "ObjectView",
            array(
                "templates_dir" => "{$templates_dir}/user_view",
                "template_var" => "user_view",
                "obj" => $user,
                "context" => $context,
            )
        );
        $user_view->print_values();

        if ($user_role == "user") {
            $this->print_head_and_page_titles("pg_user_view_my_account");
        }

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_pg_user_edit() {
        $templates_dir = "user_edit";

        $user_role = $this->get_user_role();
        if ($user_role == "admin") {
            $context = "edit_form_by_admin";
        } else {
            $context = "edit_form_by_user";
        }

        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user = $this->read_id_fetch_user();
            $user->insert_edit_form_extra_fields();
            if (!$user->is_definite()) {
                // Set initial values for user which will be created by admin
                $user->is_confirmed = 1;
                $user->is_active = 1;
            }
        }
        if ($user_role == "user" && $user->is_definite()) {
            $field_info =& $user->get_field_info("login");
            $field_info["input"]["type_attrs"]["disabled"] = "disabled";
        }
        $user->password = "";
        $user->password_confirm = "";

        $user_edit =& $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/user_edit",
                "template_var" => "user_edit",
                "obj" => $user,
                "context" => $context,
            )
        );
        $user_edit->print_values();

        if ($user_role == "user") {
            $this->print_head_and_page_titles("pg_user_edit_my_account");
        }

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_update_user() {
        $user = $this->read_id_fetch_user();
        $user->insert_edit_form_extra_fields();
        $user_old = $user;

        $field_names_to_not_read = array();
        $user_role = $this->get_user_role();

        $context = ($user_role == "admin") ? "edit_form_by_admin" : "edit_form_by_user";

        $user->read($context);

        $messages = $user->validate($user_old, $context);
        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_user_edit", array("user" => $user));
        } else {
            $field_names_to_not_update = $field_names_to_not_read;
            if (is_value_empty($user->password)) {
                $field_names_to_not_update[] = "password";
            }
            
            if ($user_old->is_definite()) {
                $user->update(null, $field_names_to_not_update);
            } else {
                $user->store();
            }

            $this->print_status_message_db_object_updated($user_old);

            if ($user_role == "admin") {
                $this->create_self_redirect_response(array("action" => "pg_users"));
            } else {
                $this->create_self_redirect_response(array(
                    "action" => "pg_user_view", 
                    "user_id" => $user->id, 
                ));
            }
        }
    }

    function action_delete_user() {
        $user =& $this->read_id_fetch_db_object("User");
        if ($user->role == "admin") {
            if ($user->get_num_admins() <= 1) {
                $this->add_session_status_message(
                    new ErrorStatusMsg("user.cannot_delete_main_admin")
                );
                $this->create_self_redirect_response(array("action" => "pg_users"));
                return;
            }
        }
        $redirect_url_params = array("action" => "pg_users");
        $this->delete_db_object(array(
            "obj" => $user,
            "success_url_params" => $redirect_url_params,
            "error_url_params" => $redirect_url_params,
        ));
    }
//
    function action_pg_news_articles() {
        $templates_dir = "news_articles";

        $user_role = $this->get_user_role();
        if ($user_role == "admin") {
            $templates_subdir = "news_articles_admin";
            $default_order_by = array(
                "default_order_by" => array("created DESC", "id DESC"),    
            );
            $context = "list_item_admin";
        } else {
            $templates_subdir = "news_articles";
            $default_order_by = array(
                "query_ex" => array(
                    "order_by" => "created DESC, id DESC",
                ),    
            );
            $context = "list_item";
        }
        
        $news_article =& $this->create_db_object("NewsArticle");
        $news_articles_list =& $this->create_object(
            "PagedQueryObjectsList",
            array_merge(
                array(
                    "templates_dir" => "{$templates_dir}/{$templates_subdir}",
                    "template_var" => "news_articles",
                    "obj" => $news_article,
                    "filter_form.visible" => true,
                    "context" => $context,
                ),
                $default_order_by
            )
        );
        $news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_pg_news_article_view() {
        $templates_dir = "news_article_view";

        $news_article =& $this->read_id_fetch_db_object("NewsArticle");
        $news_article_view =& $this->create_object(
            "ObjectView",
            array(
                "templates_dir" => "{$templates_dir}/news_article_view",
                "template_var" => "news_article_view",
                "obj" => $news_article,
                "context" => "view",
            )
        );
        $news_article_view->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_pg_news_article_edit() {
        $templates_dir = "news_article_edit";

        $news_article = get_param_value($this->action_params, "news_article", null);
        if (is_null($news_article)) {
            $news_article =& $this->read_id_fetch_db_object("NewsArticle");
        }
        $news_article_edit =& $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/news_article_edit",
                "template_var" => "news_article_edit",
                "obj" => $news_article,
                "context" => "edit",
            )
        );
        $news_article_edit->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_update_news_article() {
        $news_article =& $this->read_id_fetch_db_object("NewsArticle");
        $news_article_old = $news_article;
        $news_article->read();

        $messages = $news_article->validate($news_article_old);
        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_news_article_edit", array("news_article" => $news_article));
        } else {
            $this->process_uploaded_image(
                $news_article,
                "image_id",
                "image_file",
                array(
                    "image_processor.class" => $this->get_config_value("image_processor"),
                    "image_processor.actions" => array(
                        array(
                            "name" => "crop_and_resize",
                            "width" => $this->get_config_value("news_article_image_width"),
                            "height" => $this->get_config_value("news_article_image_height"),
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
            $this->print_status_message_db_object_updated($news_article_old);
            $this->create_self_redirect_response(array("action" => "pg_news_articles"));
        }
    }

    function action_delete_news_article() {
        $news_article =& $this->read_id_fetch_db_object("NewsArticle");

        $this->delete_db_object(array(
            "obj" => $news_article,
            "success_url_params" => array("action" => "pg_news_articles"),
            "error_url_params" => array("action" => "pg_news_articles"),
        ));
    }

    function action_delete_news_article_image() {
        $news_article =& $this->read_id_fetch_db_object("NewsArticle");

        $this->delete_db_object_image($news_article, "image_id");
        
        $this->add_session_status_message(new OkStatusMsg("news_article.image_deleted"));
        $this->create_self_redirect_response(array(
            "action" => "pg_news_article_edit",
            "news_article_id" => $news_article->id,
        ));
    }

    function action_delete_news_article_file() {
        $news_article =& $this->read_id_fetch_db_object("NewsArticle");

        $this->delete_db_object_file($news_article, "file_id");
        
        $this->add_session_status_message(new OkStatusMsg("news_article.file_deleted"));
        $this->create_self_redirect_response(array(
            "action" => "pg_news_article_edit",
            "news_article_id" => $news_article->id,
        ));
    }
//
    function action_pg_categories() {
        $templates_dir = "categories";

        $current_category_ids = $this->_read_and_print_current_category_ids();

        $should_redirect = false;

        $command = (string) param("command");
        $avail_obj_names = array(
            "category1" => "Category1",
            "category2" => "Category2",
            "category3" => "Category3",
        );
        $obj_name = (string) param("obj");
        switch ($command) {
        case "edit_obj":
        case "update_obj":
            if (!in_array($obj_name, array_keys($avail_obj_names))) {
                break;
            }
            $obj =& $this->read_id_fetch_db_object($avail_obj_names[$obj_name], "1", "obj_id");
            if (!$obj->is_definite()) {
                $parent_id_field_name = null;
                if ($obj_name == "category2") {
                    $parent_id_field_name = "category1_id";
                } else if ($obj_name == "category3") {
                    $parent_id_field_name = "category2_id";
                }
                if (!is_null($parent_id_field_name)) {
                    $obj->set_field_value(
                        $parent_id_field_name,
                        $current_category_ids["current_{$parent_id_field_name}"]
                    );
                }
            }

            if ($command == "update_obj") {
                $obj_old = $obj;
                $obj->read();

                $messages = $obj->validate($obj_old);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $obj->save();
                    $this->print_status_message_db_object_updated($obj_old);

                    $should_redirect = true;
                    break;
                }
            }

            $category_edit =& $this->create_object(
                "ObjectEdit",
                array(
                    "templates_dir" => "{$templates_dir}/{$obj_name}_edit",
                    "template_var" => "category_edit",
                    "obj" => $obj,
                    "page_title_resource" => "pg_categories_{$obj_name}_edit",
                )
            );
            $category_edit->print_values();
            break;
        case "delete_obj":
        case "move_obj":
            if (!in_array($obj_name, array_keys($avail_obj_names))) {
                break;
            }
            $obj =& $this->read_id_fetch_db_object($avail_obj_names[$obj_name], "1", "obj_id");

            if ($command == "delete_obj") {
                $this->delete_db_object(array(
                    "obj" => $obj,
                    "should_redirect" => false,
                ));
            } else {
                $obj->update_position((string) param("to"));
            }
            $should_redirect = true;
            break;
        }

        if ($should_redirect) {
            $this->create_self_redirect_response(array(
                "action" => "pg_categories",
                "current_category1_id" => $current_category_ids["current_category1_id"],
                "current_category2_id" => $current_category_ids["current_category2_id"],
                "current_category3_id" => $current_category_ids["current_category3_id"],
            ));
        } else {
            $category_browser =& $this->create_object(
                "CategoryBrowser",
                array(
                    "templates_dir" => "{$templates_dir}/category_browser",
                    "template_var" => "category_browser",
                )
            );
            $category_browser->current_category1_id = $current_category_ids["current_category1_id"];
            $category_browser->current_category2_id = $current_category_ids["current_category2_id"];
            $category_browser->current_category3_id = $current_category_ids["current_category3_id"];
            $category_browser->print_values();

            $this->print_file("{$templates_dir}/body.html", "body");
        }
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
    function action_pg_products() {
        $templates_dir = "products";

        $product =& $this->create_db_object("Product");

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
                "context" => "list_item_admin",
            )
        );
        $products_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_pg_product_edit() {
        $templates_dir = "product_edit";

        $command = (string) param("command");
        switch ($command) {
        case "":
        case "update":
            $product =& $this->read_id_fetch_db_object("Product");

            if ($command == "update") {
                $product_old = $product;
                $product->read();

                $messages = $product->validate($product_old);
                if (count($messages) != 0) {
                    $this->print_status_messages($messages);
                } else {
                    $product->save();
                    $this->print_status_message_db_object_updated($product_old);

                    $this->create_self_redirect_response(array("action" => "pg_products"));
                    break;
                }
            }

            $product_edit =& $this->create_object(
                "ObjectEdit",
                array(
                    "templates_dir" => "{$templates_dir}/product_edit",
                    "template_var" => "product_edit",
                    "obj" => $product,
                )
            );
            $product_edit->print_values();
            $this->print_file("{$templates_dir}/body.html", "body");
            break;
        }
    }

    function action_delete_product() {
        $product =& $this->read_id_fetch_db_object("Product");

        $this->delete_db_object(array(
            "obj" => $product,
            "success_url_params" => array("action" => "pg_products"),
            "error_url_params" => array("action" => "pg_products"),
        ));
    }

}

?>