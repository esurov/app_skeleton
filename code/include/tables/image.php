<?php

class Image extends CustomDbObject {
    
    function init($params) {
        parent::init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "value" => $this->app->get_db_now_datetime(),
            "read" => 0,
            "update" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "updated",
            "type" => "datetime",
            "value" => $this->app->get_db_now_datetime(),
            "read" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "filename",
            "type" => "varchar",
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "width",
            "type" => "integer",
        ));

        $this->insert_field(array(
            "field" => "height",
            "type" => "integer",
        ));

        $this->insert_field(array(
            "field" => "type",
            "type" => "varchar",
        ));

        $this->insert_field(array(
            "field" => "content",
            "type" => "blob",
        ));

        $this->insert_field(array(
            "field" => "content_length",
            "type" => "integer",
        ));

        $this->insert_field(array(
            "field" => "is_thumbnail",
            "type"   => "boolean",
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
    function get_updated_as_gmt_str() {
        return get_gmt_str_from_timestamp(
            $this->app->get_timestamp_from_db_datetime($this->updated)
        );
    }
//
    // $uploaded_image here means UploadedImage-based class
    function set_image_fields_from($uploaded_image) {
        $this->width = $uploaded_image->get_width();
        $this->height = $uploaded_image->get_height();
        $this->type = $uploaded_image->get_type();
        $this->content = $uploaded_image->get_content();
        $this->content_length = $uploaded_image->get_content_length();
    }

    function create_in_memory_image() {
        return $this->app->create_component(
            "InMemoryImage", 
            array(
                "width" => $this->width,
                "height" => $this->height,
                "type" => $this->type,
                "content" => $this->content,
            )
        );
    }

}

?>