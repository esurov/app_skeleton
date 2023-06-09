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
            
            // Join example in insert_field() to NewsArticle
            "join" => array(
                "type" => "left",
                "obj_class" => "NewsArticle",
                "field" => "id",
            ),
        ));

        $this->insert_field(array(
            "field" => "news_article_alias_id",
            "type" => "foreign_key",
            
            // One more join example in insert_field() to NewsArticle
            // with SQL table alias 'news_article_alias'
            "join" => array(
                "type" => "left",
                "obj_class" => "NewsArticle",
                "table_sql_alias" => "news_article_alias",
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
            "obj_class" => "Image",
            
            // SQL string for join condition
            "condition" => "{$this->_table_name}.image_id = image.id",
        ));

        // Self-join example
        $this->insert_field(array(
            "field" => "parent_id",
            "type" => "foreign_key",
            "join" => array(
                "type" => "left",
                "obj_class" => $this->get_table_class_name(),
                "table_sql_alias" => "{$this->_table_name}_alias",
                "field" => "id",
            ),
        ));

        // Datetime value of record creation
        // Note: It should not be read from CGI and should not be updated
        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
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
                        "nonset_value_caption_pair" =>
                            array(0.0, $this->get_lang_str("not_specified")),
                    ),
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "field_currency2",
            "type" => "currency",
            "value" => 1000000.00,
            "input" => array(
                "values" => array(
                    "data" => array(
                        "sign" => "!super_currency_sign!",
                        "sign_at_start" => false,
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
                            array("value1", "SampleEnum & Value1"),
                            array("value2", "SampleEnum < Value2"),
                            array("value3", "SampleEnum > Value3"),
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

        // Alias field name 'field_text_alias' for the field 'field_text' from self-joined table
        // Note: Calculated fields cannot be got this way when 'table_sql_alias' specified
        $this->insert_field(array(
            "table_sql_alias" => "{$this->_table_name}_alias",
            "field" => "field_text",
            "field_sql_alias" => "field_text_alias"
        ));

        // Multilingual field from NewsArticle table
        // Note: Type not specified because it is taken automatically from another DbObject
        $this->insert_field(array(
            "obj_class" => "NewsArticle",
            "field" => "title",
        ));

        // Field from NewsArticle joined with SQL table alias 'news_article_alias'
        $this->insert_field(array(
            "obj_class" => "NewsArticle",
            "table_sql_alias" => "news_article_alias",
            "field" => "title",
        ));

        // Calculated field from NewsArticle
        // Note: Type not specified because it is taken automatically from another DbObject
        // NB: Field commented because field was removed from NewsArticle
//        $this->insert_field(array(
//            "obj_class" => "NewsArticle",
//            "field" => "full_text",
//        ));

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
    function print_values($params = array()) {
        parent::print_values($params);

        // This template variable is expansion of default printed variables for all contexts
        $this->app->print_raw_value(
            "{$this->_template_var_prefix}.field_double.decorated",
            "!!" . $this->app->page->get_filling_value("{$this->_table_name}_field_double") . "!!"
        );

        // Context handling
        switch ($this->_context) {
        case "context1":
            // Accessing templates directory for current list
            // May be used for printing inner lists
            // $this->_templates_dir;

            // Accessing custom parameters of this list
            // May be used for creating complex links
            // NB: Custom params 'param1' and 'param2' are accessible in this context only 
            $custom_param1_value = $this->_custom_params["param1"];
            $custom_param2_value = $this->_custom_params["param2"];

            // Access to list-item-specific params
            $list_item_number = get_param_value($params, "list_item_number", 0);
            $list_item_parity = get_param_value($params, "list_item_parity", 0);
            $list_item_class = get_param_value($params, "list_item_class", "");
            $list_items_count = get_param_value($params, "list_items_count", 0);

            // Add context-specific template variable
            $this->app->print_varchar_value(
                "{$this->_template_var_prefix}.context1_specific_value",
                "str1&{$list_item_number}<>{$list_item_parity}"
            );
            break;

        case "context2":
            // Add context-specific template variable
            $this->app->print_boolean_value(
                "{$this->_template_var_prefix}.context2_specific_value",
                1 - $this->field_boolean
            );
            break;
        }
    }

}

?>