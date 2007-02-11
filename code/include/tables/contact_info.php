<?php

class ContactInfoTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "created",
            "type" => "date",
            "value" => $this->app->get_db_now_date(),
            "read" => 0,
            "update" => 0,
            "index" => "index",
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
                    "cols" => 60,
                    "rows" => 9,
                ),
            ),
        ));
    }
//
    function get_validate_conditions() {
        return array(
            array(
                "field" => "first_name",
                "type" => "not_empty",
                "message" => "status_message_first_name_empty",
            ),
            array(
                "field" => "last_name",
                "type" => "not_empty",
                "message" => "status_message_last_name_empty",
            ),
            array(
                "field" => "email",
                "type" => "not_empty",
                "message" => "status_message_email_empty",
                "dependency" => array(
                    "field" => "email",
                    "type" => "email",
                    "message" => "status_message_email_bad",
                ),
            ),
            array(
                "field" => "message_text",
                "type" => "not_empty",
                "message" => "status_message_message_text_empty",
            ),
        );
    }

}

?>