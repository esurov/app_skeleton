<?php

class DbObject {

    // Base class for all MySQL table based classes.

    var $app = null;

    var $sql;  // SQL object to use for queries

    var $config;  // Config object

    var $class_name;  // class_name == table name (without prefix)

    var $fields;  // hash, not array (!)
                  // All info about fields are stored here.
                  // See also function insert_field().

    var $where_conditions;  // hash, not array (!)
                            // has 2 indexes: ['field_name']['type']

    var $table_indexes;    // additional table indexes
    var $select_from;      // FROM clause for SELECT query
    var $aux_select_from;  // FROM clause for update_auxilary_data() function


    function DbObject($class_name) {
        // Constructor.

        global $app;

        if (!isset($app)) {
            die("No application found!\n");
        }

        $this->app =& $app;

        $this->sql    =& $this->app->sql;
        $this->config =& $this->app->config;
        $this->log    =& $this->app->log;

        $this->class_name = $class_name;

        $this->fields = array();
        $this->where_conditions = array();

        $this->set_indefinite();  // initialize object to be indefinie

        $this->insert_table_indexes();
            // must be called again from derived class constructor
            // to create more than one index.

        $this->insert_select_from();
        $this->insert_aux_select_from();
            // must be called again from derived class constructor
            // if more than one table is used in select query.
    }


    // Core functions:

    function mysql2app_date($value) {
        list($year, $month, $day) = explode("-", $value);
        return "{$day}/{$month}/{$year}";
    }

    function mysql2app_time($value) {
        list($hour, $minute, $second) = explode(":", $value);
        return "{$hour}:{$minute}:{$second}";
    }

    function mysql2app_datetime($value) {
        list($year, $month, $day, $hour, $minute, $second) = explode("-", $value);
        return "{$day}/{$month}/{$year} {$hour}:{$minute}:{$second}";
    }

    function mysql2app_timestamp($value) {
        $datetime_values = array();
        $date_regexp = '/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/';
        preg_match($date_regexp, $value, $date_values);
        $year   = $datetime_values[1];
        $month  = $datetime_values[2];
        $day    = $datetime_values[3];
        $hour   = $datetime_values[4];
        $minute = $datetime_values[5];
        $second = $datetime_values[6];
        return sprintf("%02d/%02d/%04d %02d:%02d:%02d", $day, $month, $year, $hour, $minute, $second);
    }

    function app2mysql_date($date) {
        $parts = explode("/", $date);
        if (count($parts) != 3) {
            return "0000-00-00";
        }
        list($day, $month, $year) = $parts;
        $t = mktime(0, 0, 0, $month, $day, $year);
        return DbObject::mysql_date($t);
    }

    function mysql_now_date() {
        return DbObject::mysql_date(time());
    }

    function mysql_date($ts) {
        return date("Y-m-d", $ts);
    }

    function mysql_now_datetime() {
        return DbObject::mysql_datetime(time());
    }

    function mysql_datetime($ts) {
        return date("Y-m-d H:i:s", $ts);
    }

    function get_app_double_value($double_value, $decimals = 2) {
        return format_double_value($double_value);
    }

    function get_php_double_value($str) {
        $result = str_replace('.', '', $str);
        $result = str_replace(',', '.', $result);
        return doubleval($result);
    }

    function get_date_format() {
        // Return PHP date format usable for strftime() function.
        return '%Y-%m-%d';
    }

    function get_time_format() {
        // Return PHP date format usable for strftime() function.

        return '%Y-%m-%d %H:%M';
    }

    function primary_key_name() {
        // Retun name of the PRIMARY KEY column.

        return 'id';
    }

    function primary_key_value() {
        // Retun value of the PRIMARY KEY member variable.

        $pr_key_name = $this->primary_key_name();

        return $this->$pr_key_name;  // NB! Variable member variable.
    }


    function is_definite() {
        // Return true if PRIMARY KEY member variable is non-zero.
        // In other words, the object is uniquely defined.

        return($this->primary_key_value() != 0);
    }


    function set_indefinite() {
        // Set PRIMARY KEY member variable to zero.
        // In other words, make the object undefined.

        $pr_key_name = $this->primary_key_name();

        $this->$pr_key_name = 0;  // NB! Variable member variable
    }


