<?php

class UserTable extends CustomDbObject {
    
    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "login",
            "type" => "varchar",
            "index" => "unique",
        ));

        $this->insert_field(array(
            "field" => "password",
            "type" => "varchar",
            "width" => 16,
            "input" => array(
                "type" => "password",
            ),
        ));

        $this->insert_field(array(
            "field" => "first_name",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "last_name",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "email",
            "type" => "varchar",
            "index" => "unique",
        ));

        $this->insert_field(array(
            "field" => "extra_info",
            "type" => "text",
        ));

        $this->insert_field(array(
            "field" => "role",
            "type" => "enum",
            "value" => "user",
            "input" => array(
                "type" => "radio",
                "values" => array(
                    "source" => "array",
                    "data" => array(
                        "array" => array(
                            array("user", $this->get_lang_str("user_role.user")),
                            array("admin", $this->get_lang_str("user_role.admin")),
                        ),
                        "delimiter" => "<br />",
                    ),
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "confirmation_date",
            "type" => "datetime",
            "read" => 0,
            "update" => 0,
        ));

        $this->insert_field(array(
            "field" => "is_confirmed",
            "type" => "boolean",
            "value" => 0,
        ));

        $this->insert_field(array(
            "field" => "is_active",
            "type" => "boolean",
            "value" => 0,
        ));

        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "read" => 0,
            "update" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "updated",
            "type" => "datetime",
            "read" => 0,
        ));
    }

    function insert_filters() {
        $this->insert_filter(array(
            "name" => "login",
            "relation" => "like",
        ));

        $this->insert_filter(array(
            "name" => "full_name",
            "relation" => "like_many",
            "type" => "text",
            "select" => array(
                "user.first_name",
                "user.last_name",
            ),
        ));

        $this->insert_filter(array(
            "name" => "email",
            "relation" => "like",
        ));

        $this->insert_filter(array(
            "name" => "role",
            "relation" => "equal",
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "field",
                    "data" => array(
                        "nonset_value_caption_pair" => array(-1, $this->get_lang_str("any")),
                    ),
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "is_confirmed",
            "relation" => "equal",
            "value" => 1,
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "array",
                    "data" => array(
                        "nonset_value_caption_pair" => array(-1, $this->get_lang_str("any")),
                        "array" => array(
                            array(1, $this->get_lang_str("yes")),
                            array(0, $this->get_lang_str("no")),
                        ),
                    ),
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "is_active",
            "relation" => "equal",
            "value" => 1,
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "array",
                    "data" => array(
                        "nonset_value_caption_pair" => array(-1, $this->get_lang_str("any")),
                        "array" => array(
                            array(1, $this->get_lang_str("yes")),
                            array(0, $this->get_lang_str("no")),
                        ),
                    ),
                ),
            ),
        ));
    }

    function insert_login_form_extra_fields() {
        $this->insert_field(array(
            "field" => "should_remember",
            "type" => "boolean",
            "select" => "0",
        ));
    }

    function insert_signup_form_extra_fields() {
        $this->insert_edit_form_extra_fields();

        $this->insert_field(array(
            "field" => "agreement_accepted",
            "type" => "boolean",
            "select" => "0",
        ));
    }

    function insert_edit_form_extra_fields() {
        $this->insert_field(array(
            "field" => "password_confirm",
            "type" => "varchar",
            "width" => 16,
            "input" => array(
                "type" => "password",
            ),
            "select" => "''",
        ));
    }
//
    function get_read_field_names_info($context, $context_params) {
        $field_names_to_read = null;
        $field_names_to_not_read = null;

        switch ($context) {
        case "login_form":
            $field_names_to_read = array("login", "password", "should_remember");
            break;
        case "signup_form":
            $field_names_to_not_read = array("role", "is_confirmed", "is_active");
            break;
        case "user_edit_admin":
            if ($this->is_definite()) {
                $field_names_to_not_read = array("role");
            }
            break;
        case "user_edit_user":
            $field_names_to_not_read = array("login", "role", "is_confirmed", "is_active");
            break;
        case "recover_password_form":
            $field_names_to_read = array("login", "email");
            break;
        }
        return array(
            "field_names_to_read" => $field_names_to_read,
            "field_names_to_not_read" => $field_names_to_not_read,
        );
    }
