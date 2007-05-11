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
//    function store(
//        $field_names_to_store = null,
//        $field_names_to_not_store = null
//    ) {
//        $this->position = $this->fetch_last_db_object_position() + 1;
//
//        parent::store($field_names_to_store, $field_names_to_not_store);
//    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "list_item_category_browser") {
            if ($this->id == $this->_custom_params["selected_item_id"]) {
                $this->app->print_raw_value(
                    "list_item_class",
                    "{$params['list_item_class']}_selected"
                );
            }
        }
    }

}

?>