    function insert_field($field) {
        // Store all parameters of the table field (column) in hash $fields .
        // The key of this hash is parameter 'name'.

        // Supported parameters:
        // 'name'   (string)  Name of the class member variable.
        //                    default value == 'column'
        //                    (or 'table_column' if the field is from other table).
        // 'table'  (string)  Name of the table in which the field is stored.
        //                    default value == class_name.
        // 'link'   (string)  Name of the table which will be linked.
        // 'alias'  (string)  Alias for linked table.
        // 'column' (string)  Name of the field in the table.
        // 'type'   (string)  Type of the column (integer, double, varchar, etc.)
        //                    integer, enum, varchar, double, datetime, timestamp.
        // 'values' (array)   Values for enum type.
        // 'width'  (int)     Width of the stored value (for varchar and double).
        // 'prec'   (int)     Precision of the stored value (for double only).
        // 'attr'   (string)  Additional column attributes for CREATE TABLE query.
        // 'value'  (*)       Initial value for class member variable.
        // 'aux'    (string)  SQL expression for updating auxilary data.
        // 'create' (bool)    Must be used in CREATE TABLE query.
        // 'store'  (bool)    May be stored to table.
        // 'update' (bool)    May be updated in table.
        // 'read'   (bool)    May be read from CGI.
        // 'write'  (bool)    May be written to web page.
        // 'input'  (string)  HTML form input type for this field.

        // set required parameters:
        if (!isset($field['table'])) {
            $field['table'] = isset($field['name']) ? '' : $this->class_name;
        }

        // join:
        if (isset($field['join'])) {

            // Set default join mode to INNER:
            if (!isset($field['join']['mode'])) {
                $field['join']['mode'] = 'inner';
            }

            // Table name must be given:
            if (!isset($field['join']['table'])) {
                die('Table name must be given for join');
            }

            // Set alias for joined table:
            if (!isset($field['join']['as'])) {
                $field['join']['as'] = $field['join']['table'];
            }

            // On what column we make join:
            if (!isset($field['join']['column'])) {
                $field['join']['column'] = 'id';
            }

            // Expand select query:
            $mode   = $field['join']['mode'];
            $table  = $field['join']['table'];
            $alias  = $field['join']['as'];
            $column = $field['join']['column'];

            $t_name = get_table_name($table);

            $this->select_from .=
                " $mode join $t_name as $alias" .
                " on $alias.$column = {$this->class_name}.$field[column]";
        }

        // links -- OBSOLETE CODE:
        if (isset($field['link'])) {
            $table = $field['link'];

            // FIXME: too many default assumptions here.

            // enter some default values:
            if (!isset($field['alias'])) {
                $field['alias'] = $table;
            }
            if (!isset($field['type'])) {
                $field['type'] = 'integer';
            }
            if (!isset($field['column'])) {
                $field['column'] = $table . '_id';
            }
            //if (!isset($field['input'])) {
            //    $field['input'] = 'select';
            //}

            // expand select query:
            $t_name = get_table_name($table);
            $this->select_from .=
                " inner join $t_name as $field[alias]" .
                " on $field[alias].id = {$this->class_name}.$field[column]";
        }

        if (!isset($field['name'])) {
            if (!isset($field['column'])) {
                die("$this->class_name: Error! Field name is not specified!<br>");
            }
            $table_name = (isset($field["alias"])) ? $field["alias"] : $field['table'];
            $field['name'] =
                ($field['table'] == $this->class_name) ?
                $field['column'] :
                ("{$table_name}_{$field['column']}");
        }

        if (!isset($field['width'])) {
            switch($field['type']) {
            case 'varchar':
                $field['width'] = 255;
                break;
            case 'double':
                $field['width'] = 16;
                break;
            }
        }

        if (!isset($field['select']) && $field['table'] != '' ) {
            $field['select'] = " $field[table].$field[column]";
        }

        if (!isset($field['attr'])) {
            $field['attr'] = '';
        }

        if (!isset($field['create'])) {
            $field['create'] = ($field['table'] == $this->class_name) ? 1 : 0;
        }

        if ($field['create'] && !isset($field['aux'])) {
            $field['aux'] = "$field[table].$field[column]";
        }

        if (!isset($field['store'])) {
            $field['store'] = (
                $field['table'] == $this->class_name            &&
                $field['create']                                &&
                $field['name'] != $this->primary_key_name() &&
                $field['type'] != 'timestamp'
           ) ? 1 : 0;
        }

        if (!isset($field['update'])) {
            $field['update'] = (
                $field['table'] == $this->class_name            &&
                $field['create']                                &&
                $field['name'] != $this->primary_key_name() &&
                $field['type'] != 'timestamp'
           ) ? 1 : 0;
        }

        if (!isset($field['read'])) {
            $field['read'] = ($field['type'] != 'timestamp') ? 1 : 0;
        }

        if (!isset($field['write'])) {
            $field['write'] = 1;
        }

        if (!isset($field['input'])) {
            $field['input'] = ($field['name'] == 'password') ? 'password' : 'text';
        }

        $name = $field['name'];
        $this->fields[$name] = $field;

        if (isset($field['value'])) {
            $this->$name = $field['value'];  // !!!
        }
    }


    function insert_where_condition($condition) {
        // Store all parameters of the table field (column) in hash $fields .
        // The key of this hash is parameter 'name'.

        $name     = $condition['name'];
        $relation = $condition['relation'];
        $this->where_conditions[$name][$relation] = $condition;
    }


    function set_where_condition_value($name, $relation, $value) {
        // Set (default) where condition value.

        if (isset($this->where_conditions[$name][$relation])) {
            $this->where_conditions[$name][$relation]['value'] = $value;

        } else {
            die("Non-existent where condition for {$this->class_name}!");
        };
    }


    function insert_table_indexes($table_indexes = NULL) {
        // Store table indexes for CREATE TABLE query.

        $pr_key = $this->primary_key_name();

        $this->table_indexes =
            isset($table_indexes) ?
            $table_indexes :
            "primary key($pr_key)";
    }


    function insert_select_from($select_from = NULL) {
        // Store FROM clause for SELECT query.

        $this->select_from =
            isset($select_from) ?
            $select_from :
            ($this->table_name() . " as $this->class_name");
    }


    function insert_aux_select_from($aux_select_from = NULL) {
        // Store FROM clause for update_auxilary_data function.

        $this->aux_select_from =
            isset($aux_select_from) ?
            $aux_select_from :
            $this->select_from;
    }


    function table_name() {
        // Return name of MySQL table where data are stored.

        return get_table_name($this->class_name);
    }


    // SQL functions:

    function create_table($table_name = NULL) {
        // Create MySQL table.

        if (!isset($table_name)) {
            $table_name = $this->table_name();
        }

        $str = "create table if not exists $table_name (";

        reset($this->fields);
        while (list($name, $f) = each($this->fields)) {
            if (!$f['create']) {
                continue;
            }
            $column = $f['column'];
            $type   = $f['type'  ];
            $attr   = $f['attr'  ];

            $str .= "$column $type";
            switch($type) {
            case 'enum':
                $str .=
                    " ('" .
                    implode("', '", array_keys($f['values'])) .
                    "')";
                break;

            case 'varchar':
                $str .= " ($f[width])";
                break;

            case 'double':
                $str .= " ($f[width], $f[prec])";
                break;
            }

            $str .= " not null $attr,\n";
        }
        $str .= $this->table_indexes;
        $str .= ")";

        $this->sql->query($str);
    }


