<?php

class DbResult {
    // result of the last query
    var $resource;
    var $log;

    function DbResult($resource, &$log) {
        $this->resource = $resource;
        $this->log =& $log;
    }

    // Return number of rows in last SELECT query.
    function get_num_rows() {
        return mysql_num_rows($this->resource);
    }

    function fetch($with_numeric_keys = false) {
        // Fetch data trom the result.
        $row = ($with_numeric_keys) ?
            mysql_fetch_row($this->resource) :
            mysql_fetch_assoc($this->resource);

        // Logger optimization
        if ($this->log->debug_level == 8) {
            if (is_array($row)) {
                $field_value_pairs = array();
                foreach ($row as $field => $value) {
                    $value = get_shortened_string($value, 300);
                    $field_value_pairs[] = "{$field}='{$value}'";
                }
                $log_str = join(", ", $field_value_pairs);
            } else {
                $log_str = "no rows left";
            }
            $this->log->write("DbResult", "fetch(): {$log_str}", 8);
        }
        return $row;
    }
}

?>