<?php

class Logger {

    // Logger with 'debug level' support.
    var $truncate_always;
    var $filename;
    var $debug_level;
    var $max_filesize;

    function Logger() {
        $this->set_truncate_always(false);
        $this->set_filename("log/app.log");
        $this->set_debug_level(1);
        $this->set_max_filesize(1048576);
    }
//
    function set_truncate_always($new_truncate_always) {
        $this->truncate_always = (bool) $new_truncate_always;
        
        if ($this->truncate_always) {
            $this->truncate();
        }
    }

    function set_filename($new_filename) {
        $this->filename = $new_filename;

        if ($this->truncate_always) {
            $this->truncate();
        }
    }

    function set_debug_level($new_debug_level) {
        $this->debug_level = $new_debug_level;
    }

    function set_max_filesize($new_max_filesize) {
        $max_filesize = $new_max_filesize;
        if ($new_max_filesize > 0) {
            $this->max_filesize = $new_max_filesize;
        }
    }
//
    function write($class_name, $message, $debug_level) {
        // Write $class_name and $message to log file,
        // if debug level is high enough.

        // Skip insignificant messages:
        if ($debug_level > $this->debug_level) {
            return;
        }

        $f = @fopen($this->filename, "a");
        if (!$f) {
            return;
        }
        flock($f, LOCK_EX);  // lock

        $file_stats = fstat($f);
        $filesize = $file_stats["size"];
        if ($filesize > $this->max_filesize) {
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
        fputs($f, "{$time_str} - [{$class_name}] {$message_text}\n");

        flock($f, LOCK_UN);  // unlock
        fclose($f);
    }

    function truncate() {
        $f = @fopen($this->filename, "a");
        if ($f) {
            flock($f, LOCK_EX);  // lock
            ftruncate($f, 0);
            flock($f, LOCK_UN);  // unlock
            fclose($f);
        }
    }

}

?>