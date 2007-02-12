<?php

class UserTable extends CustomDbObject {
    
    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "value" => $this->app->get_db_now_datetime(),
            "read" => 0,
            "update" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "updated",
            "type" => "datetime",
            "value" => $this->app->get_db_now_datetime(),
            "read" => 0,
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
            "field" => "email",
            "type" => "varchar",
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
            "field" => "role",
            "type" => "enum",
            "value" => "user",
            "input" => array(
                "type" => "radio",
                "values" => array(
                    "source" => "array",
                    "data" => array(
                        "array" => array(
                            array("user", $this->get_lang_str("user_role_user")),
                            array("admin", $this->get_lang_str("user_role_admin")),
                        ),
                    ),
                ),
            ),
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
//
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
        $this->insert_field(array(
            "field" => "password_confirm",
            "type" => "varchar",
            "width" => 16,
            "input" => array(
                "type" => "password",
            ),
            "select" => "''",
        ));

        $this->insert_field(array(
            "field" => "agreement_accepted",
            "type" => "boolean",
            "select" => "0",
        ));
    }
//
    function get_validate_conditions($context, $context_params) {
        switch ($context) {
        case "login_form":
            $conditions = array(
                array(
                    "field" => "login",
                    "type" => "not_empty",
                    "message" => "user_login_empty",
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
                    "message" => "user_login_or_password_unknown",
                    "message_params" => array("login" => $login),
                    "dependency" => array(
                        "field" => "password",
                        "type" => "equal",
                        "param" => $password,
                        "message" => "user_login_or_password_unknown",
                        "message_params" => array("login" => $login),
                        "dependency" => array(
                            "field" => "is_confirmed",
                            "type" => "not_equal",
                            "param" => 0,
                            "message" => "user_not_confirmed_yet",
                            "message_params" => array("login" => $login),
                            "dependency" => array(
                                "field" => "is_active",
                                "type" => "not_equal",
                                "param" => 0,
                                "message" => "user_disabled_by_admin",
                                "message_params" => array("login" => $login),
                            ),
                        ),
                    ),
                ),
            );
            break;
        case "edit_form":
            $conditions = array(
                array(
                    "field" => "login",
                    "type" => "not_empty",
                    "message" => "user_login_empty",
                    "dependency" => array(
                        "field" => "login",
                        "type" => "unique",
                        "message" => "user_login_exists",
                        "message_params" => array(
                            "login" => $this->login,
                        ),
                    ),
                ),
                array(
                    "field" => "user_role",
                    "type" => "not_empty",
                    "message" => "user_role_empty",
                ),
            );
            break;
        default:
            $conditions = array();
        }
        return $conditions;
    }

    function validate($old_obj = null, $context = "", $context_params = array()) {
        $messages = parent::validate($old_obj, $context, $context_params);

        if ($context == "edit_form" && is_value_not_empty($this->password)) {
            $this->validate_passwords($messages);
        }

        return $messages;
    }

    function validate_passwords(&$messages) {
        if ($this->password != $this->password_confirm) {
            $messages[] = new ErrorStatusMsg("user_passwords_do_not_match");
        }
    }

    function update(
        $field_names_to_update = null,
        $field_names_to_not_update = null
    ) {
        $this->updated = $this->app->get_db_now_datetime();

        parent::update($field_names_to_update, $field_names_to_not_update);
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "list_item") {
            $this->app->print_varchar_value("user_full_name", $this->get_full_name());
            $this->app->print_varchar_value(
                "user_full_name_reversed",
                $this->get_full_name_reversed()
            );
        }
    }

    function get_full_name() {
        return trim("{$this->first_name} {$this->last_name}");
    }

    function get_full_name_reversed() {
        if (is_value_empty($this->last_name)) {
            if (is_value_empty($this->first_name)) {
                return "";
            } else {
                return $this->first_name;
            }
        } else {
            if (is_value_empty($this->first_name)) {
                return $this->last_name;
            } else {
                return "{$this->last_name}, {$this->first_name}";
            }
        }
    }

}

?>