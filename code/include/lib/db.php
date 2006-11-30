<?php

class Db {

    // MySQL connection.
    var $connection_info;
    var $connection;
    var $table_prefix;

    var $log;

    function Db($connection_info, &$log) {
        $this->connection_info = $connection_info;
        $host = $connection_info["host"];
        $database = $connection_info["database"];
        $username = $connection_info["username"];
        $password = $connection_info["password"];

        $this->table_prefix = $connection_info["table_prefix"];
        $this->log =& $log;

        $this->connection = mysql_pconnect($host, $username, $password);
        if ($this->connection === false) {
            $this->log->write(
                "Db",
                "Cannot connect to MySQL server!",
                0
            );
            die();
        }
        $this->log->write(
            "Db",
            "Connected to MySQL server '{$host}' as '{$username}'",
            3
        );

        if (!mysql_select_db($database, $this->connection)) {
            $err = mysql_error($this->connection);
            $this->log->write(
                "Db",
                "Cannot select database '{$database}' -- {$err}",
                0
            );
            die();
        }

        $this->log->write(
            "Db",
            "Selected database '{$database}'",
            3
        );
    }

    function get_connection_info() {
        return $this->connection_info;
    }

    function get_full_table_name($table_name) {
        return "{$this->table_prefix}{$table_name}";
    }

    function run_query($query_str) {
        // Run MySQL query.
        // Return result.

        // Handling table name templates
        $query_str = preg_replace(
            "/{%(.*?)_table%}/e",
            " \$this->table_prefix . '$1' ",
            $query_str
        );

        // Write query to log file:
        $qs = preg_replace('/\s+/', " ", $query_str);
        $this->log->write("Db", "Running query \"$qs\"", 2);

        // Read time (before starting query):
        list($usec, $sec) = explode(" ", microtime());
        $t0 = (float)$sec + (float)$usec;

        // Run query:
        $resource = mysql_query($query_str, $this->connection);
        if ($resource === false) {
            $this->log->write(
                "Db",
                "MySQL error: #" . mysql_errno($this->connection) . ": " .
                mysql_error($this->connection), 0);
            die();
        }

        // Read time (after query is finished):
        list($usec, $sec) = explode(" ", microtime());
        $t1 = ((float)$sec + (float)$usec);

        // Write query result and timing to log file:
        $t_str = number_format(($t1 - $t0), 6);
        $n = $this->get_num_affected_rows();
        $this->log->write("Db", "Query result: $n rows (in $t_str sec)", 2);

        return new DbResult($resource, $this->log);
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
        return mysql_affected_rows($this->connection);
    }

    // Return id, generated in last INSERT query by field with type primary_key
    function get_last_autoincrement_id() {
        return mysql_insert_id($this->connection);
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
                '/^' . $this->table_prefix . '(\w+)$/', $table_name_with_prefix, $matches
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

?>