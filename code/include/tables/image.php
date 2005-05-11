<?php

class Image extends CustomDbObject {
    
    function Image() {
        parent::CustomDbObject("image");

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "value" => $this->get_db_now_datetime(),
            "read" => 0,
            "update" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "updated",
            "type" => "datetime",
            "value" => $this->get_db_now_datetime(),
            "read" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "filename",
            "type" => "varchar",
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "filesize",
            "type" => "integer",
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
            "field" => "is_thumbnail",
            "type"   => "boolean",
        ));
    }
//
    function update($fields = null) {
        $this->updated = $this->get_db_now_datetime();
        parent::update($fields);
    }
//
    function get_updated_as_gmt_str() {
        return get_gmt_str_from_timestamp(
            $this->get_timestamp_from_db_datetime($this->updated)
        );
    }
//
    function was_uploaded($input_name = "image_file") {
        return
            isset($_FILES[$input_name]) &&
            $_FILES[$input_name]["error"] != UPLOAD_ERR_NO_FILE;
    }

    function read_uploaded_content($input_name) {
        $uploaded_file_info = $_FILES[$input_name];

        $this->filename = $uploaded_file_info["name"];
        $this->filesize = $uploaded_file_info["size"];
        $this->type = $uploaded_file_info["type"];

        $filename = $uploaded_file_info["tmp_name"];
        $image = getimagesize($filename);

        $this->width = $image[0];
        $this->height = $image[1];

        $this->content = file_get_contents($filename);
    }
//
    function init_from($image_new) {
        $this->filename = $image_new->filename;
        $this->filesize = $image_new->filesize;
        $this->type = $image_new->type;

        $this->width = $image_new->width;
        $this->height = $image_new->height;

        $this->content = $image_new->content;

        $this->is_thumbnail = $image_new->is_thumbnail;
    }

    function create_from_image_magick($image_magick, $filename) {
        $image = new Image();
        $image->width = $image_magick->get_width();
        $image->height = $image_magick->get_height();
        $image->filename = $filename;
        $image->filesize = $image_magick->get_filesize();
        $image->type = $image_magick->get_mime_type();
        $image->content = $image_magick->get_content();
        return $image;
    }
}

?>