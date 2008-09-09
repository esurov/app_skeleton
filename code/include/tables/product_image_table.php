<?php

class ProductImageTable extends OrderedDbObject {
    
    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "product_id",
            "type" => "foreign_key",
        ));

        $this->insert_field(array(
            "field" => "image_id",
            "type" => "foreign_key",
        ));

        $this->insert_field(array(
            "field" => "thumbnail_image_id",
            "type" => "foreign_key",
        ));
    }
//
    function del() {
        $this->del_image("thumbnail_image_id");
        $this->del_image("image_id");
        
        parent::del();
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        if ($this->_context == "product_edit_list_item") {
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->thumbnail_image_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.thumbnail_image",
                "_thumbnail_image.html",
                null
            );
            $this->app->print_db_object_info(
                $this->app->fetch_image_without_content($this->image_id),
                $this->_templates_dir,
                "{$this->_template_var_prefix}.image",
                "_image.html",
                null
            );
        }
    }
//
    function get_position_where_str() {
        return "product_id = {$this->product_id}";
    }

}

?>