    function update_table() {
        // Update MySQL table definition preserving existing data.

        $table_name = $this->table_name();

        // DEBUG:
        //echo "<br>[$table_name] ";

        // Check, whether this table already exists in database:
        $table_exists = false;
        $res = $this->sql->query("show tables");
        while($row = $res->fetch(true)) {
            if ($row[0] == $table_name) {
                $table_exists = true;
                break;
            }
        }

        if (!$table_exists) {
            $this->create_table();
            return;
        }

        // DEBUG:
        //echo "[table_exists] ";

        $old_fields = array();

        $res = $this->sql->query("show columns from $table_name");
        while($row = $res->fetch()) {
            $name = $row['Field'];
            $old_fields[$name] = array(
                'name'    => $row['Field'],
                'type'    => $row['Type'],
                'null'    => $row['Null'],
                'key'     => $row['Key'],
                'default' => isset($row['Default']) ? $row['Default'] : NULL,
                'extra'   => $row['Extra'],
           );
        }

        $query_str = "alter table $table_name ";

        $comma = '';
        $prev_column = '';

        foreach($this->fields as $name => $f) {
            if (!$f['create']) {
                continue;
            }
            $column = $f['column'];
            $type   = $f['type'  ];
            $attr   = $f['attr'  ];

            $str = "$column $type"; // create_definition

            switch($type) {
            case 'enum':
                $str .=
                    " ('" .
                    implode("', '", array_keys($f['values'])) .
                    "')";
                break;
            case 'varchar':
                $str .= " ($f[width])";
                break;
            case 'double':
                $str .= " ($f[width],$f[prec])";
                break;
            }

            $str .= " not null $attr";

            $query_str .= $comma;
            $comma = ', ';

            if (isset($old_fields[$column])) {
                $query_str .= "change column $name $str";

            } else {
                if ($prev_column == '') {
                    $query_str .= "add column $str first";
                } else {
                    $query_str .= "add column $str after $prev_column";
                }
            }

            $prev_column = $column;
        }

        foreach($old_fields as $name => $f) {
            if (!(
                isset($this->fields[$name]) &&
                $this->fields[$name]['create']
            )) {
                $query_str .= $comma;
                $comma = ', ';
                $query_str .= "drop column $name";
            }
        }

        $this->sql->execute($query_str);
    }


    function delete_table() {
        // Delete MySQL table.

        $table = $this->table_name();

        $str = "drop table if exists $table";

        $this->sql->query($str);
    }


    function get_select_query($fields_to_select = NULL) {
        // Return SelectQuery object.

        // select:
        $field_names = isset($fields_to_select) ?
            $fields_to_select : array_keys($this->fields);

        $select = '';
        $comma = '';

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];

            if (!isset($f['select'])) {  // ???
                continue;
            }

            $select .= $comma;
            $comma = ', ';

