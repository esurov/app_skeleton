<?php

// These are parameters for insert_field():

// "table" - table name, if not specified - current table name
// "table_alias" - table alias name, if not specified - table name
// "field" - field name
// "field_alias" - field alias name, if not specified - field name
// "type" - field type (type is then mapped to real database specific type)
// "width" - output width for field
// "prec" - output precision after decimal point for field
// "select" - select sql expression for field (makes create = 0)
// "attr" - attribute section of create table sql query, rarely used

// "create" - field should be created in the real db table
// "read" - field should be read from cgi by read()
// "store" - field should be stored to DB by store()
// "update" - field should be updated to DB by update()

// "value" - initial value of the field when db_object created
// "multilingual" - is field multilingual (has additional fields for all languages)

// "input" - html control for field value editing

// note that you may not specify for many field types:
// "create" - default is true for current table fields and false for another
// "read" - default is true for current table fields and false for another
// "store" - field should be stored to DB by store()
// "update" - field should be updated to DB by update()
// "value" - default one will be used, its value depends on field type
// "input" - default one will be used

class SampleTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        // note: no need to specify index on primary_key and foreign_key fields
        // it is added automatically

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "news_article_id",
            "type" => "foreign_key",
            
            // join example in insert_field()
            "join" => array(
                "type" => "left",
                "table" => "news_article",
                "field" => "id",
            ),
        ));

        $this->insert_field(array(
            "field" => "news_article2_id",
            "type" => "foreign_key",
            
            // another join example in insert_field()
            "join" => array(
                "type" => "left",
                "table" => "news_article",
                "table_alias" => "news_article2",
                "field" => "id",
            ),
        ));

        $this->insert_field(array(
            "field" => "image_id",
            "type" => "foreign_key",
            "input" => array(
                // "type" can be "text", "select", "radio"
                "type" => "select",
                "values" => array(
                    // "source" can be "array", "db_object", "function"
                    "source" => "db_object",
                    "data" => array(
                        "nonset_value_caption_pair" =>
                            array(0, $this->get_message("choose_one")),
                        "obj_name" => "image",
                        "captions_field_name" => "filename",
                        "query_ex" => array(
                            "order_by" => "filename",
                        ),
                        "end_value_caption_pairs" => array(
                            array(-1, $this->get_message("other")),
                        ),
                    ),
                ),
            ),
        ));

        // join example out of insert_field()
        // should be used for complex join conditions
        $this->insert_join(array(
            "type" => "left",
            "table" => "image",
            
            // sql string for join condition
            "condition" => "{$this->_table_name}.image_id = image.id",
        ));


        // self-join example
        $this->insert_field(array(
            "field" => "parent_id",
            "type" => "foreign_key",
            "join" => array(
                "type" => "left",
                "table" => $this->_table_name,
                "table_alias" => "{$this->_table_name}_alias",
                "field" => "id",
            ),
        ));

        // datetime value of record creation
        // note: it should not be read from cgi and updated
        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "value" => $this->app->get_db_now_datetime(),
            "read" => 0,
            "update" => 0,

            // create ordinary index on this field
            "index" => "index",
        ));

        // datetime value of last record update
        // note: it should not be read from cgi and therefore
        // new value should be set in redefined update() function
        $this->insert_field(array(
            "field" => "updated",
            "type" => "datetime",
            "value" => $this->app->get_db_now_datetime(),
            "read" => 0,
        ));

        $this->insert_field(array(
            "field" => "field_varchar",
            "type" => "varchar",

            // create field for every language
            "multilingual" => 1,

            // create unique index on this field
            // use validate() before store(), update(), save()
            // to be sure this value is really unique
            "index" => "unique", 

            // this is optional
            "input" => array(
                "type" => "text",
                "type_attrs" => array(
                    "maxlength" => 3,
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "field_text",
            "type" => "text",

            // create field for every language
            "multilingual" => 1,

            // this is optional
            "input" => array(
                "type" => "textarea",
                "type_attrs" => array(
                    "cols" => 10,
                    "rows" => 4,
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "field_integer",
            "type" => "integer",
            "value" => 1000000,
        ));

        $this->insert_field(array(
            "field" => "field_double",
            "type" => "double",
            "value" => 1234567890123456.12,
        ));

        $this->insert_field(array(
            "field" => "field_double_12_7",
            "type" => "double",
            "width" => "12",
            "prec" => "7",
            "value" => 1234567890.1234567,
        ));

        $this->insert_field(array(
            "field" => "field_currency",
            "type" => "currency",
            "value" => 0.0,
            "input" => array(
                "values" => array(
                    "data" => array(
                        "sign" => "!super_currency_sign!",
                        "sign_at_start" => false,
                        "nonset_value_caption_pair" =>
                            array(0.0, $this->get_message("not_specified")),
                    ),
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "field_boolean",
            "type" => "boolean",
            "value" => 1,
        ));

        $this->insert_field(array(
            "field" => "field_enum",
            "type" => "enum",
            "value" => "value1",
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "array",
                    "data" => array(
                        "array" => array(
                            array("value1", $this->get_message("_example_field_enum_value1")),
                            array("value2", $this->get_message("_example_field_enum_value2")),
                            array("value3", $this->get_message("_example_field_enum_value3")),
                        ),
                    ),
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "field_blob",
            "type" => "blob",
        ));

        $this->insert_field(array(
            "field" => "field_datetime",
            "type" => "datetime",
        ));
        
        $this->insert_field(array(
            "field" => "field_date",
            "type" => "date",
        ));

        $this->insert_field(array(
            "field" => "field_time",
            "type" => "time",
        ));
//
        // calculated field example
        $this->insert_field(array(
            "field" => "calculated_field",
            "type" => "text",
            "select" =>
                "CONCAT(" .
                    "{$this->_table_name}.field_varchar_{$this->app->lang}, " .
                    "{$this->_table_name}.field_text_{$this->app->lang}".
                ")",
        ));

        // alias for the multilingual field from current table
        // note: fields with alias are never created in DB
        // note: no need to specify type because it is taken from current table field
        $this->insert_field(array(
            "field" => "field_varchar",
            "field_alias" => "field_varchar_alias",
        ));

        // field 'field_text' from self-joined table
        // note: calculated fields cannot be get when table_alias specified
        $this->insert_field(array(
            "table_alias" => "{$this->_table_name}_alias",
            "field" => "field_text",
        ));

        // alias field name 'field_text_alias' for the field 'field_text' from self-joined table
        // note: calculated fields cannot be get when table_alias specified
        $this->insert_field(array(
            "table_alias" => "{$this->_table_name}_alias",
            "field" => "field_text",
            "field_alias" => "field_text_alias"
        ));

        // calculated field from news_article table
        // note: do not specify type because it is taken from another table
        // nb: field commented because field was removed from news_article
//        $this->insert_field(array(
//            "table" => "news_article",
//            "field" => "full_text",
//        ));

        // multilingual field from news_article table
        // note: no need to specify type because it is taken from another table
        // note: fields from another tables are never created in DB
        $this->insert_field(array(
            "table" => "news_article",
            "field" => "title",
        ));

        // field from news_article joined with alias name news_article2
        $this->insert_field(array(
            "table" => "news_article",
            "table_alias" => "news_article2",
            "field" => "title",
        ));

        // complex index example
        // note: fields should exist
        $this->insert_index(array(
            "type" => "index",
            "fields" => array("field_datetime", "field_date", "field_time"),
        ));
//
        $this->insert_filter(array(
            "name" => "calculated_field",
            "relation" => "like",
        ));
    }
//
    function update(
        $fields_names_to_update = null,
        $fields_names_to_not_update = null
    ) {
        $this->updated = $this->app->get_db_now_datetime();
        parent::update($fields_names_to_update, $fields_names_to_not_update);
    }
//

    function print_values($params = array()) {
        parent::print_values($params);

        // This template variable is extension to default printed variables for all contexts
        $this->app->print_raw_value(
            "_sample_table2_field_double_decorated",
            "!!" . $this->app->page->get_filling_value("_sample_table2_field_double") . "!!"
        );

        // Context handling
        switch ($this->print_params["context"]) {
        case "context1":
            // Accessing templates directory for current list
            // May be used for printing inner lists
            $templates_dir = $this->print_params["templates_dir"];

            // Accessing custom parameters of this list
            // May be used for creating complex links
            $param1_value = $this->print_params["custom_params"]["param1"];
            $param2_value = $this->print_params["custom_params"]["param2"];

            // Access to item number in the list and its parity
            $list_item_number = $this->print_params["list_item_number"];
            $list_item_parity = $this->print_params["list_item_parity"];

            // Add context-specific template variable
            $this->app->print_varchar_value(
                "_sample_table2_context1_specific_value",
                "str1&{$list_item_number}<>{$list_item_parity}"
            );
            break;

        case "context2":
            // Add context-specific template variable
            $this->app->print_boolean_value(
                "_sample_table2_context2_specific_value",
                1 - $this->field_boolean
            );
            break;
        }
    }

}

?>