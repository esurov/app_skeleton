<?php

class SQL {

    // MySQL connection.

    var $connection;
    var $table_prefix;
    var $log;


    function SQL($params, $log) {
        // Constructor.
        // Connect to database, using given hash of parameters.

        $host     = $params['host'];
        $username = $params['username'];
        $password = $params['password'];
        $database = $params['database'];

        $this->table_prefix = $params['table_prefix'];

        $this->log = $log;

        $this->connection = mysql_pconnect($host, $username, $password);

        if ($this->connection) {
            $this->log->write(
                'SQL',
                "Connected to SQL server '$host' as '$username'",
                3
           );
        } else {
            $this->log->write(
                'SQL',
                "Cannot connect to SQL server! -- $GLOBALS[php_errormsg]",
                0
            );
            die();
        }

        if (mysql_select_db($database, $this->connection)) {
            $this->log->write('SQL', "Selected database '$database'", 3);

        } else {
            $err = mysql_error($this->connection);
            $this->log->write(
                'SQL',
                "Cannot select database '$database' -- $err",
                0
            );
            die();
        }
    }


    function execute($query_str) {
        // Run MySQL query.
        // Return result.

        // Handling table name templates
        $query_str = preg_replace(
            "/{%(.*?)_table%}/e",
            " \$this->table_prefix . '$1' ",
            $query_str
        );

        // Write query to log file:
        $qs = preg_replace('/\s+/', ' ', $query_str);
        $this->log->write('SQL', "Running query \"$qs\"", 2);

        // Read time (before starting query):
        list($usec, $sec) = explode(' ', microtime());
        $t0 = (float)$sec + (float)$usec;

        // Run query:
        $result = mysql_query($query_str, $this->connection);
        if (!$result) {
            $this->log->write('SQL', "MySQL error: #" . mysql_errno($this->connection) . ": " .
                mysql_error($this->connection), 0);
            die();
        }

        // Read time (after query is finished):
        list($usec, $sec) = explode(' ', microtime());
        $t1 = ((float)$sec + (float)$usec);

        // Write query result and timing to log file:
        $t_str = number_format(($t1 - $t0), 3);
        $n = $this->affected_rows();
        $this->log->write('SQL', "  query result: $n rows (in $t_str sec)", 2);

        return $result;
    }


    function query($query_str) {
        // Run MySQL query.
        // Return SQLResult.

        return new SQLResult($this->execute($query_str), $this->log);
    }


    function run_select_query($query, $new_table = NULL) {
        // Run MySQL SELECT query.
        // Return SQLResult or save result into temporary table.

        // run subqueries:
        foreach ($query->sub_queries as $name => $q) {
            $this->execute("drop table if exists $name");
            $this->execute($q);
        }

        // run main query:
        if (isset($new_table)) {
            $query_str = "create temporary table $new_table " . $query->str();
            $this->execute($query_str);
            $res = NULL;

        } else {
            $query_str = $query->str();
            $res = $this->query($query_str);
        }

        // delete temporary tables:
        foreach ($query->sub_queries as $name => $q) {
            $this->execute("drop table $name");
        }

        return $res;
    }


    function get_query_num_rows($query) {
        // Return number of rows in SelectQuery object

        // calculate total number of rows:
        $count_query = $query;
        $count_query->order_by = "";
        if ($count_query->group_by == "") {
            $count_query->select = "count(*) as n_rows";
        $res = $this->run_select_query($count_query);
        $row = $res->fetch();
            $n = intval($row["n_rows"]);
        } else {
            $res = $this->run_select_query($count_query);
            $n = $res->num_rows();
        }
        return $n;
    }


    // Return number of rows in last UPDATE or DELETE query.
    function affected_rows() {
        return mysql_affected_rows($this->connection);
    }


    // Return id, generated in last INSERT query by auto_increment field.
    function insert_id() {
        return mysql_insert_id($this->connection);
    }


    }  // class SQL


class SQLResult {

    // MySQL result of the last query.

