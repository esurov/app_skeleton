<?php

// Available parameters for insert_field():
// "obj_class" - DbObject class name (makes "create" = 0), if not specified - current class name
// "table_sql_alias" - SQL table name alias, if not specified - SQL table name of 'obj_class' used
// "field" - Field name of 'obj_class'
// "field_sql_alias" - SQL field name alias, if not specified - 'field' used
// "type" - Field type (mapped to real db field type and affects reading, printing, saving)
// "width" - SQL output width for field 
// "prec" - SQL output decimal point precision for field
// "select" - Field SQL SELECT expression without SQL field name alias (makes "create" = 0)
// "attr" - Extra attributes of field CREATE TABLE SQL expression (rarely used)

// "create" - Field should be created in db table
// "read" - Field should be read from CGI by read()
// "store" - Field should be stored to db by store()
// "update" - Field should be updated in db by update()

// "value" - Initial value of the field when DbObject created
// "multilingual" - Is field multilingual (has additional fields for all languages)

// "input" - HTML control for field value editing

// Note: It is not always necessary to specify, defaults are enough almost all the time:
// "create" - Default is true for all current DbObject fields and false if from another DbObject
// "read" - Default is true for all current DbObject fields and false if from another DbObject
// "store" - Field should be stored to db by store()
// "update" - Field should be updated to db by update()
// "value" - Value depends on field type, better to specify explicitly
// "input" - Value depends on field type

class SampleTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        // Note: No need to specify index on primary_key and foreign_key fields
        // it is added automatically
        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "news_article_id",
            "type" => "foreign_key",
            
            // Join example in insert_field()
            "join" => array(
                "type" => "left",
                "obj_class" => "NewsArticleTable",
                "field" => "id",
            ),
        ));

        $this->insert_field(array(
            "field" => "news_article2_id",
            "type" => "foreign_key",
            
            // One more join to the same table
            "join" => array(
                "type" => "left",
                "obj_class" => "NewsArticleTable",
                "table_sql_alias" => "news_article2",
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
                    // "source" can be "array", "db_object_query", "query"
                    "source" => "db_object_query",
                    "data" => array(
                        "nonset_value_caption_pair" =>
                            array(0, $this->get_lang_str("choose_one")),
                        "obj" => "Image",
                        "query_ex" => array(
                            "order_by" => "filename",
                        ),
                        "captions_field_name" => "filename",
                        "end_value_caption_pairs" => array(
                            array(-1, $this->get_lang_str("other")),
                        ),
                    ),
                ),
            ),
        ));

        // Join example out of insert_field()
        // Should be used for complex join condition expressions
        // Current DbObject table name is accessed by _table_name member var
        $this->insert_join(array(
            "type" => "left",
            "obj_class" => "ImageTable",
            
            // SQL string for join condition
            "condition" => "{$this->_table_name}.image_id = image.id",
        ));

        // Self-join example
        $this->insert_field(array(
            "field" => "parent_id",
            "type" => "foreign_key",
            "join" => array(
                "type" => "left",
                "obj_class" => $this->get_class_name(),
                "table_sql_alias" => "{$this->_table_name}_alias",
                "field" => "id",
            ),
        ));

        // Datetime value of record creation
        // Note: It should not be read from CGI and should not be updated
        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "value" => $this->app->get_db_now_datetime(),
            "read" => 0,
            "update" => 0,

            // Create ordinary index on this field
            "index" => "index",
        ));

        // Datetime value of last record update
        // Note: It should not be read from CGI and therefore
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

            // Create field for every language
            "multilingual" => 1,

            // Create unique index on this field
            // Use validate() before calling save(), store(), update()
            // to be sure this value is really unique
            "index" => "unique", 

            // This is optional
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

            // Create field for every language
            "multilingual" => 1,

            // This is optional
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
                            array(0.0, $this->get_lang_str("not_specified")),
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
                            array("value1", $this->get_lang_str("_example_field_enum_value1")),
                            array("value2", $this->get_lang_str("_example_field_enum_value2")),
                            array("value3", $this->get_lang_str("_example_field_enum_value3")),
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
        // Calculated field example, could be used to create complex field
        // Field type affects field printing
        $this->insert_field(array(
            "field" => "calculated_field",
            "type" => "text",
            "select" =>
                "CONCAT(" .
                    "{$this->_table_name}.field_varchar_{$this->app->lang}, " .
                    "{$this->_table_name}.field_text_{$this->app->lang}".
                ")",
        ));

        // Alias for the multilingual field from current table
        // Note: Fields with alias are never created in db table
        // Note: No need to specify type because it is taken from that field
        $this->insert_field(array(
            "field" => "field_varchar",
            "field_sql_alias" => "field_varchar_alias",
        ));

        // Field 'field_text' from self-joined aliased table
        // Note: Calculated fields cannot be got this way when 'table_sql_alias' specified
        $this->insert_field(array(
            "table_sql_alias" => "{$this->_table_name}_alias",
            "field" => "field_text",
        ));

        // Alias field name 'field_text_alias' for the field 'field_text' from self-joined table
        // Note: Calculated fields cannot be got this way when 'table_sql_alias' specified
        $this->insert_field(array(
            "table_sql_alias" => "{$this->_table_name}_alias",
            "field" => "field_text",
            "field_sql_alias" => "field_text_alias"
        ));

        // Calculated field from NewsArticle
        // Note: Type not specified because it is taken automatically from another DbObject
        // NB: Field commented because field was removed from NewsArticle
//        $this->insert_field(array(
//            "obj_class" => "NewsArticleTable",
//            "field" => "full_text",
//        ));

        // Multilingual field from NewsArticle table
        // Note: Type not specified because it is taken automatically from another DbObject
        $this->insert_field(array(
            "obj_class" => "NewsArticleTable",
            "field" => "title",
        ));

        // Field from NewsArticle joined with SQL table alias 'news_article2'
        $this->insert_field(array(
            "obj_class" => "NewsArticleTable",
            "table_sql_alias" => "news_article2",
            "field" => "title",
        ));

        // Complex index example
        // Note: Fields should exist
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

        // This template variable is expansion of default printed variables for all contexts
        $this->app->print_raw_value(
            "{$this->_table_name}_field_double_decorated",
            "!!" . $this->app->page->get_filling_value("{$this->_table_name}_field_double") . "!!"
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
                "{$this->_table_name}_context1_specific_value",
                "str1&{$list_item_number}<>{$list_item_parity}"
            );
            break;

        case "context2":
            // Add context-specific template variable
            $this->app->print_boolean_value(
                "{$this->_table_name}_context2_specific_value",
                1 - $this->field_boolean
            );
            break;
        }
    }

}

?>