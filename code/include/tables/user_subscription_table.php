<?php

class UserSubscriptionTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "user_id",
            "type" => "foreign_key",
        ));

        $this->insert_field(array(
            "field" => "newsletter_category_id",
            "type" => "foreign_key",
        ));
    }

//
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "user_id",
                "type" => "not_equal",
                "param" => 0,
                "message" => "user_subscription.user_empty",
            ),
            array(
                "field" => "newsletter_category_id",
                "type" => "not_equal",
                "param" => 0,
                "message" => "user_subscription.category_empty",
            ),
        );
    }

//
    function insert_user_list_fields() {
        
        $this->insert_join(array(
            "type" => "left",
            "obj_class" => "User",
            "condition" => "{$this->_table_name}.user_id = user.id",
        ));

        $this->insert_field(array(
            //user_subscription_user_login
            "obj_class" => "User",
            "field" => "email",
        ));
        $this->insert_field(array(
            //user_subscription_user_login
            "obj_class" => "User",
            "field" => "first_name",
        ));
    }

}

?>