//
    function get_validate_conditions($context, $context_params) {
        switch ($context) {
        case "login_form":
            $conditions = array(
                array(
                    "field" => "login",
                    "type" => "not_empty",
                    "message" => "user.login_empty",
                ),
            );
            break;
        case "login":
            $login = $context_params["login"];
            $password = $context_params["password"];
            
            $conditions = array(
                array(
                    "field" => "id",
                    "type" => "not_equal",
                    "param" => 0,
                    "message" => "user.login_or_password_unknown",
                    "message_params" => array("login" => $login),
                    "dependency" => array(
                        "field" => "password",
                        "type" => "equal",
                        "param" => $password,
                        "message" => "user.login_or_password_unknown",
                        "message_params" => array("login" => $login),
                        "dependency" => array(
                            "field" => "is_confirmed",
                            "type" => "not_equal",
                            "param" => 0,
                            "message" => "user.not_confirmed_yet",
                            "message_params" => array("login" => $login),
                            "dependency" => array(
                                "field" => "is_active",
                                "type" => "not_equal",
                                "param" => 0,
                                "message" => "user.disabled_by_admin",
                                "message_params" => array("login" => $login),
                            ),
                        ),
                    ),
                ),
            );
            break;
        case "signup_form":
            $conditions = array(
                array(
                    "field" => "login",
                    "type" => "not_empty",
                    "message" => "user.login_empty",
                    "dependency" => array(
                        "field" => "login",
                        "type" => "unique",
                        "message" => "user.login_exists",
                        "message_params" => array(
                            "login" => $this->login,
                        ),
                    ),
                ),
                array(
                    "field" => "password",
                    "type" => "not_empty",
                    "message" => "user.password_empty",
                ),
                array(
                    "field" => "password_confirm",
                    "type" => "not_empty",
                    "message" => "user.password_empty",
                ),
                array(
                    "field" => "first_name",
                    "type" => "not_empty",
                    "message" => "user.first_name_empty",
                ),
                array(
                    "field" => "last_name",
                    "type" => "not_empty",
                    "message" => "user.last_name_empty",
                ),
                array(
                    "field" => "email",
                    "type" => "not_empty",
                    "message" => "user.email_empty",
                    "dependency" => array(
                        "field" => "email",
                        "type" => "email",
                        "message" => "user.email_bad",
                        "dependency" => array(
                            "field" => "email",
                            "type" => "unique",
                            "message" => "user.email_exists",
                            "message_params" => array(
                                "email" => $this->email,
                            ),
                        ),
                    ),
                ),
            );
            break;
        case "user_edit_admin":
        case "user_edit_user":
            $conditions = array(
                array(
                    "field" => "role",
                    "type" => "not_empty",
                    "message" => "user.role_empty",
                ),
                array(
                    "field" => "first_name",
                    "type" => "not_empty",
                    "message" => "user.first_name_empty",
                ),
                array(
                    "field" => "last_name",
                    "type" => "not_empty",
                    "message" => "user.last_name_empty",
                ),
                array(
                    "field" => "email",
                    "type" => "not_empty",
                    "message" => "user.email_empty",
                    "dependency" => array(
                        "field" => "email",
                        "type" => "email",
                        "message" => "user.email_bad",
                        "dependency" => array(
                            "field" => "email",
                            "type" => "unique",
                            "message" => "user.email_exists",
                            "message_params" => array(
                                "email" => $this->email,
                            ),
                        ),
                    ),
                ),
            );
            break;
        case "recover_password_form":
            $conditions = array(
                array(
                    "field" => "email",
                    "type" => "empty",
                    "message" => null,
                    "dependency" => array(
                        "field" => "login",
                        "type" => "not_empty",
                        "message" => "recover_password.login_or_email_empty",
                    ),
                ),
                array(
                    "field" => "login",
                    "type" => "empty",
                    "message" => null,
                    "dependency" => array(
                        "field" => "email",
                        "type" => "not_empty",
                        "message" => "recover_password.login_or_email_empty",
                        "dependency" => array(
                            "field" => "email",
                            "type" => "email",
                            "message" => "recover_password.bad_email",
                        ),
                    ),
                ),
            );
            break;
        default:
            $conditions = array();
        }
        return $conditions;
    }

    function validate($context = "", $context_params = array()) {
        $messages = parent::validate($context, $context_params);

        if ($context == "signup_form") {
            $this->validate_passwords($messages);
            if (!$this->agreement_accepted) {
                $messages[] = new ErrorStatusMsg("user.should_accept_agreement");
            }
        }
        
        if ($context == "user_edit_admin") {
            $this->validate_condition(
                $messages,
                array(
                    "field" => "login",
                    "type" => "not_empty",
                    "message" => "user.login_empty",
                    "dependency" => array(
                        "field" => "login",
                        "type" => "unique",
                        "message" => "user.login_exists",
                        "message_params" => array(
                            "login" => $this->login,
                        ),
                    ),
                )
            );
        }

        if (
            ($context == "user_edit_admin" || $context == "user_edit_user") &&
            is_value_not_empty($this->password)
        ) {
            $this->validate_passwords($messages);
        }

        if ($context == "recover_password_form" && count($messages) == 0) {
            // NB: This part of code overwrites current user,
            // it fetches user with current user's login or email
            if (is_value_not_empty($this->login)) {
                if (!$this->fetch("user.login = ". qw($this->login))) {
                    $messages[] = new ErrorStatusMsg(
                        "recover_password.no_account_with_login",
                        array("login" => $this->login)
                    );
                }
            } else if (is_value_not_empty($this->email)) {
                if (!$this->fetch("user.email = ". qw($this->email))) {
                    $messages[] = new ErrorStatusMsg(
                        "recover_password.no_account_with_email",
                        array("email" => $this->email)
                    );
                }
            }
        }

        return $messages;
    }

    function validate_passwords(&$messages) {
        if ($this->password != $this->password_confirm) {
            $messages[] = new ErrorStatusMsg("user.passwords_do_not_match");
        }
    }
