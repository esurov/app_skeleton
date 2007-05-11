<?php

class ProductTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "category3_id",
            "type" => "foreign_key",
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "db_object_query",
                    "data" => array(
                        "nonset_value_caption_pair" => array(0, $this->get_lang_str("choose_one")),
                        "obj" => "Category3",
                        "captions_field_name" => "name",
                        "query_ex" => array(
                            "order_by" => "position ASC",
                        ),
                    ),
                ),
            ),
        ));

        $this->insert_field(array(
            "field" => "name",
            "type" => "varchar",
            "multilingual" => 1,
            "index" => "index",
            "input" => array(
                "type_attrs" => array(
                    "class" => "varchar_wide",
                )
            ),
        ));

        $this->insert_field(array(
            "field" => "description",
            "type" => "text",
            "multilingual" => 1,
        ));

        $this->insert_field(array(
            "field" => "price",
            "type" => "currency",
        ));
//
        $this->insert_join(array(
            "type" => "left",
            "obj_class" => "Category3",
            "condition" => "product.category3_id = category3.id",
        ));

        $this->insert_join(array(
            "type" => "left",
            "obj_class" => "Category2",
            "condition" => "category3.category2_id = category2.id",
        ));

        $this->insert_join(array(
            "type" => "left",
            "obj_class" => "Category1",
            "condition" => "category2.category1_id = category1.id",
        ));

        $this->insert_field(array(
            "obj_class" => "Category2",
            "field" => "category1_id",
            "input" => array(
                "type" => "main_select",
                "values" => array(
                    "source" => "db_object_query",
                    "data" => array(
                        "nonset_value_caption_pair" =>
                            array(0, $this->app->get_lang_str("choose_one")),
                        "obj" => "Category1",
                        "captions_field_name" => "name",
                        "query_ex" => array(
                            "order_by" => "position ASC",
                        ),
                    ),
                    "dependency" => array(
                        "field" => "category3_category2_id",
                        "key_field_name" => "category2.category1_id",
                    ),
                ),
            ),
            "read" => 1,
        ));

        $this->insert_field(array(
            "obj_class" => "Category3",
            "field" => "category2_id",
            "input" => array(
                "type" => "main_select",
                "values" => array(
                    "source" => "db_object_query",
                    "data" => array(
                        "nonset_value_caption_pair" =>
                            array(0, $this->app->get_lang_str("choose_one")),
                        "obj" => "Category2",
                        "captions_field_name" => "name",
                        "query_ex" => array(
                            "order_by" => "position ASC",
                        ),
                    ),
                    "dependency" => array(
                        "field" => "category3_id",
                        "key_field_name" => "category3.category2_id",
                    ),
                ),
            ),
            "read" => 1,
        ));

        $this->insert_field(array(
            "obj_class" => "Category1",
            "field" => "position",
        ));

        $this->insert_field(array(
            "obj_class" => "Category1",
            "field" => "name",
        ));

        $this->insert_field(array(
            "obj_class" => "Category2",
            "field" => "position",
        ));

        $this->insert_field(array(
            "obj_class" => "Category2",
            "field" => "name",
        ));

        $this->insert_field(array(
            "obj_class" => "Category3",
            "field" => "position",
        ));

        $this->insert_field(array(
            "obj_class" => "Category3",
            "field" => "name",
        ));
//
        $this->insert_filter(array(
            "name" => "keywords",
            "relation" => "like_many",
            "type" => "text",
            "select" => array(
                "product.name_{$this->app->lang}",
                "product.description_{$this->app->lang}",
                "category1.name_{$this->app->lang}",
                "category2.name_{$this->app->lang}",
                "category3.name_{$this->app->lang}",
            ),
            "input" => array(
                "type_attrs" => array(
                    "class" => "varchar_mid_wide",
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "category2_category1_id",
            "relation" => "equal",
            "input" => array(
                "type" => "main_select",
                "values" => array(
                    "source" => "field",
                    "data" => array(
                        "nonset_value_caption_pair" => array(0, $this->app->get_lang_str("all")),
                    ),
                    "dependency" => array(
                        "filter" => "category3_category2_id",
                        "key_field_name" => "category2.category1_id",
                    ),
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "category3_category2_id",
            "relation" => "equal",
            "input" => array(
                "type" => "main_select",
                "values" => array(
                    "source" => "field",
                    "data" => array(
                        "nonset_value_caption_pair" => array(0, $this->app->get_lang_str("all")),
                    ),
                    "dependency" => array(
                        "filter" => "category3_id",
                        "key_field_name" => "category3.category2_id",
                    ),
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "category3_id",
            "relation" => "equal",
            "input" => array(
                "type" => "select",
                "values" => array(
                    "source" => "field",
                    "data" => array(
                        "nonset_value_caption_pair" => array(0, $this->app->get_lang_str("all")),
                    ),
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "price",
            "relation" => "less_equal",
            "input" => array(
                "type_attrs" => array(
                    "class" => "currency",
                ),
            ),
        ));

        $this->insert_filter(array(
            "name" => "price",
            "relation" => "greater_equal",
            "input" => array(
                "type_attrs" => array(
                    "class" => "currency",
                ),
            ),
        ));
    }
//
    function get_validate_conditions($context, $context_params) {
        return array(
            array(
                "field" => "category2_category1_id",
                "type" => "not_equal",
                "param" => 0,
                "message" => "product.category1_empty",
            ),
            array(
                "field" => "category3_category2_id",
                "type" => "not_equal",
                "param" => 0,
                "message" => "product.category2_empty",
            ),
            array(
                "field" => "category3_id",
                "type" => "not_equal",
                "param" => 0,
                "message" => "product.category3_empty",
            ),
            array(
                "field" => "name",
                "type" => "not_empty",
                "message" => "product.name_empty",
            ),
        );
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

//        $categories = $this->fetch_categories();
//        foreach ($categories as $category) {
//            $category->print_values();
//        }
    }

//
//    function set_categories() {
//        list($category1, $category2) = $this->fetch_categories();
//        $this->category1_id = $category1->id;
//        $this->category2_id = $category2->id;
//    }
//
//    function fetch_categories() {
//        $category3 = $this->fetch_db_object("category3", $this->category3_id);
//        $category2 = $this->fetch_db_object("category2", $category3->category2_id);
//        $category1 = $this->fetch_db_object("category1", $category2->category1_id);
//        return array($category1, $category2, $category3);
//    }

}

?>