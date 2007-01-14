<?php

class File extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

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
            "field" => "filesize",
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
    }
//
    function get_updated_as_gmt_str() {
        return get_gmt_str_from_timestamp(
            $this->app->get_timestamp_from_db_datetime($this->updated)
        );
    }
//
    function read_uploaded_info($input_name) {
        $uploaded_file_info = get_uploaded_file_info($input_name);

        $this->filename = $uploaded_file_info["name"];
        $this->filesize = $uploaded_file_info["size"];
        $this->type = $uploaded_file_info["type"];
        
        $filename = $uploaded_file_info["tmp_name"];
        $this->content = file_get_contents($filename);
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

        $this->app->print_varchar_value(
            "file_filesize_formatted",
            get_formatted_filesize_str($this->filesize)
        );
    }

}

?>