<?php

class _File {

    function get_type() {
        return "";
    }

    function get_mime_type() {
        return get_mime_type_by_file_extension($this->get_type());
    }

    function get_content() {
        return null;
    }
    
    function get_content_length() {
        return 0;
    }

}

class InMemoryFile extends _File {

    // Type of file content (based on file extension)
    var $_type;
    
    // File binary content
    var $_content;

    // File binary content length in bytes, if null - autodetect
    var $_content_length;

    function _init($params) {
        $this->set_type(get_param_value($params, "type", ""));
        $this->set_content(
            get_param_value($params, "content", ""),
            get_param_value($params, "content_length", null)
        );
    }

    function get_type() {
        return $this->_type;
    }

    function set_type($type) {
        $this->_type = $type;
    }

    function get_content() {
        return $this->_content;
    }
    
    function set_content($content, $content_length = null) {
        $this->_content = $content;
        $this->_content_length = $content_length;
    }

    function get_content_length() {
        return (is_null($this->_content_length)) ?
            strlen($this->get_content()) :
            $this->_content_length;
    }

}

class File extends _File {

    var $_full_filename;

    function _init($params) {
        $this->set_full_filename(get_param_value($params, "filename", ""));
    }
//
    function get_type() {
        return get_file_extension($this->get_full_filename());
    }

    function get_content() {
        return file_get_contents($this->get_full_filename());
    }

    function get_content_length() {
        clearstatcache();
        return filesize($this->get_full_filename());
    }
//
    function set_full_filename($full_filename) {
        $this->_full_filename = $full_filename;
    }

    function get_full_filename() {
        return $this->_full_filename;
    }

    function get_filename() {
        return basename($this->get_full_filename());
    }

    function get_file_path() {
        return dirname($this->get_full_filename());
    }

}

class UploadedFile extends File {
    
    var $_input_name;
    var $_uploaded_file_info;

    function _init($params) {
        parent::_init($params);

        $this->_input_name = get_param_value($params, "input_name", "file");
        $this->_uploaded_file_info = get_uploaded_file_info($this->_input_name);
        
        $this->set_full_filename($this->_uploaded_file_info["tmp_name"]);
    }
//
    function get_orig_filename() {
        return $this->_uploaded_file_info["name"];
    }

}

?>