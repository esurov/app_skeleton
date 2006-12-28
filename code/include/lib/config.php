<?php

class Config {

    // Configuration file with simple format:
    // name = value

    var $params;

    function Config() {
        // Constructor.

        $this->params = array();
    }

    function get_value($name, $default_value = null) {
        // Return value of the given parameter.

        return (isset($this->params[$name]) ? $this->params[$name] : $default_value);
    }

    function set_value($name, $value) {
        $this->params[$name] = $value;
    }

    function read($filename) {
        // Read configuration file, parse it and store all data in hash.
        // Also read debug configuration file, if exists.

        $this->read_file($filename);

        $debug_filename = "{$filename}.debug";

        if (file_exists($debug_filename)) {
            $this->read_file($debug_filename);
        }
    }

    function read_file($filename) {
        // Read configuration file, parse it and store all data in hash.

        if (!file_exists($filename)) {
            trigger_error(
                "Configuration file '{$filename}' does not exist!",
                E_USER_ERROR
            );
        }

        $f = fopen($filename, "r");
        if (!$f){
            trigger_error(
                "Cannot open configuration file '{$filename}'!",
                E_USER_ERROR
            );
        }

        flock($f, LOCK_SH);

        while ($line = fgets($f, 1024)) {
            $line = chop($line);

            if (
                preg_match('/^#.*$/', $line) ||
                preg_match('/^\/\/.*$/', $line) ||
                preg_match('/^\[/', $line)
            ) {
                continue;
            }

            if (preg_match('/^(.+?)\s*=\s*(.*)$/', $line, $matches)) {
                $var_name = $matches[1];
                $var_value = $matches[2];
                $this->params[$var_name] = $var_value;
            }
        }

        flock($f, LOCK_UN);
        fclose($f);
    }

    function write($filename) {
        // Write all data from array into configuration file.

        $f = fopen($filename, "w");
        if (!$f) {
            trigger_error(
                "Cannot open configuration file '{$filename}'!",
                E_USER_ERROR
            );
        }

        flock($f, LOCK_EX);

        reset($params);
        while (list($name, $value) = each($this->params)) {
            fputs($f, "$name = $value\n");
        }

        flock($f, LOCK_UN);
        fclose($f);
    }

}

?>