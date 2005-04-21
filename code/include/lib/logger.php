<?php

class Logger {

    // Log file with 'debug level' support.

    var $filename;
    var $level;


    function Logger($filename, $level = 1) {
        // Constructor.

        $this->filename = $filename;
        $this->level    = $level;

        $this->max_file_size = 1048576;
    }

    function set_max_file_size($max_file_size) {
        $this->max_file_size = $max_file_size;
    }

    function write($class_name, $message, $level = 1) {
        // Write $class_name and $message to log file,
        // if debug level is high enough.

        // Skip insignificant messages:
        if ($level > $this->level) {
            return;
        }

        $f = @fopen($this->filename, "a");
        if (!$f) {
            return;
        }
        flock($f, LOCK_EX);  // lock

        $file_stats = fstat($f);
        $file_size = $file_stats["size"];
        if ($file_size > $this->max_file_size) {
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
        $time_str = strftime('%Y.%m.%d %H:%M:%S', time());
        $s = "{$time_str} - [{$class_name}] {$message_text}\n";
        fputs($f, $s);

        flock($f, LOCK_UN);  // unlock
        fclose($f);
    }

}  // class Config

?>