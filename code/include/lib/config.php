<?php

class Config {

    // Configuration file with simple format:
    // name = value

    var $params;

    function Config() {
        // Constructor.

        $this->params = array();
    }


    function value($name) {
        // Return value of the given parameter.

        return (isset($this->params[$name]) ? $this->params[$name] : NULL);
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


    function read_file($filename)
    {
        // Read configuration file, parse it and store all data in hash.

        if (!file_exists($filename)) {
            die("Configuration file '$filename' does not exist.");
        }

        $f = fopen($filename, "r");
        if (!$f){
            die("Cannot open configuration file [$filename].");
        }

        flock($f, LOCK_SH);

        while ($line = fgets($f, 1024)) {
            $line = chop($line);

            if ($this->ignore_comments) {
                $line = preg_replace('/^#.*$/', '', $line);
                $line = preg_replace('/([^\\\\])#.*$/', "$1", $line);
                $line = preg_replace('/\/\/.*$/', '', $line);
                $line = preg_replace('/\\\\#/', '#', $line);
                $line = trim($line);
            }
            if ($line == '') {
                continue;
            }

            list ($name, $value) = preg_split('/\s*=\s?/', $line, 2);

            $this->params[$name] = $value;
        }

        flock($f, LOCK_UN);
        fclose($f);

        return '';
    }


    function write($filename) {
        // Write all data from array into configuration file.

        $f = fopen($filename, "w");
        if (!$f) {
            die("Cannot open configuration file [$filename].");
        }

        flock($f, LOCK_EX);

        reset($params);
        while (list($name, $value) = each($this->params)) {
            fputs($f, "$name = $value\n");
        }

        flock($f, LOCK_UN);
        fclose($f);
    }

}  // class Config

?>