//
    function get_save_field_names_info($context, $context_params) {
        $field_names_to_not_update = null;

        switch ($context) {
        case "skip_password":
            $field_names_to_not_update = array("password");
            break;
        }
        return array(
            "field_names_to_not_update" => $field_names_to_not_update,
        );
    }

    function confirm() {
        $this->confirmation_date = $this->app->get_db_now_datetime();
        $this->is_confirmed = 1;

        $this->update();
    }

    function activate() {
        $this->is_active = 1;
        
        $this->update();
    }

    function deactivate() {
        $this->is_active = 0;
        
        $this->update();
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        $this->app->print_varchar_value(
            "{$this->_template_var_prefix}.full_name",
            $this->get_full_name()
        );

        $this->app->print_varchar_value(
            "{$this->_template_var_prefix}.full_name.reversed",
            $this->get_full_name_reversed()
        );
    }
//
    function print_form_values($params = array()) {
        parent::print_form_values($params);

        if ($this->_context == "user_edit_admin") {
            if (!$this->is_definite()) {
                $this->app->print_file("{$this->_templates_dir}/_role.html", "_role");
            }
            $this->app->print_file("{$this->_templates_dir}/_is_active.html", "_is_active");
            $this->app->print_file("{$this->_templates_dir}/_is_confirmed.html", "_is_confirmed");
            $this->app->print_file("{$this->_templates_dir}/_link_back_to_users.html", "_link_back");
        }
        
        if ($this->_context == "user_edit_user") {
            $this->app->print_file(
                "{$this->_templates_dir}/_link_back_to_my_account.html",
                "_link_back"
            );
        }
    }
//
    function get_full_name() {
        return get_full_name($this->first_name, $this->last_name);
    }

    function get_full_name_reversed() {
        return get_full_name_reversed($this->first_name, $this->last_name);
    }
//
    function get_num_admins() {
        $query = new SelectQuery(array(
            "select" => "id",
            "from"   => "{%user_table%}",
            "where"  => "role = 'admin'",
        ));
        return $this->db->get_select_query_num_rows($query);
    }

}

?>