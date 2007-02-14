<?php

class UserApp extends CustomApp {

    // Current logged user
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

            // Index and home pages
            "pg_index" => $e,
            "pg_home" => $u_a,

            // Login/Signup/Confirm/RecoverPassword
            "pg_login" => $e,
            "login" => $e,
            "logout" => $e,
            "pg_signup" => $e,
            "signup" => $e,
            "confirm" => $e,
            "pg_recover_password" => $e,
            "recover_password" => $e,

            // User management
            "pg_users" => $a,

            // News articles
            "pg_news_articles" => $e,
            "pg_news_article_view" => $e,

            // Contact form
            "pg_contact_form" => $e,
            "process_contact_form" => $e,
        );
    }
//
    function create_current_user() {
        $this->user = $this->create_db_object("User");
        $login_state =& Session::get_login_state();

        if (is_null($login_state)) {
            if (isset($_COOKIE["user_id"]) && isset($_COOKIE["user_password_hash"])) {
                $user_id = (int) $_COOKIE["user_id"];
                $user_password_hash = $_COOKIE["user_password_hash"];

                $this->user->fetch("user.id = {$user_id}");
                if ($this->user->is_definite()) {
                    if (sha1($this->user->password) == $user_password_hash) {
                        $this->create_login_state($user_id);
                    } else {
                        $this->user->set_indefinite();
                    }
                }
            }
        } else {
            if (!$login_state->is_expired()) {
                $user_id = (int) $login_state->get_login_id();
                $this->user->fetch("user.id = {$user_id}");
            }
        }
    }

    function &create_login_state($login_id) {
        return Session::create_login_state(
            $this->get_config_value("login_state_idle_timeout"),
            $this->get_config_value("login_state_max_timeout"),
            $login_id
        );
    }

    function get_user_role($user = null) {
        if (is_null($user)) {
            $user = $this->user;
        }
        if ($user->is_definite() && $user->is_active && $user->role != "") {
            $user_role = $user->role;
        } else {
            $user_role = "guest";
        }
        return $user_role;
    }

    function on_before_run_action() {
        parent::on_before_run_action();
        
        $this->print_lang_menu();
        $this->print_login_status();
    }

    function print_login_status() {
        if ($this->user->is_definite()) {
            $this->user->print_values();
            $template_name = "_login_status_logged_in.html";
        } else {
            $template_name = "_login_status_not_logged_in.html";
        }
        $this->print_file_new("_login_status/{$template_name}", "login_status");
    }

    function run_access_denied_action() {
        Session::save_request_params();

        if ($this->action != "pg_home") {
            $this->add_session_status_message(
                new ErrorStatusMsg("status_message_resource_access_denied")
            );
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
//
    function action_pg_index() {
        $templates_dir = "index";

        $news_article = $this->create_db_object("NewsArticle");
        $n_recent_news_articles = $this->get_config_value("recent_news_articles_number");
        $recent_news_articles_list = $this->create_object(
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
        $this->create_self_redirect_response(array("action" => "pg_user_edit"));
    }

    function pg_admin_home_page() {
        $this->create_self_redirect_response(array("action" => "pg_users"));
    }
//
/*
    function action_pg_news_article_edit() {
        $templates_dir = "news_article_edit";

        $news_article = get_param_value($this->action_params, "news_article", null);
        if (is_null($news_article)) {
            $news_article = $this->read_id_fetch_db_object("NewsArticle");
        }
        $news_article_edit = $this->create_object(
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
        $news_article = $this->read_id_fetch_db_object("NewsArticle");
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
*/
    function action_pg_login() {
        $templates_dir = "login";

        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user = $this->create_db_object("User");
            $user->insert_login_form_extra_fields();
        } else {
            $user->login = "";
            $user->password = "";
        }
        $user_edit = $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/user_edit",
                "template_var" => "user_edit",
                "obj" => $user,
                "context" => "login_form",
            )
        );
        $user_edit->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_login() {
        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user = $this->create_db_object("User");
            $user->insert_login_form_extra_fields();
            $user->read(array("login", "password", "should_remember"));
        }
        $should_remember = $user->should_remember;

        $messages = $user->validate(null, "login_form");
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
            $this->create_login_state($user->id);

            $saved_request_params = Session::get_saved_request_params();
            if (is_null(get_param_value($saved_request_params, "action", null))) {
                $saved_request_params = array("action" => "pg_home") + $saved_request_params;
            }
            Session::destroy_saved_request_params();
            $this->create_self_redirect_response($saved_request_params);
            if ($should_remember) {
                $this->add_remember_user_cookies($user->id, $user->password);
            }
        }
    }

    function add_remember_user_cookies($login_id, $password) {
        $cookie_expiration_period_in_days = $this->get_config_value(
            "remember_user_cookie_expiration_period"
        );
        $cookie_expiration_ts = time() + 60 * 60 * 24 * $cookie_expiration_period_in_days;
        $this->response->add_cookie(new Cookie(
            "user_id",
            $login_id,
            $cookie_expiration_ts
        ));
        $this->response->add_cookie(new Cookie(
            "user_password_hash",
            sha1($password),
            $cookie_expiration_ts
        ));
    }

    function action_logout() {
        Session::destroy_login_state();
        $this->add_session_status_message(new OkStatusMsg("status_message_logged_out"));
        $this->create_self_redirect_response(array("action" => "pg_login"));
        $this->remove_remember_user_cookies();
    }

    function remove_remember_user_cookies() {
        if (isset($_COOKIE["user_id"]) && isset($_COOKIE["user_password_hash"])) {
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

    function action_pg_signup() {
        $templates_dir = "signup";

        $user = get_param_value($this->action_params, "user", null);
        if (is_null($user)) {
            $user = $this->create_db_object("User");
            $user->insert_signup_form_extra_fields();
        } else {
            $user->password = "";
            $user->password_confirm = "";
        }
        $user_edit = $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/user_edit",
                "template_var" => "user_edit",
                "obj" => $user,
                "context" => "signup_form",
            )
        );
        $user_edit->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }
