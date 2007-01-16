<?php

define("DL_FATAL_ERROR", 1);
define("DL_ERROR", 2);
define("DL_WARNING", 4);
define("DL_INFO", 8);
define("DL_DEBUG", 16);
define("DL_EXTRA_DEBUG", 32);

// Logger with 'debug_level' support.
class Logger extends AppObject {

    var $_filename;

    var $_truncate_always;
    var $_debug_level;
    var $_max_filesize;

    function _init($params) {
        parent::_init($params);

        $this->set_debug_level(constant($this->get_config_value("log_debug_level")), DL_ERROR);
        $this->set_max_filesize($this->get_config_value("log_max_filesize", 0));
        $this->set_truncate_always($this->get_config_value("log_truncate_always", false));

        $this->set_filename("log/app.log");

        if ($this->_truncate_always) {
            $this->truncate();
        } else {
            $this->_write_line(str_repeat("-", 100));
        }
    }
//
    function set_filename($filename) {
        $this->_filename = $filename;
    }

    function set_truncate_always($truncate_always) {
        $this->_truncate_always = (bool) $truncate_always;
    }

    function get_debug_level() {
        return $this->_debug_level;
    }

    function set_debug_level($debug_level) {
        $this->_debug_level = $debug_level;
    }

    function set_max_filesize($max_filesize) {
        $this->_max_filesize = $max_filesize;
    }
//
    // Write header and message to log file, if debug_level is high enough
    function write($header, $message, $debug_level) {
        if ($debug_level > $this->_debug_level) {
            return;
        }

        if (is_array($message)) {
            $message_lines = array();
            foreach ($message as $key => $value) {
                $message_lines[] = "    '{$key}' => '{$value}',\n";
            }
            $line_text = "array(\n" . join("", $message_lines) . ")";
        } else {
            $line_text = $message;
        }

        $datetime_str = date("Y-m-d H:i:s");
        $this->_write_line("{$datetime_str} - [{$header}] {$line_text}");
    }

    function _write_line($line_text) {
        if ($this->_max_filesize > 0) {
            clearstatcache();
            if (filesize($this->_filename) > $this->_max_filesize) {
                $this->truncate();
            }
        }

        $f = @fopen($this->_filename, "a");
        if (!$f) {
            return;
        }
        flock($f, LOCK_EX);  // lock
        fputs($f, "{$line_text}\n");
        flock($f, LOCK_UN);  // unlock
        fclose($f);
    }

    function truncate() {
        $f = @fopen($this->_filename, "a");
        if (!$f) {
            return;
        }
        flock($f, LOCK_EX);  // lock
        ftruncate($f, 0);
        flock($f, LOCK_UN);  // unlock
        fclose($f);
    }

}

?>