    var $result;
    var $log;


    function SQLResult($res, &$log) {
        // Constructor.

        $this->result = $res;
        $this->log =& $log;
    }

    // Return number of rows in last SELECT query.
    function num_rows() {
        return mysql_num_rows($this->result);
    }

    function fetch($with_numeric_keys = false) {
        // Fetch data trom the result.
        $row =
            ($with_numeric_keys) ?
            mysql_fetch_row($this->result) :
            mysql_fetch_assoc($this->result);

        if (is_array($row)) {
            $field_value_pairs = array();
            foreach ($row as $field => $value) {
                if (strlen($value) > 400) {
                    $value = substr($value, 0, 400) . "...";
                }
                $field_value_pairs[] = "{$field}='{$value}'";
            }
            $log_str = join(", ", $field_value_pairs);
        } else {
            $log_str = "no rows left";
        }
        $this->log->write("SQLResult", "fetch: {$log_str}", 3);
        return $row;
    }


}  // class SQLResult


class SelectQuery {

    // SQL SELECT query.

    var $select;
    var $from;
    var $where;
    var $group_by;
    var $order_by;
    var $limit;

    var $sub_queries;


    function SelectQuery($q) {
        // Constructor.

        $this->select   = isset($q['select'  ]) ? $q['select'  ] : '*';
        $this->from     = isset($q['from'    ]) ? $q['from'    ] : '' ;
        $this->where    = isset($q['where'   ]) ? $q['where'   ] : '1';
        $this->group_by = isset($q['group_by']) ? $q['group_by'] : '' ;
        $this->having   = isset($q['having'])   ? $q['having']   : '' ;
        $this->order_by = isset($q['order_by']) ? $q['order_by'] : '' ;
        $this->limit    = isset($q['limit'   ]) ? $q['limit'   ] : '' ;

        $this->sub_queries = array();
    }


    function add_sub_query($t_name, $q) {
        // Add new sub-query (creating temporary table with given name).
        // The query is based on given SELECT, FROM and GROUP BY clauses,
        // merged with FROM and WHERE clauses of the main query.
        // (Really, FROM in merged, while ORDER BY and LIMIT is ignored.)

        $str =
            "create temporary table $t_name " .
            '  (primary key(id)) ' .
            "select    $q[select]   " .
            "    from  $this->from  " . $q['from'] .
            "    where $this->where " .
            ($q['group_by'] ? " group by $q[group_by]": '') .
            ($this->limit   ? " limit    $this->limit": '');

        $this->sub_queries[$t_name] = $str;
    }


    function str() {
        // Return complete query string assembled from clauses.
        return
            "select    $this->select" .
            "    from  $this->from  " .
            "    where $this->where " .
            ($this->group_by ? " group by $this->group_by": '') .
            ($this->having   ? " having $this->having"    : '') .
            ($this->order_by ? " order by $this->order_by": '') .
            ($this->limit    ? " limit    $this->limit   ": '');
    }


    function expand($q) {
        // Add more statements to the clauses using given array.

        $this->select .= isset($q['select'  ]) ? " ,   $q[select]  " : '';
        $this->from   .= isset($q['from'    ]) ? "     $q[from]    " : '';
        $this->where  .= isset($q['where'   ]) ? " and $q[where]   " : '';
        $this->limit  .= isset($q['limit'   ]) ? "     $q[limit]  "  : '';

        $qwote = empty($this->order_by) ? '' : ', ';
        $this->order_by .= isset($q['order_by']) ? $qwote." $q[order_by]" : '';

        $qwote = empty($this->group_by) ? '' : ', ';
        $this->group_by .= isset($q['group_by']) ? $qwote." $q[group_by]" : '';

        $qwote = empty($this->having) ? '' : ' and ';
        $this->having .= isset($q['having']) ? $qwote."$q[having]" : '';
    }

}  // class SelectQuery


// Quote and escape string for mysql.
function qw($str) {
    return "'" . mysql_escape_string($str) . "'" ;
}

?>