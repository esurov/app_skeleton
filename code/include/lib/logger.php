<?php

// logger.php,v 1.3 2001/11/09 11:23:01 max Exp

// class Logger.


class Logger {

    // Log file with 'debug level' suppurt.

    var $filename;
    var $level;


function Logger($filename, $level = 1)
{
    // Constructor.

    $this->filename = $filename;
    $this->level    = $level;
}


function write($class_name, $message, $level = 1)
{
    // Write $class_name and $message to log file,
    // if debug level is high enough.

    // Skip insignificant messages:
    if($level > $this->level) {
        return;
    }

    $f = @fopen($this->filename, "a");
    if(!$f) {
        // Max: if no log file -- just return.
        //die("Cannot open log file [$filename].");
        return;
    }
    flock($f, LOCK_EX);  // lock

    $time_str = strftime('%Y.%m.%d %H:%M:%S', time());
    $s = "$time_str - [$class_name] $message\n";
    fputs($f, $s);

    flock($f, LOCK_UN);  // unlock
    fclose($f);
}


}  // class Config


?>
