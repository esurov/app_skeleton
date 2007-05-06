<?php

class Category3Table extends OrderedDbObject {
    
    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "category2_id",
            "type" => "foreign_key",
            "read" => 0,
            "update" => 0,
        ));

        $this->insert_field(array(
            "field" => "name",
            "type" => "varchar",
            "input" => array(
                "type_attrs" => array(
                    "class" => "wide",
                ),
            ),
        ));
    }
//
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "name",
                "type" => "not_empty",
                "message" => "category3_name_empty",
            ),
        );
    }
//
    function get_position_where_str() {
        return "category2_id = {$this->category2_id}";
    }

}

?>