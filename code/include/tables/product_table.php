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

        $this->insert_field(array(
            "field" => "primary_image_id",
            "type" => "foreign_key",
            "read" => 0,
        ));

        $this->insert_field(array(
            "field" => "primary_thumbnail_image_id",
            "type" => "foreign_key",
            "read" => 0,
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
    }
//
    function insert_filters() {
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
    function del() {
        $this->del_image("primary_image_id");
        $this->del_image("primary_thumbnail_image_id");

        $product_images = $this->fetch_product_images();
        foreach ($product_images as $product_image) {
            $product_image->del();
        }

        parent::del();
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

    function validate($context = null, $context_params = array()) {
        $messages = parent::validate($context, $context_params);

        if (was_file_uploaded("primary_image_file")) {
            $this->validate_condition(
                $messages,
                array(
                    "field" => "primary_image_id",
                    "type" => "uploaded_file_types",
                    "param" => array(
                        "input_name" => "primary_image_file",
                        "type" => "images",
                    ),
                    "message" => "product.primary_image_bad",
                )
            );
        } else {
            $orig_image_id = $this->get_orig_field_value("primary_image_id");
            if ($orig_image_id == 0) {
                $messages[] = new ErrorStatusMsg("product.primary_image_empty");
            }
        }

        if (was_file_uploaded("image_file")) {
            $this->validate_condition(
                $messages,
                array(
                    "field" => "image_id",
                    "type" => "uploaded_file_types",
                    "param" => array(
                        "input_name" => "image_file",
                        "type" => "images",
                    ),
                    "message" => "product.image_bad",
                )
            );
        } else {
            if ($this->get_num_product_images() == 0) {
                $messages[] = new ErrorStatusMsg("product.image_empty");
            }
        }
            
        return $messages;
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "products_list_item") {
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->primary_thumbnail_image_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.primary_thumbnail_image",
                "_primary_thumbnail_image.html",
                "_primary_thumbnail_image_empty.html"
            );
        }

        if ($this->_context == "product_edit") {
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->primary_image_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.primary_image",
                "_primary_image.html",
                "_primary_image_empty.html"
            );

            $product_images_list =& $this->create_object(
                "ObjectsList",
                array(
                    "templates_dir" => "{$this->_templates_dir}/images",
                    "template_var" => "product.images",
                    "template_var_prefix" => "product.image",
                    "objects" => $this->fetch_product_images(),
                    "context" => "product_edit_list_item",
                )
            );
            $product_images_list->print_values();
        }
    }
//
    function fetch_product_images() {
        if (!$this->is_definite()) {
            return array();
        }

        $query_ex = new SelectQueryEx(array(
            "where" => "product_image.product_id = {$this->id}",
            "order_by" => "product_image.position ASC",
        ));

        return $this->fetch_db_objects_list("ProductImage", $query_ex);
    }

    function get_num_product_images() {
        return count($this->fetch_product_images());
    }

}

?>