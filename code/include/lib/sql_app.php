<?php

// Application with MySQL support -- base class.
class SqlApp extends App {

    var $sql;
    var $tables;

    function SqlApp(
        $app_name, $tables,
        $sql_config_file = 'sql', $config_dir = 'config', $log_dir = 'log'
    ) {
        // Constructor.

        parent::App($app_name, $config_dir, $log_dir);

        // Read SQL configuration:
        $sql_config = new Config();
        $sql_config->read("$config_dir/$sql_config_file.cfg");

        // Create SQL object:
        $this->sql = new SQL(
            array(
                'host'         => $sql_config->value('host'),
                'username'     => $sql_config->value('username'),
                'password'     => $sql_config->value('password'),
                'database'     => $sql_config->value('database'),
                'table_prefix' => $sql_config->value('table_prefix'),
            ),
            $this->log
        );

        $this->tables = $tables;
    }


    // SQL functions:

    function create_object($name) {
        // Create new object (derived from DbObject) and return it.

        $obj = new $this->tables[$name]();  // !!!
        return $obj;
    }


    function read_id_fetch_object(
        $name, $aspect, $where_str, $id_param_name = NULL
    ) {
        // Create new object, read it's PRIMARY KEY from CGI
        // (using CGI variable with name $id_param_name),
        // then fetch object data from MySQL table.
        // Return obtained object.

        // NB! parameter $aspect is not used for now..

        // NB! If PRIMARY KEY is invalid, it is set to zero.

        $obj = $this->create_object($name);

        $pr_key_name = $obj->primary_key_name();

        if (!isset( $id_param_name)) {
            $id_param_name = "{$name}_{$pr_key_name}";
        }

        $obj_key = intval(param($id_param_name));
        if (!(
            $obj_key != 0 &&
            $obj->fetch("{$name}.{$pr_key_name} = $obj_key and $where_str ")
        )) {
            $obj->$pr_key_name = 0;  // NB! Variable variable.
        }

        return $obj;
    }

}  // class SqlApp

?>