/*
        $supplier = get_param_value($this->action_params, "supplier", null);
        if (is_null($supplier)) {
            $supplier = $this->create_db_object("supplier");
        }
        $company_contact_info = get_param_value(
            $this->action_params,
            "company_contact_info",
            null
        );
        if (is_null($company_contact_info)) {
            $company_contact_info = $this->create_db_object("contact_info");
        }
        $templates_dir = "signup";

        $supplier->print_form_values(array("context" => "signup"));
        $this->print_contact_info_subform(
            $company_contact_info,
            $templates_dir,
            "_contact_info_company_subform.html",
            "supplier_company_contact_info_subform",
            "supplier_company_contact_info"
        );
        $this->print_file("{$templates_dir}/form.html", "signup_form");
        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_signup() {
        $supplier = $this->create_db_object("supplier");
        $company_contact_info = $this->create_db_object("contact_info");

        $supplier->read();
        $company_contact_info->read(
            null,
            null,
            "supplier_company_contact_info"
        );

        $messages = array_merge(
            $supplier->validate(null, "signup"),
            $company_contact_info->validate()
        );

        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action(
                "pg_signup",
                array(
                    "supplier" => $supplier,
                    "company_contact_info" => $company_contact_info,
                )
            );
        } else {
            $company_contact_info->store();
            $supplier->init_fields("guest_with_access_key", $company_contact_info);
            $supplier->store();

            $this->send_email_signup_finished_to_admin($supplier, $company_contact_info);

            $this->create_self_redirect_response(array(
                "action" => "pg_static",
                "page" => "signup_finished",
            ));
        }
    }

    function send_email_signup_finished_to_admin($supplier, $company_contact_info) {
        $supplier->print_values();
        $this->print_contact_info(
            $company_contact_info,
            "signup",
            "_email_contact_info_company.html",
            "supplier_company_contact_info",
            "supplier_company_contact_info"
        );

        $url = create_self_full_url(array(
            "action" => "pg_view_suppliers",
            "supplier_status_equal" => "unverified",
        ));
        $this->print_value("url", $url);

        $this->send_email(
            $this->config->get_value("website_email_from"),
            $this->config->get_value("website_name_from"),
            $this->config->get_value("admin_email_to"),
            "",
            $this->config->get_value("email_signup_finished_to_admin_subject"),
            "signup/email_signup_finished_to_admin.html"
        );
*/

    function action_pg_recover_password() {
    }
//
    function action_pg_news_articles() {
        $templates_dir = "news_articles";
        
        $news_article = $this->create_db_object("NewsArticle");
        $news_articles_list = $this->create_object(
            "PagedQueryObjectsList",
            array(
                "templates_dir" => "{$templates_dir}/news_articles",
                "template_var" => "news_articles",
                "obj" => $news_article,
                "query_ex" => array(
                    "order_by" => "created DESC, id DESC",
                ),
                "filter_form.visible" => true,
                "context" => "list_item",
            )
        );
        $news_articles_list->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_pg_news_article_view() {
        $templates_dir = "news_article_view";

        $news_article = $this->read_id_fetch_db_object("NewsArticle");
        $news_article_view = $this->create_object(
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
//
    function action_pg_contact_form() {
        $templates_dir = "contact_form";

        $contact_info = get_param_value($this->action_params, "contact_info", null);
        if (is_null($contact_info)) {
            $contact_info = $this->create_db_object("ContactInfo");
        }
        $contact_form = $this->create_object(
            "ObjectEdit",
            array(
                "templates_dir" => "{$templates_dir}/contact_info_edit",
                "template_var" => "contact_info_edit",
                "obj" => $contact_info,
            )
        );
        $contact_form->print_values();

        $this->print_file("{$templates_dir}/body.html", "body");
    }

    function action_process_contact_form() {
        $contact_info = $this->create_db_object("ContactInfo");
        $contact_info_old = $contact_info;
        $contact_info->read();

        $messages = $contact_info->validate($contact_info_old);

        if (count($messages) != 0) {
            $this->print_status_messages($messages);
            $this->run_action("pg_contact_form", array("contact_info" => $contact_info));
        } else {
            $this->send_email_contact_form_processed_to_admin($contact_info);
            
            $this->add_session_status_message(new OkStatusMsg("contact_form_processed"));
            $this->create_self_redirect_response(array("action" => "pg_contact_form"));
        }
    }

    function send_email_contact_form_processed_to_admin($contact_info) {
        $email_from = $contact_info->email;
        $name_from = "{$contact_info->first_name} {$contact_info->last_name}";
        $email_to = $this->get_actual_email_to($this->get_config_value("contact_form_email_to"));
        $name_to = $this->get_config_value("contact_form_name_to");
        $subject = $this->get_config_value("email_contact_form_processed_subject");
        $contact_info->print_values();
        $body = $this->print_file("contact_form/email.html");

        $email_sender = $this->create_email_sender();
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
        
        $user = $this->create_db_object("User");
        $users_list = $this->create_object(
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

}

?>