<?php

class MySqlDb extends AppObject {

    // MySQL connection info
    var $_host;
    var $_database;
    var $_username;
    var $_password;

    // MySQL connection
    var $_connection;
    var $_table_prefix;

    var $_has_connection;

    function _init($params) {
        parent::_init($params);

        $this->_host = $params["host"];
        $this->_database = $params["database"];
        $this->_username = $params["username"];
        $this->_password = $params["password"];

        $this->_table_prefix = $params["table_prefix"];

        $this->_has_connection = false;
    }

    function connect() {
        $this->_connection = mysql_pconnect(
            $this->_host,
            $this->_username,
            $this->_password
        );
        if ($this->_connection === false) {
            $this->process_fatal_error(
                "Cannot connect to MySQL server '{$this->_host} as {$this->_username}'!"
            );
        }
        $this->write_log(
            "Connected to MySQL server '{$this->_host}' as '{$this->_username}'",
            3
        );

        if (!mysql_select_db($this->_database, $this->_connection)) {
            $err_str = mysql_error($this->_connection);
            $this->process_fatal_error(
                "Cannot select database '{$this->_database}'! MySQL error: {$err_str}"
            );
        }
        $this->write_log(
            "Selected database '{$this->_database}'",
            3
        );

        $this->_has_connection = true;
    }

    function get_full_table_name($table_name) {
        return "{$this->_table_prefix}{$table_name}";
    }

    // Run MySQL query
    function run_query($query_str) {
        if (!$this->_has_connection) {
            $this->connect();
        }
        // Handling table name templates
        $query_str = preg_replace(
            '/{%(.*?)_table%}/',
            "{$this->_table_prefix}\$1",
            $query_str
        );

        // Write query to log file
        $this->write_log(
            "Running MySQL query:\n{$query_str}",
            2
        );

        // Read time (before starting query)
        list($usec, $sec) = explode(" ", microtime());
        $t0 = (float)$sec + (float)$usec;

        // Run query
        $res = mysql_query($query_str, $this->_connection);
        if ($res === false) {
            $err_no = mysql_errno($this->_connection);
            $err_str = mysql_error($this->_connection);
            $this->process_fatal_error(
                "Db",
                "MySQL error #{$err_no}: {$err_str}"
            );
        }

        // Read time (after query is finished)
        list($usec, $sec) = explode(" ", microtime());
        $t1 = ((float)$sec + (float)$usec);

        // Write query result and timing to log file
        $t_str = number_format(($t1 - $t0), 6);
        $n = $this->get_num_affected_rows();
        $this->write_log(
            "Query result: {$n} rows (in $t_str sec)",
            2
        );

        return $this->create_object(
            "MySqlDbResult",
            array(
                "res" => $res,
            )
        );
    }

    function run_select_query($query) {
        return $this->run_query($query->get_string());
    }

    function get_select_query_num_rows($query) {
        // Return number of rows in SelectQuery object
        $count_query = $query;
        $count_query->order_by = "";
        if ($query->group_by == "" && !$query->distinct) {
            $count_query->select = "COUNT(*) AS n_rows";
            $res = $this->run_select_query($count_query);
            $row = $res->fetch();
            $n = (int) $row["n_rows"];
        } else {
            if (!$query->distinct) {
            $count_query->select = "NULL";
            }
            $res = $this->run_select_query($count_query);
            $n = $res->get_num_rows();
        }
        return $n;
    }

    // Return number of rows in last UPDATE or DELETE query.
    function get_num_affected_rows() {
        return mysql_affected_rows($this->_connection);
    }

    // Return id, generated in last INSERT query by field with type primary_key
    function get_last_autoincrement_id() {
        return mysql_insert_id($this->_connection);
    }

    function drop_table($table_name) {
        $this->run_query("DROP TABLE IF EXISTS {%{$table_name}_table%}");
    }

    function get_actual_table_names($with_prefix = false) {
        $table_names = array();

        $res = $this->run_query("SHOW TABLES");
        while ($row = $res->fetch(true)) {
            $table_name_with_prefix = $row[0];
            if (preg_match(
                '/^' . $this->_table_prefix . '(\w+)$/', $table_name_with_prefix, $matches
            )) {
                if ($with_prefix) {
                    $table_names[] = $table_name_with_prefix;
                } else {
                    $table_names[] = $matches[1];
                }
            }
        }

        return $table_names;
    }

    function get_actual_table_fields_info($table_name) {
        $actual_fields_info = array();
        $res = $this->run_query("SHOW COLUMNS FROM {%{$table_name}_table%}");
        while ($row = $res->fetch()) {
            $actual_fields_info[$row["Field"]] = $row;
        }
        return $actual_fields_info;
    }

    function get_actual_table_indexes_info($table_name) {
        $indexes_info = array();
        $res = $this->run_query("SHOW INDEX FROM {%{$table_name}_table%}");
        while ($row = $res->fetch()) {
            $indexes_info[] = $row;
        }
        
        $actual_indexes_info = array();
        
        if (count($indexes_info) > 0) {
            $i = 0;
            $index_name = $indexes_info[$i]["Key_name"];
            $index_non_unique = $indexes_info[$i]["Non_unique"];
            $index_field_names = array($indexes_info[$i]["Column_name"]);
            $i++;
            while ($i < count($indexes_info)) {
                if ($indexes_info[$i]["Key_name"] == $index_name) {
                    $index_field_names[] = $indexes_info[$i]["Column_name"];
                } else {
                    $actual_indexes_info[$index_name] = array(
                        "Non_unique" => $index_non_unique,
                        "Field_names" => $index_field_names,
                    );

                    $index_name = $indexes_info[$i]["Key_name"];
                    $index_non_unique = $indexes_info[$i]["Non_unique"];
                    $index_field_names = array($indexes_info[$i]["Column_name"]);
                }
                $i++;
            }
            $actual_indexes_info[$index_name] = array(
                "Non_unique" => $index_non_unique,
                "Field_names" => $index_field_names,
            );
        }
        
        return $actual_indexes_info;
    }
}

class MySqlDbResult extends AppObject {

    // Result resource of the last MySQL query
    var $_res;

    function _init($params) {
        $this->_res = $params["res"];
    }

    // Return number of rows in last SELECT query.
    function get_num_rows() {
        return mysql_num_rows($this->_res);
    }

    function fetch($with_numeric_keys = false) {
        // Fetch data trom the result.
        $row = ($with_numeric_keys) ?
            mysql_fetch_row($this->_res) :
            mysql_fetch_assoc($this->_res);

        // Logger optimization
        if ($this->get_log_debug_level() == 8) {
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
            $this->write_log(
                "fetch(): {$log_str}",
                8
            );
        }
        
        return $row;
    }

}

?>