<?php

// Configuration file
class Config {

    var $_params;

    function Config() {
        $this->_params = array();
    }

    // Return value of the given parameter
    function get_value($name, $default_value = null) {
        return get_param_value($this->_params, $name, $default_value);
    }

    function set_value($name, $value) {
        $this->_params[$name] = $value;
    }

    // Read configuration file, parse it and store all data
    // Also read debug configuration file, if exists
    function read($filename) {
        $this->read_file($filename);

        $debug_filename = "{$filename}.debug";
        if (is_file($debug_filename)) {
            $this->read_file($debug_filename);
        }
    }

    // Read configuration file, parse it and store all data
    function read_file($filename) {
        if (!is_file($filename)) {
            trigger_error(
                "Configuration file '{$filename}' does not exist!",
                E_USER_ERROR
            );
        }

        $f = fopen($filename, "r");
        if (!$f) {
            trigger_error(
                "Cannot open configuration file '{$filename}'!",
                E_USER_ERROR
            );
        }

        flock($f, LOCK_SH);
        while ($line = fgets($f, 1024)) {
            // Comments and group specifiers may start only at the beginning of lines
            if (
                preg_match('/^#/', $line) ||
                preg_match('/^\/\//', $line) ||
                preg_match('/^\[/', $line)
            ) {
                continue;
            }

            if (preg_match('/^(.+?)\s*=\s?(.*?)\r?$/', $line, $matches)) {
                $this->_params[$matches[1]] = $matches[2];
            }
        }
        flock($f, LOCK_UN);

        fclose($f);
    }
/*
    // Write all data into configuration file
    function write($filename) {

        $f = fopen($filename, "w");
        if (!$f) {
            trigger_error(
                "Cannot open configuration file '{$filename}'!",
                E_USER_ERROR
            );
        }

        flock($f, LOCK_EX);

        reset($this->_params);
        while (list($name, $value) = each($this->_params)) {
            fputs($f, "$name = $value\n");
        }

        flock($f, LOCK_UN);
        fclose($f);
    }
*/

}

?>