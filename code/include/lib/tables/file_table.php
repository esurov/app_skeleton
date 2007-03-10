<?php

class FileTable extends CustomDbObject {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "id",
            "type" => "primary_key",
        ));

        $this->insert_field(array(
            "field" => "created",
            "type" => "datetime",
            "read" => 0,
            "update" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "updated",
            "type" => "datetime",
            "read" => 0,
            "index" => "index",
        ));

        $this->insert_field(array(
            "field" => "filename",
            "type" => "varchar",
            "index" => "index",
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
    }
//
    function store(
        $field_names_to_store = null,
        $field_names_to_not_store = null
    ) {
        $this->created = $this->app->get_db_now_datetime();
        $this->updated = $this->app->get_db_now_datetime();
        if (!is_null($field_names_to_store)) {
            $field_names_to_store[] = "created";
            $field_names_to_store[] = "updated";
        }

        parent::store($field_names_to_store, $field_names_to_not_store);
    }
//
    function update(
        $field_names_to_update = null,
        $field_names_to_not_update = null
    ) {
        $this->updated = $this->app->get_db_now_datetime();
        if (!is_null($field_names_to_update)) {
            $field_names_to_update[] = "updated";
        }
        
        parent::update($field_names_to_update, $field_names_to_not_update);
    }
//
    function print_values($params = array()) {
        parent::print_values($params);

        $this->app->print_varchar_value(
            "file_filesize_formatted",
            get_formatted_filesize_str($this->content_length)
        );
    }
//
    function was_updated_since_last_browser_request() {
        $cached_gmt_str = (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) ?
            get_gmt_str_from_if_modified_since($_SERVER["HTTP_IF_MODIFIED_SINCE"]) :
            "";
        return ($this->get_updated_as_gmt_str() != $cached_gmt_str);
    }

    function get_updated_as_gmt_str() {
        return get_gmt_str_from_timestamp(
            $this->app->get_timestamp_from_db_datetime($this->updated)
        );
    }
//
    // $uploaded_file here means UploadedFile-based class
    function set_file_fields_from($uploaded_file) {
        $this->type = $uploaded_file->get_type();
        $this->content = $uploaded_file->get_content();
        $this->content_length = $uploaded_file->get_content_length();
    }

    function &create_in_memory_file() {
        return $this->create_object(
            "InMemoryFile", 
            array(
                "type" => $this->type,
                "content" => $this->content,
            )
        );
    }

}

?>