            $select .= $f['select'] . " as $name";
        }

        $query = new SelectQuery(array(
            'select' => $select,
            'from'   => $this->select_from,
        ));

        return $query;
    }

    /** Returns result of expanded select query for this object */
    function get_expanded_result($clauses = array()) {
        $query = $this->get_select_query();
        $query->expand($clauses);
        return $this->sql->run_select_query($query);
    }


    function expand_select_query($query) {
        // Make query more detailed.

        return $query;
    }


    function get_group_fields($aspect = '') {
        // Return fields on which group operation is defined.

        return NULL;
    }



    function get_num_objects_where($where_str = '1', $more = 0) {
        // Return number of objects using given WHERE condition.

        $query = $this->get_select_query();
        $query->expand(array(
            'where' => $where_str,
        ));

        if ($more) {
            $query = $this->expand_select_query($query);
        }

        return $this->sql->get_query_num_rows($query);
    }

    function store($fields_to_store = NULL) {
        // Store data to MySQL database.

        $this->log->write('DbObject', 'store()', 3);

        $field_names = isset($fields_to_store) ?
            $fields_to_store : array_keys($this->fields);

        $table = $this->table_name();
        $str = "insert into $table set";
        $comma = "\n";

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];
            if (!$f['store']) {
                continue;
            }
            $type   = $f['type'];
            $column = $f['column'];

            $str .= $comma;
            $comma = ",\n";

            $str .= "$column=";

            if (isset($this->$name)) {
                $value = $this->$name;  // !!!

                switch($type) {
                case 'date':
                case 'time':
                case 'datetime':
                case 'varchar':
                case 'text':
                case 'mediumtext':
                case 'longtext':
                case 'blob':
                case 'mediumblob':
                case 'longblob':
                case 'enum':
                    $str .= qw($value);
                    break;
                default:
                    $str .= $value;
                }

            } else {
                // no data in object variable:
                if ($type == 'datetime' || $type == 'date' || $type == 'time') {
                    $str .= 'now()';
                } else {
                    $this->sql->log->write(
                        "DbObject=$this->class_name",
                        "Cannot store field '$column' -- no data in object!",
                        0
                    );
                    die();
                }
            }
        }

        $this->sql->query($str);

        $pr_key_name = $this->primary_key_name();

        if (
            isset($this->fields[$pr_key_name]) &&
            $this->fields[$pr_key_name]['attr'] == 'auto_increment'
        ) {
            $this->$pr_key_name = $this->sql->insert_id();  // NB! Variable member variable
        }
    }


    function default_where_str($use_table_alias = true) {
        // Return default WHERE condition for fetching one object.

        $pr_key_name  = $this->primary_key_name();
        $pr_key_value = $this->primary_key_value();

        if ($use_table_alias) {
            return "{$this->class_name}.{$pr_key_name} = " . qw($pr_key_value);

        } else {
            return "$pr_key_name = " . qw($pr_key_value);
        }
    }


    function update($fields_to_update = NULL) {
        // Update data in MySQL database.

        $field_names = isset($fields_to_update) ?
            $fields_to_update : array_keys($this->fields);

        $table = $this->table_name();
        $str = "update $table set";
        $comma = "\n";

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];
            if (!$f['update'] && is_null($fields_to_update)) {
                continue;
            }
            $type   = $f['type'];
            $column = $f['column'];
            $value  = $this->$name;  // !!!

            $str .= $comma;
            $comma = ",\n";

            $str .= "$column=";

            switch($type) {
            case 'date':
            case 'datetime':
            case 'time':
            case 'varchar':
            case 'text':
            case 'mediumtext':
            case 'longtext':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
            case 'enum':
                $str .= qw($value);
                break;
            default:
                $str .= $value;
            }
        }

        $str .= " where " . $this->default_where_str(false);

        $this->sql->query($str);
    }


    function update_auxilary_data($where_str = NULL) {
        // Update auxilary data in the whole table.
        // Return number of updated rows.

        if (!isset($where_str)) {
            $where_str = $this->default_where_str();
        }

        // select:
        $select = '';
        $comma = '';

        reset($this->fields);
        while (list($name, $f) = each($this->fields)) {
            if (!$f['create']) {  // only creatable fields are involved
                continue;
            }
            $select .= "$comma $f[aux] as $name";
            $comma = ',';
        }

        $query = new SelectQuery(array(
            'select' => $select,
            'from'   => $this->aux_select_from,
            'where'  => $where_str,
        ));

        $table = $this->table_name();
        $new_table = 'new_' . $this->table_name();

        $pr_key_name = $this->primary_key_name();

        $query_str =
            "create temporary table $new_table (primary key($pr_key_name)) " .
            $query->str();
        $this->sql->query($query_str);

        $str = "replace $table select * from $new_table";
        $this->sql->query($str);
        $n = $this->sql->affected_rows();

        $this->sql->query("drop table $new_table");

        return $n;
    }


    function update_all_auxilary_data() {
        // Update auxilary fields in the whole table.

        return $this->update_auxilary_data('1');
    }


    function fetch($where_str = NULL, $more = 0) {
        // Fetch data from MySQL database using given WHERE condition.
        // Return true if found.

        if (!isset($where_str)) {
            $where_str = $this->default_where_str();
        }

        $query = $this->get_select_query();
        $query->expand(array(
            'where' => $where_str,
        ));

        if ($more) {
            $query = $this->expand_select_query($query);
        }

        $res = $this->sql->run_select_query($query);

        if ($res->num_rows() != 1) {  // record not found
            return false;
        }

        $row = $res->fetch();
        $this->fetch_row($row);

        return true;
    }


    function fetch_row($row) {
        // Fetch data from query result row.

        foreach($row as $name => $value) {
            $this->$name = $value;  // NB! Variable variable.
        }
    }


    function del() {
        // Delete row from MySQL table.
        // Return number of deleted rows.

        $table = $this->table_name();

        $query_str = "delete from $table where " . $this->default_where_str(false);
        $this->sql->query($query_str);

        return $this->sql->affected_rows();
    }


    function del_where($where_condition) {
        // Delete row from MySQL table using where condition.
        // Return number of deleted rows.

        $table = $this->table_name();

        $query_str = "delete from $table where $where_condition";
        $this->sql->query($query_str);

        return $this->sql->affected_rows();
    }


    // CGI functions:

    function read_order_by($default_order_by = 'id asc', $additional = array()) {
        // Return array ($order_by, $params).
        // $order_by --
        //     'WHERE' clause, read from CGI and tested to be valid.
        // $params  --
        //     order_by parameter for use in pager-generated links.

        list($res_field, $res_dir) = explode(' ', "$default_order_by asc");
        $a = explode(' ', param('order_by'), 2);

        // check, if given field exists:
        if (isset($this->fields[$a[0]]) || in_array($a[0], $additional)) {
            $res_field = $a[0];
            $res_dir = 'asc';
            if (isset($a[1]) && preg_match( '/^(asc|desc)$/i', $a[1])) {
                $res_dir = $a[1];
            }
        }

        $order_by = "$res_field $res_dir";
        $params = array(
            'order_by' => $order_by,
        );

        return array($order_by, $params);
    }


    function read_where($fields_to_read = NULL) {
        // Return list ($where, $params).
        // $where --
        //     with 'WHERE' clause, read from CGI and tested to be valid.
        // $params --
        //     valid read parameters for use in pager-generated links.

        $where = '1';
        $params = array();

        $field_names = isset($fields_to_read) ?
            $fields_to_read : array_keys($this->fields);

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];

            /*
            if (!$f['read']) {
                continue;
            }
            */

            $type  = $f['type'];
            $pname = $this->class_name . '_' . $name;

            switch($type) {
            case 'datetime':
            case 'date':
                $less = param("{$pname}_less");
                if ($less) {
                    $where .= " and $f[select] <= ". qw($less);
                    $params["{$pname}_less"] = $less;
                }
                $greater = param("{$pname}_greater");
                if ($greater) {
                    $where .= " and $f[select] >= " . qw($greater);
                    $params["{$pname}_greater"] = $greater;
                }
                break;

            case 'integer':
                $less = param("{$pname}_less");
                if ($less) {
                    $where .= " and $f[select] <= " . intval($less);
                    $params["{$pname}_less"] = $less;
                }

                $greater = param("{$pname}_greater");
                if ($greater) {
                    $where .= " and $f[select] >= " . intval($greater);
                    $params["{$pname}_greater"] = $greater;
                }

                $equal = param("{$pname}_equal");
                if ($equal) {
                    $where .= " and $f[select] = " . intval($equal);
                    $params["{$pname}_equal"] = $equal;
                }

                break;

            case 'double':
                $less = param("{$pname}_less");
                if ($less) {
                    $where .= " and $f[select] <= " . doubleval($less);
                    $params["{$pname}_less"] = $less;
                }

                $greater = param("{$pname}_greater");
                if ($greater) {
                    $where .= " and $f[select] >= " . doubleval($greater);
                    $params["{$pname}_greater"] = $greater;
                }

                $equal = param("{$pname}_equal");
                if ($equal) {
                    $where .= " and $f[select] = " . doubleval($equal);
                    $params["{$pname}_equal"] = $equal;
                }

                break;

            case 'varchar':
            case 'enum':
                // Max: "not so cool."

                $like = param("{$pname}_like");
                if ($like != '') {
                    $where .= " and $f[select] like " . qw("%$like%");
                    $params["{$pname}_like"] = $like;
                }

                // NB! no 'break' here.

                $equal = param("{$pname}_equal");
                if ($equal != '') {
                    $where .= " and $f[select] = " . qw($equal);
                    $params["{$pname}_equal"] = $equal;
                }

                break;
            //default:
            }
        }

        return array($where, $params);
    }


    function read_where_cool($conditions_to_read = NULL) {
        // Read where conditions (from search form).
        $condition_names = isset($conditions_to_read) ?
            $conditions_to_read : array_keys($this->where_conditions);

        foreach($this->where_conditions as $name => $field_conditions) {
            if (in_array($name, $condition_names)) {
                foreach($field_conditions as $relation => $cond) {
                    $pname = "{$this->class_name}_{$name}_{$relation}";
                    $value = param($pname);
                    if (!is_null($value)) {
                        $this->where_conditions[$name][$relation]['value'] = $value;
                    }
                }
            }
        }
    }


    function get_where_condition() {
        // Read where conditions (from search form).

        $where_str = "1";
        $havings = array();

        foreach($this->where_conditions as $name => $field_conditions) {

            foreach($field_conditions as $relation => $cond) {
                $type = (isset($cond["type"])) ?
                    $cond["type"] :
                    $this->fields[$name]["type"];
                $select = (isset($cond["select"])) ?
                    $cond["select"] :
                    $this->fields[$name]["select"];
                
                $nonset_value =
                    (isset($cond["input"]["nonset_id"])) ? $cond["input"]["nonset_id"] : "";

                if (!isset($cond['value']) || $cond['value'] == $nonset_value) {
                    continue;
                }
                $value = $cond['value'];

                switch($type) {
                case 'integer':
                    if (is_array($value)) {
                        $value_str = array();
                        foreach($value as $val) {
                            $value_str[] = intval($val);
                        }
                    } else {
                        $value_str = intval($value);
                    }
                    break;

                case 'double':
                    if (is_array($value)) {
                        $value_str = array();
                        foreach($value as $val) {
                            $value_str[] = double($val);
                        }
                    } else {
                        $value_str = double($value);
                    }
                    break;

                case 'date':
                    if (is_array($value)) {
                        $value_str = array();
                        foreach($value as $val) {
                            $value_str[] = qw($this->app2mysql_date($val));
                        }
                    } else {
                        $value_str = qw($this->app2mysql_date($value));
                    }
                    break;

                default:
                    if (is_array($value)) {
                        $value_str = array();
                        foreach($value as $val) {
                            $value_str[] = qw($val);
                        }
                    } else {
                        $value_str = qw($value);
                    }
                }

                switch($relation) {
                case 'less':
                    $where_str .= " and $select <= $value_str";
                    break;

                case 'greater':
                    $where_str .= " and $select >= $value_str";
                    break;

                case 'equal':

                    if (is_array($value)) {
                        $where_arr = array();
                        foreach($value as $val) {
                            $where_arr[] = "$select = $val";
                        }
                        $where_str .= ' and (' . join(' or ', $where_arr) . ')';
                    } else {
                        $where_str .= " and $select = $value_str";
                    }

                    break;

                case 'like':
                    $where_str .= " and $select like concat('%', $value_str, '%')";
                    break;

                case "having_equal":
                    $havings[] = "({$select} = {$value_str})";
                    break;

                case "having_less":
                    $havings[] = "({$select} <= {$value_str})";
                    break;

                case "having_greater":
                    $havings[] = "({$select} >= {$value_str})";
                    break;

                default:
                    if (is_array($value)) {
                        $where_arr = array();
                        foreach($value_str as $val) {
                            $where_arr[] = "$select = $val";
                        }
                        $where_str .= ' and (' . join(' or ', $where_arr) . ')';
                    } else {
                        $where_str .= " and $select = $value_str";
                    }

                    break;
                }
            }
        }
        $having_str = join(" and ", $havings);
        return array($where_str, $having_str);
    }


    function get_where_params() {
        // Read where conditions (from search form).

        $params = array();

        foreach($this->where_conditions as $name => $field_conditions) {
            foreach($field_conditions as $relation => $cond) {
                if (isset($cond['value'])) {
                    $pname = "{$this->class_name}_{$name}_{$relation}";
                    $params[$pname] = $cond['value'];
                }
            }
        }

        return $params;
    }

    function read($fields_to_read = NULL) {
        // Read given fields from CGI query.

        $field_names = isset($fields_to_read) ?
            $fields_to_read : array_keys($this->fields);

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];

            if (!$f['read']) {
                continue;
            }

            $type  = $f['type'];
            $pname = $this->class_name . '_' . $name;
            $param_value = param($pname);

            switch($type) {
            case 'integer':
                $value = intval($param_value);
                break;
            case 'enum':
                $value = $param_value;
                if (!isset($f['values'][$value])) {
                    $value = 1;
                }
                break;
            case 'double':
                $value = $this->get_php_double_value($param_value);
                break;
            default:
                $value = isset($param_value) ? $param_value : '';
            }

            $this->$name = $value;  // !!!
        }
    }

    function write($fields_to_write = NULL) {
        // Return an array with given fields stored in it
        // for future use in a page template.

        $h = array();

        $field_names = isset($fields_to_write) ?
            $fields_to_write : array_keys($this->fields);

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];
            if (!$f['write']) {
                continue;
            }
            $type  = $f['type'];
            $value = $this->$name;  // !!!

            if (
                $type == 'blob' ||
                $type == 'mediumblob' ||
                $type == 'longblob'
            ) {  // do not try to write blobs...
                continue;
            }

            $pname = $this->class_name . '_' . $name;

            $h[$pname] = htmlspecialchars($value);  // sentry

            switch($type) {
            case 'enum':
                $h[$pname . '_name'] = $f['values'][$value];
                break;

            case 'integer':
                $h[$pname . '_signed'] =
                    ($value > 0) ? "+$value" :
                        (($value<0)? ('&minus;' . -$value) : '0');
                $h[$pname . '_signed_nozero'] =
                    ($value > 0) ? "+$value" :
                        (($value<0)? ('&minus;' . -$value) : '');
                break;

            case 'double':
                $h[$pname] = $this->get_app_double_value($value, 2);
                $h[$pname . "_orig"] = $this->get_app_double_value($value, $f["prec"]);
                $h[$pname . "_long"] = $this->get_app_double_value($value, 5);

                break;

            case 'varchar':
                $h[$pname . '_url'] = urlencode($value);
                if ($value != "" && isset($f["values"]) && array_key_exists($value, $f["values"])) {
                    $h[$pname] = htmlspecialchars($f["values"][$value]);
                }
                break;

            case 'date':
                $date_regexp = '/^(\d+)-(\d+)-(\d+)$/';
                $date_values = array();
                if (preg_match($date_regexp, $value, $date_values)) {
                    $year   = $date_values[1];
                    $month  = $date_values[2];
                    $day    = $date_values[3];
                    $t = mktime(0, 0, 0, $month, $day, $year);
                    $date_str =
                        ($t != -1) ?
                        strftime($this->get_date_format(), $t) :
                        'N/A';
                    $h[$pname . '_str'] = $date_str;
                }
                break;

            case 'datetime':
                $t = mysql_to_unix_time($value);
                if ($t) {
                    $date_str =
                        ($t != -1) ?
                        strftime($this->get_time_format(), $t) :
                        'N/A';
                    $h[$pname . '_str'] = $date_str;
                }
                break;

            case 'timestamp':
                $date_regexp = '/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/';
                $date_values = array();
                if (preg_match($date_regexp, $value, $date_values)) {
                    $year   = $date_values[1];
                    $month  = $date_values[2];
                    $day    = $date_values[3];
                    $hour   = $date_values[4];
                    $minute = $date_values[5];
                    $second = $date_values[6];
                    $t = mktime($hour, $minute, $second, $month, $day, $year);
                    $date_str =
                        ($t != -1) ?
                        strftime($this->get_time_format(), $t) :
                        'N/A';
                    $h[$pname . '_str'] = $date_str;
                }
                break;

            case 'text':
            case 'mediumtext':
                $h[$pname . '_raw'] = $value;
                $html = htmlspecialchars($value);
                $html = preg_replace('/\r/', ''    , $html);
                $html = preg_replace('/\n/', '<br>', $html);
                $h[$pname . '_html'] = "<p>$html</p>";
                break;
            }
        }

        return $h;
    }


    function write_form($fields_to_write = NULL) {
        // Return an array with given fields stored in it
        // for future use in a form template.

        $h = array();

        $field_names = isset($fields_to_write) ?
            $fields_to_write : array_keys($this->fields);

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];
            if (!$f['write']) {
                continue;
            }
            $type = $f['type'];

            // changed to make form inputs for empty fields:
            //$value = $this->$name;  // !!!
            $value = isset($this->$name) ? $this->$name : '' ;  // !!!

            $pname = $this->class_name . '_' . $name;

            $value = htmlspecialchars($value);

            $h[$pname] = $value;  // sentry

            $h[$pname . '_input'] =
                "<input type=\"$f[input]\" name=\"$pname\" value=\"$value\">";

            $h[$pname . '_hidden'] =
                "<input type=\"hidden\" name=\"$pname\" value=\"$value\">";

            switch($type) {
            case 'integer':
                if (isset($f['input'])) {
                    if (is_array($f['input'])) {
                        $input = $f['input'];
                        switch($input['type']) {
                        case 'select':
                            if ($this->fields[$name]['type'] == 'enum') {
                                $items = $this->fields[$name]['values'];

                            } else if (isset($input['values'])) {
                                $items = $input['values'];

                            } else if (isset($input['items_callback'])) {
                                $items = $this->$input['items_callback']();  // NB! Variable function

                            } else {
                                $from     = $input['from'];
                                $data     = $input['data'];
                                $caption  = $input['caption'];
                                $query_ex =
                                    isset($input['query_ex']) ?
                                    $input['query_ex'] :
                                    array();

                                $obj = $this->app->create_object($from);
                                $items = $obj->get_items($data, $caption, $query_ex);
                            }

                            if (
                                isset($input['nonset_id']) &&
                                isset($input['nonset_name'])
                            ) {
                                $items = array_merge(
                                    array(array(
                                        'id' => $input['nonset_id'],
                                        'name' => $input['nonset_name']
                                    )), $items);
                            }

                            $options = make_options($items, $value);

                            $h[$pname . '_input'] =
                                "<select name=\"$pname\">$options</select>";
                            break;

                        default:
                            $h["{$pname}_input"] =
                                "<input type=\"$input[type]\" name=\"$pname\" value=\"$value\">";
                        }

                    } else {  // COMPATIBILITY

                        switch($f['input']) {
                        case 'checkbox':
                            $input_text = $value ? 'checked' : '';
                            $h[$pname . '_input'] ="<input type=\"checkbox\"".
                               "id=\"$pname\" name=\"$pname\" value=\"1\" $input_text>";
                            break;

                        case 'select':
                            if (isset($f['join'])) {  // COMPATIBILITY
                                $options = $this->get_options($name, $f['join']['table']);
                                $h[$pname . '_input'] =
                                    "<select name=\"$pname\">$options</select>";

                            } else if (isset($f['link'])) {  // COMPATIBILITY
                                $options = $this->get_options($name, $f['link']);
                                $h[$pname . '_input'] =
                                    "<select name=\"$pname\">$options</select>";
                            }
                            break;
                        }
                    }
                }
                break;

            case 'enum':
                if (isset($f['values'][$value])) {
                    $h[$pname . '_name'] = $f['values'][$value];
                }
                $options = write_options($f['values'], $value);
                $h[$pname . '_input'] = "<select name=\"$pname\">$options</select>";
                break;

            case 'double':
                $h[$pname] = $this->get_app_double_value($value, 2);
                $orig_value = $this->get_app_double_value($value, $f["prec"]);
                $h[$pname . "_orig"] = $orig_value;
                $h[$pname . "_long"] = $this->get_app_double_value($value, 5);

                $h[$pname . "_input"] =
                    "<input type=\"text\" name=\"$pname\" value=\"{$orig_value}\">";
                $h[$pname . "_hidden"] =
                    "<input type=\"hidden\" name=\"$pname\" value=\"{$orig_value}\">";
                break;

            case "varchar":
                if (isset($f["values"])) {
                    $options = write_options($f["values"], $value);
                    $h[$pname . "_input"] = "<select name=\"$pname\">$options</select>";
                }
                break;
            }
        }

        return $h;
    }

    function write_search_form($nonset_id = '', $nonset_name = '---') {
        // Return an array with given fields stored in it
        // for future use in a search form template.

        $h = array();

        foreach ($this->where_conditions as $name => $field_conditions) {
            foreach ($field_conditions as $relation => $cond) {
                $pname = "{$this->class_name}_{$name}_{$relation}";

                $input = isset($cond['input']) ? $cond['input'] : array('type' => 'text');
                $value = isset($cond['value']) ? $cond['value'] : '';
                $value = htmlspecialchars($value);
                $h["{$pname}_hidden"] =
                    "<input type=\"hidden\" name=\"$pname\" value=\"$value\">";

                $field_type = (isset($cond["type"])) ?
                    $cond["type"] :
                    $this->fields[$name]["type"];

                switch ($input['type']) {
                case 'select':
                    if ($field_type == 'enum') {
                        $items = $this->fields[$name]['values'];

                    } else if (isset($input['values'])) {
                        $items = $input['values'];

                    } else {
                        $from     = $input['from'];
                        $data     = $input['data'];
                        $caption  = $input['caption'];
                        $query_ex =
                            isset($input['query_ex']) ?
                            $input['query_ex'] :
                            array('order_by' => $input['caption']); // default order_by.                        );

                        $obj = $this->app->create_object($from);
                        $items = $obj->get_items($data, $caption, $query_ex);
                    }

                    array_unshift($items, array(
                        'id'   => (isset($input['nonset_id']) ? $input['nonset_id'] : $nonset_id),
                        'name' => (isset($input['nonset_name']) ? $input['nonset_name'] : $nonset_name),
                    ));
                    $options = make_options($items, $value);

                    $h[$pname . '_input'] =
                        "<select name=\"$pname\">$options</select>";
                    break;

                case 'multiselect':

                    $value = isset($cond['value']) ? $cond['value'] : array();

                    if ($field_type == 'enum') {
                        $items = $this->fields[$name]['values'];

                    } else if (isset($input['values'])) {
                        $items = $input['values'];

                    } else {
                        $from     = $input['from'];
                        $data     = $input['data'];
                        $caption  = $input['caption'];
                        $query_ex =
                            isset($input['query_ex']) ?
                            $input['query_ex'] :
                            array('order_by' => $input['caption']); // default order_by.

                        $obj = $this->app->create_object($from);
                        $items = $obj->get_items($data, $caption, $query_ex);
                    }

                    if (isset($input['nonset_id']) && isset($input['nonset_name'])) {
                        array_unshift($items, array(
                            'id'   => $input['nonset_id'],
                            'name' => $input['nonset_name'],
                        ));
                    }

                    $options = make_options($items, $value);

                    $h[$pname . '_input'] =
                        "<select name=\"{$pname}[]\" multiple>$options</select>";
                    break;

                default:
                    $h["{$pname}_input"] =
                        "<input type=\"$input[type]\" name=\"$pname\" value=\"$value\">";
                }
            }
        }

        return $h;
    }


    // Other functions:

    function verify() {
        // Check object data.
        // Return error message or empty string if all ok.

        return array();
    }


    function check_links() {
        // Check, whether any links to this object exist.
        // Return error message or empty string if all ok.

        return array();
    }


    function plural_resource_name() {
        // Return class name in plural.

        return "{$this->class_name}s";
    }


    function singular_resource_name() {
        // Return class name in singular.
        return "$this->class_name";
    }

    function plural_name() {
        return $this->plural_resource_name();
    }

    function singular_name() {
        return $this->singular_resource_name();
    }

    function quantity_str($n)
    {
        // Return nicely formatted string with given quantity of objects.
        // Redefine for non-statdard plural (i. e. categories).

        return("$n&nbsp;" . (
            ($n == 1) ?
            $this->singular_name() :
            $this->plural_name()
        ));
    }


    function get_options($field_name, $table , $order_by = 'id asc') {
        // Return HTML code with series of <option> tags,
        // fetched from given table, using fields 'id' and 'name'.

        $h = array();

        $obj = $this->app->create_object($table);  // !!!
        $query = $obj->get_select_query();

        $query->expand(array(
            'order_by' => $order_by,
        ));

        $res = $this->app->sql->run_select_query($query);
        while($row = $res->fetch()) {
            $obj->fetch_row($row);
            $h[$obj->id] = $obj->name;
        }

        $value = isset($this->$field_name) ? $this->$field_name : 0;  // !!!

        return write_options($h, $value);
    }


    function get_items($field_id, $field_name, $q = array()) {
        // Return array created from two columns fetched from table.

        $fields = array($field_id, $field_name);

        $query = $this->get_select_query($fields);
        $query->expand($q);

        $res = $this->sql->run_select_query($query);

        $h = array();
        while($row = $res->fetch()) {
            $this->fetch_row($row);
            $id   = $this->$field_id;    // NB! Variable varialbe
            $name = $this->$field_name;  // NB! Variable varialbe
            $h[] = array(
                'id'   => $id,
                'name' => $name,
           );
        }

        return $h;
    }


    function get_links() {
        // Return hash: table => field.
        // Must be redefined for classes, where need
        // cascading deleted object.
        // This function are using in function del_cascading()

        return array();

    }


    function del_cascading() {
        // Cascading delete objects.
        // Using 'id' current object ($this->id) and
        // returned array get_links()

        $links = $this->get_links(); // check reference integrity
        foreach($links as $table => $field) {

            $obj = $this->app->create_object($table);
            $obj_links = $obj->get_links();

            $this->log->write('DelCascading',"Try delete in {$table} where {$field}={$this->id}",3);

            if (count($obj_links) == 0 ) {     // empty
                $obj->del_where("{$field} = {$this->id}");
            } else {
                $del_query = $obj->get_select_query();
                $del_query->expand(array(
                    'where' => "{$table}.{$field} = {$this->id}",
                ));
                $res = $this->sql->run_select_query($del_query);
                while($row = $res->fetch()) {
                    $obj->fetch_row($row);
                    $obj->del_cascading();
                }
            }
        }

        $this->del();
        return true;
    }

    function get_dependency_array(
        $main_objs,
        $dep_obj_name,
        $dep_key_name,
        $dep_data_field_name = "name",
        $query_clauses = array()
    ) {
        $main_obj_ids = get_ids_from_items($main_objs);
        $main_to_dep_rows = array(array(0));

        if (!isset($query_clauses["where"])) {
            $query_clauses["where"] = "1";
        }
        foreach ($main_obj_ids as $main_obj_id) {
            $q = $query_clauses;
            $q["where"] .= " and {$dep_key_name} = {$main_obj_id}";

            $dep_obj_ids = get_names_from_items(get_table_field_values(
                $dep_obj_name,
                $dep_data_field_name,
                $q
            ));

            array_unshift($dep_obj_ids, 0);
            $main_to_dep_rows[] = $dep_obj_ids;
        }
        return $main_to_dep_rows;
    }

    function is_unique($field_names, $old_obj) {
        if (!is_array($field_names)) {
            $field_names = array($field_names);
        }

        $res = false;
        foreach ($field_names as $field_name) {
            $res = $res || $this->{$field_name} == $old_obj->{$field_name};
        }

        return $res || !$this->field_values_exist($field_names);
    }

    function field_values_exist($field_names) {
        $field_sqls = array();
        foreach ($field_names as $field_name) {
            $field_sqls[] = "{$field_name} = " . qw($this->{$field_name});
        }
        $query = new SelectQuery(array(
            "from"  => get_table_name($this->class_name),
            "where" => join(" and ", $field_sqls),
        ));

        $n = $this->sql->get_query_num_rows($query);

        return ($n != 0);
    }

    function fetch_linked_object($obj_name, $id) {
        $obj = $this->app->create_object($obj_name);
        if ($id != 0) {
            $obj->fetch("{$obj_name}.id = {$id}");
        }
        return $obj;
    }

    function save($refetch_after_save = true) {
        $is_definite = $this->is_definite();

        if ($is_definite) {
            $this->update();
        } else {
            $this->store();
        }
        
        if ($refetch_after_save) {
            $this->fetch();
        }

        return $is_definite;
    }

}  // class DbObject


function write_select(
    $name_table, $field_link, $field = 'name', $order_by = 'name asc', $where ='1'
) {
    $id_equal = param("{$field_link}_equal");
    $selected = ($id_equal) ? $id_equal : '';

    $option_string = get_option_ex(
        $name_table,
        $field,
        $id_equal,
        $order_by,
        $where
    );
    
    $select = "<select name=\"{$field_link}_equal\"> \n".
    "<option value=\"0\" {$selected}>all</option>\n".
    $option_string.
    "</select>";

    return $select;
}

// Auxilary functions:

function get_table_name($class_name) {
    // Return name of MySQL table where data are stored.

    global $app;

    return $app->sql->table_prefix . $class_name;
}


function get_option_ex(
    $table, $field = 'name', $selected = 0,
    $order_by = 'name asc', $where ='1'
) {
    // Return HTML code with series of <option> tags,
    // fetched from given table, using fields 'id' and 'name'.
    $h = get_table_field_values(
        $table, $field, array("order_by" => $order_by, "where" => $where_str)
    );

    return write_options($h, $selected);
}

?>