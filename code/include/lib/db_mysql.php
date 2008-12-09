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
//
    function get_host() {
        return $this->_host;
    }

    function get_database() {
        return $this->_database;
    }

    function get_username() {
        return $this->_username;
    }

    function get_password() {
        return $this->_password;
    }
//    
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
            DL_INFO
        );

        if (!mysql_select_db($this->_database, $this->_connection)) {
            $err_str = mysql_error($this->_connection);
            $this->process_fatal_error(
                "Cannot select database '{$this->_database}'! MySQL error: {$err_str}"
            );
        }
        $this->write_log(
            "Selected database '{$this->_database}'",
            DL_INFO
        );

        $this->_has_connection = true;
    }

    function get_full_table_name($table_name) {
        return "{$this->_table_prefix}{$table_name}";
    }

    // Return number of rows in last UPDATE or DELETE query.
    function get_num_affected_rows() {
        return mysql_affected_rows($this->_connection);
    }

    // Return id, generated in last INSERT query by field with type primary_key
    function get_last_autoincrement_id() {
        return mysql_insert_id($this->_connection);
    }

    // Run MySQL query
    function &run_query($raw_query_str) {
        if (!$this->_has_connection) {
            $this->connect();
        }

        // Handling table name templates
        $query_str = $this->subst_table_prefix($raw_query_str);

        // Write query to log file
        $this->write_log(
            "Running MySQL query:\n  {$query_str}",
            DL_INFO
        );

        // Read time (before starting query)
        $start_time = get_microtime_as_double();

        // Run query
        $res = mysql_query($query_str, $this->_connection);
        if ($res === false) {
            $err_no = mysql_errno($this->_connection);
            $err_str = mysql_error($this->_connection);
            $this->process_fatal_error(
                "MySQL error #{$err_no}: {$err_str}"
            );
        }

        // Read time (after query is finished)
        $exec_time_str = number_format(
            get_microtime_as_double() - $start_time,
            6
        );

        // Write query result and timing to log file
        $n = $this->get_num_affected_rows();
        $this->write_log(
            "Query result: {$n} rows (in {$exec_time_str} sec)",
            DL_INFO
        );

        return $this->create_object(
            "MySqlDbResult",
            array(
                "res" => $res,
            )
        );
    }

    function subst_table_prefix($raw_query_str) {
        return preg_replace(
            '/{%(.*?)_table%}/',
            "{$this->_table_prefix}\$1",
            $raw_query_str
        );
    }

    function run_select_query($query) {
        return $this->run_query($query->get_string());
    }

    function get_select_query_num_rows($query) {
        // Return number of rows in SelectQuery object
        $count_query = clone($query);
        $count_query->order_by = "";
        if ($query->group_by == "" && !$query->distinct) {
            $count_query->select = "COUNT(*) AS n_rows";
            $res = $this->run_select_query($count_query);
            $row = $res->fetch_next_row();
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

    function get_insert_query($table_name, $fields_expression) {
        return
            "INSERT INTO {%{$table_name}_table%}\n" .
            "  SET\n" .
            "    {$fields_expression}";
    }

    function get_update_query($table_name, $fields_expression, $where_str) {
        return
            "UPDATE {%{$table_name}_table%}\n" .
            "  SET\n" .
            "    {$fields_expression}\n" .
            "  WHERE {$where_str}";
    }

    function get_delete_query($table_name, $where_str) {
        return
            "DELETE FROM {%{$table_name}_table%}\n" .
            "  WHERE {$where_str}";
    }

    function get_create_table_query($table_name, $create_table_expression) {
        return
            "CREATE TABLE IF NOT EXISTS {%{$table_name}_table%} (\n" .
            "    {$create_table_expression}\n" .
            "  )";
    }

    function get_update_table_query($table_name, $update_table_expression) {
        return
            "ALTER TABLE {%{$table_name}_table%}\n" .
            "    {$update_table_expression}";
    }

    function get_drop_table_query($table_name) {
        return "DROP TABLE IF EXISTS {%{$table_name}_table%}";
    }

    function get_truncate_table_query($table_name) {
        return "TRUNCATE TABLE {%{$table_name}_table%}";
    }

    function get_actual_table_names($get_table_names_with_prefix, $from_all_tables) {
        $table_names = array();
        $like_str = ($from_all_tables) ? "" : " LIKE '{$this->_table_prefix}%'";

        $res = $this->run_query("SHOW TABLES{$like_str}");
        while ($row = $res->fetch_next_row(true)) {
            $table_name_with_prefix = $row[0];
            if (preg_match(
                '/^' . $this->_table_prefix . '(\w+)$/',
                $table_name_with_prefix,
                $matches
            )) {
                if ($get_table_names_with_prefix) {
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
        while ($row = $res->fetch_next_row()) {
            $actual_fields_info[$row["Field"]] = $row;
        }
        return $actual_fields_info;
    }

    function get_actual_table_indexes_info($table_name) {
        $indexes_info = array();
        $res = $this->run_query("SHOW INDEX FROM {%{$table_name}_table%}");
        while ($row = $res->fetch_next_row()) {
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

    function fetch_next_row($with_numeric_keys = false) {
        // Fetch data trom the result
        $row = ($with_numeric_keys) ?
            mysql_fetch_row($this->_res) :
            mysql_fetch_assoc($this->_res);

        // Logger optimization
        if ($this->get_log_debug_level() == DL_EXTRA_DEBUG) {
            if (is_array($row)) {
                $log_str = "\n";
                foreach ($row as $field => $value) {
                    $value_str = (is_null($value)) ?
                        "NULL" :
                        qw(get_shortened_string($value, 300));
                    $log_str .= "  {$field} = {$value_str},\n";
                }
            } else {
                $log_str = " no rows left";
            }
            $this->write_log(
                "fetch():{$log_str}",
                DL_EXTRA_DEBUG
            );
        }
        
        return $row;
    }

    function fetch_next_row_to_db_object(&$obj) {
        $row = $this->fetch_next_row();
        if ($row === false) {
            return false;
        }
        $obj->set_field_values_from_row($row);
        $obj->sync_orig_field_values();
        return $row;
    }

}

?>