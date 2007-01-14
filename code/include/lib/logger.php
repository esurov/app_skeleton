<?php

class Logger {

    // Logger with 'debug_level' support.
    var $_truncate_always;
    var $_filename;
    var $_debug_level;
    var $_max_filesize;

    function Logger() {
        $this->set_truncate_always(false);
        $this->set_filename("log/app.log");
        $this->set_debug_level(1);
        $this->set_max_filesize(1048576);
    }
//
    function set_truncate_always($truncate_always) {
        $this->_truncate_always = (bool) $truncate_always;
        
        if ($this->_truncate_always) {
            $this->truncate();
        }
    }

    function set_filename($filename) {
        $this->_filename = $filename;

        if ($this->_truncate_always) {
            $this->truncate();
        }
    }

    function set_debug_level($debug_level) {
        $this->_debug_level = $debug_level;
    }

    function get_debug_level() {
        return $this->_debug_level;
    }

    function set_max_filesize($max_filesize) {
        if ($max_filesize > 0) {
            $this->_max_filesize = $max_filesize;
        }
    }
//
    function write($header, $message, $debug_level) {
        // Write $header and $message to log file,
        // if debug_level is high enough.

        // Skip insignificant messages:
        if ($debug_level > $this->_debug_level) {
            return;
        }

        $f = @fopen($this->_filename, "a");
        if (!$f) {
            return;
        }
        flock($f, LOCK_EX);  // lock

        $file_stats = fstat($f);
        $filesize = $file_stats["size"];
        if ($filesize > $this->_max_filesize) {
            ftruncate($f, 0);
        }

        if (is_array($message)) {
            $messages = array();
            foreach ($message as $key => $value) {
                $messages[] = "'{$key}' => '{$value}'";
            }
            $message_text = "array(" . join(", ", $messages) . ")";
        } else {
            $message_text = $message;
        }
        $time_str = strftime("%Y-%m-%d %H:%M:%S", time());
        fputs($f, "{$time_str} - [{$header}] {$message_text}\n");

        flock($f, LOCK_UN);  // unlock
        fclose($f);
    }

    function truncate() {
        $f = @fopen($this->_filename, "a");
        if ($f) {
            flock($f, LOCK_EX);  // lock
            ftruncate($f, 0);
            flock($f, LOCK_UN);  // unlock
            fclose($f);
        }
    }

}

?>