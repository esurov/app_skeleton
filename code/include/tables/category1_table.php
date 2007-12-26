<?php

class Category1Table extends OrderedDbObject {
    
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
                    "class" => "wide",
                ),
            ),
        ));
    }
//
    function get_restrict_relations() {
        return array(
            array("Category2", "category1_id"),
        );
    }
//
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "name",
                "type" => "not_empty",
                "message" => "category1.name_empty",
            ),
        );
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "category_browser_list_item") {
            $list_item_selected_class = ($this->id == $this->_custom_params["selected_item_id"]) ?
                " list_item_selected" : 
                "";
            $this->app->print_raw_value(
                "list_item_selected_class", 
                $list_item_selected_class
            );
        }
    }

}

?>