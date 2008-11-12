<?php

class NewsletterCategoryTable extends CustomDbObject {
    
    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "name",
            "type" => "varchar",
            "multilingual" => 1,
            "input" => array(
                "type_attrs" => array(
                    "class" => "varchar_wide",
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "is_active",
            "type" => "boolean",
            "value" => 0,
        ));

    }

    function get_restrict_relations() {
        return array(
            array("newsletter", "newsletter_category_id"),
        );
    }
//
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "name",
                "type" => "not_empty",
                "message" => "newsletter_category.name_empty",
            ),
        );
    }
//
    function activate_deactivate() {
        if ($this->is_active) {
            $this->deactivate();
        } else {
            $this->activate();
        }
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
    function insert_list_extra_fields($user_id) {
        $this->insert_join(array(
            "type" => "left",
            "obj_class" => "UserSubscription",
            "condition" =>
                "newsletter_category.id = user_subscription.newsletter_category_id AND " .
                "user_subscription.user_id = {$user_id}",
        ));
        
        $this->insert_field(array(
            "field" => "is_checked",
            "type" => "boolean",
            "select" => "!ISNULL(user_subscription.id)",
            "value" => 0,
        ));
    }

    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "my_subscriptions_list_item") {
            $template_value_is_checked = "";
            if ($this->is_checked) {
                $template_value_is_checked = "checked";
            }
            $this->app->print_value(
                "{$this->_template_var_prefix}.is_checked",
                $template_value_is_checked
            );
        }
    }

}

?>