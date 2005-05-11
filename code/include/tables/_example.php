<?php

// These are parameters for insert_field():

// "table" - table name, if not specified - current table
// "field" - field name
// "type" - field type (type is then mapped to real database specific type)
// "width" - output width for field
// "prec" - output precision after decimal point for field
// "select" - select sql expression for field (makes create = 0)
// "attr" - attribute section of create table sql query, rarely used

// "create" - field should be created in the real db table
// "read" - field should be read from cgi by read()
// "store" - field should be stored to DB by store()
// "update" - field should be updated to DB by update()
// "print" - field should be updated to DB by update()

// "value" - initial value of the field when db_object created
// "multilingual" - is field multilingual (has additional fields for all languages)

// "input" - html control for field value editing

// note that you may not specify for many field types:
// "create" - default is true for current table fields and false for another
// "read" - default is true for current table fields and false for another
// "store" - field should be stored to DB by store()
// "update" - field should be updated to DB by update()
// "print" - field should be updated to DB by update()
// "value" - default one will be used, its value depends on field type
// "input" - default one will be used

class Example extends CustomDbObject {
    function Example() {
        parent::CustomDbObject("_example");

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
            "field" => "image_id",
            "type" => "foreign_key",
            "input" => array(
                // "type" can be "text", "select", "radio"
                "type" => "select",
                "values" => array(
                    // "source" can be "array", "db_object", "function"
                    "source" => "db_object",
                    "data" => array(
                        "obj_name" => "image",
                        "caption_field_name" => "filename",
                        "query_ex" => array("order_by" => "filename"),
                        "begin_value_caption_pair" => array(
                            0 => $this->get_message("choose_one")
                        )
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
            "condition" => "_example.image_id = image.id",
        ));


        // self-join example
        $this->insert_field(array(
            "field" => "parent_id",
            "type" => "foreign_key",
            "join" => array(
                "type" => "left",
                "table" => "_example",
                "table_alias" => "_example_alias",
                "field" => "id",
            ),
        ));

        // datetime value of record creation
        // note: it should not be read from cgi and updated
        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "value" => $this->get_db_now_datetime(),
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
            "value" => $this->get_db_now_datetime(),
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

            // create ordinary index on this field
            "index" => "fulltext",

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
            "width" => "12",
            "prec" => "7",
            "value" => 1000000.12345678,
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
                        "value1" => $this->get_message("_example_field_enum_value1"),
                        "value2" => $this->get_message("_example_field_enum_value2"),
                        "value3" => $this->get_message("_example_field_enum_value3"),
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
        // multilingual field from news_article table
        // note: no need to specify type because it is taken from another table,
        // note: fields from another tables are never created in DB
        $this->insert_field(array(
            "table" => "news_article",
            "field" => "title",
        ));

//!!NB: cannot specify multilingual field in "field"
        // alias for the field from current table
        // note: should not be created in DB
        $this->insert_field(array(
            "field" => "field_varchar_{$this->lang}",
            "field_alias" => "field_varchar_alias",
            "type" => "varchar",
            "create" => 0,
        ));

        // alias for the field from joined (self-joined) table
//!!NB: Doesn't work - recursion in insert_field
//        $this->insert_field(array(
//            "table" => "_example",
//            "table_alias" => "_example_alias",
//            "field" => "field_text",
//            "field_alias" => "field_text_alias"
//        ));

        // calculated field example
        $this->insert_field(array(
            "field" => "calculated_field",
            "type" => "text",
            "select" =>
                "CONCAT(_example.field_varchar_{$this->lang}, _example.field_text_{$this->lang})",
        ));

        // calculated field from news_article table
        // need not to specify type because it is taken from another table
        $this->insert_field(array(
            "table" => "news_article",
            "field" => "full_text",
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
    function update($fields = null) {
        $this->updated = $this->mysql_now_datetime();
        parent::update($fields);
    }
//

//    function print_values($params = array()) {
//        $h1 = parent::print_values($params);
//        $h = array();
//
//        $title_short_len = $this->config->value("article_title_short_length");
//        $h["article_title_short"] =
//            get_word_shortened_string(strip_tags($this->title), $title_short_len);
//        $body_short_len = $this->config->value("article_body_short_length");
//        $h["article_body_short"] =
//            get_word_shortened_string(strip_tags($this->body), $body_short_len);
//
//        $this->assign_values($h);
//        return $h1 + $h;
//    }
//
//    function validate($old_obj = null) {
//        $messages = array();
//
//        if (!$this->validate_not_empty_field("title")) {
//            $messages[] = new ErrorStatusMsg("article_title_empty");
////        } else {
////            if (!$this->validate_unique_field("title", $old_obj)) {
////                $messages[] = new ErrorStatusMsg("shop_name_exists");
////            }
//        }
//
//        return $messages;
//    }
}

?>