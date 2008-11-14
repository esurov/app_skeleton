<?php

class ContactInfoTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
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
        ));

        $this->insert_field(array(
            "field" => "company_name",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "city",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "address",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "phone",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "fax",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "message_text",
            "type" => "text",
            "input" => array(
                "type" => "textarea",
                "type_attrs" => array(
                    "cols" => 80,
                    "rows" => 9,
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "security_code",
            "type" => "varchar",
        ));
    }
//
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "first_name",
                "type" => "not_empty",
                "message" => "contact_info.first_name_empty",
            ),
            array(
                "field" => "last_name",
                "type" => "not_empty",
                "message" => "contact_info.last_name_empty",
            ),
            array(
                "field" => "email",
                "type" => "not_empty",
                "message" => "contact_info.email_empty",
                "dependency" => array(
                    "field" => "email",
                    "type" => "email",
                    "message" => "contact_info.email_bad",
                ),
            ),
            array(
                "field" => "message_text",
                "type" => "not_empty",
                "message" => "contact_info.message_text_empty",
            ),
        );
    }
//
    function validate($context = null, $context_params = array()) {
        $messages = parent::validate($context, $context_params);

        if ($this->get_config_value("security_image_enabled") == 1) {
            $security_image_generator =& $this->create_object("SecurityImageGenerator");
            $this->validate_condition(
                $messages,
                array(
                    "field" => "security_code",
                    "type" => "equal",
                    "param" => $security_image_generator->get_security_code(),
                    "message" => "contact_info.security_code_bad",
                )
            );
        }
        
        return $messages;
    }

    function print_form_values($params = array()) {
        parent::print_form_values($params);

        if ($this->get_config_value("security_image_enabled") == 1) {
            $security_image_generator =& $this->create_object("SecurityImageGenerator");
            $security_image_generator->set_security_code();
            $this->app->print_file(
                "{$this->_templates_dir}/_security_image.html",
                "{$this->_template_var_prefix}.security_image"
            );
        }
    }

}

?>