<?php

class ImageTable extends FileTable {
    
    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "width",
            "type" => "integer",
        ));

        $this->insert_field(array(
            "field" => "height",
            "type" => "integer",
        ));

        $this->insert_field(array(
            "field" => "is_thumbnail",
            "type"   => "boolean",
        ));
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
        return $this->create_object(
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