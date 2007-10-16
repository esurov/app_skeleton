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

}

?>