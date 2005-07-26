<?php

class DbObject {

    // Base class for all MySQL table based classes

    var $table_name;  // Table name (without db specific prefix)
    var $fields;
    var $indexes;
    var $select_from;      // FROM clause for SELECT query

    var $print_params = array();
    var $filters;
    var $order_by;

    var $app = null;

    // Variables from App
    var $db;
    var $config;
    var $log;
    var $page;

    var $avail_langs; // Available languages
    var $lang; // Current language
    var $dlang; // Default language


    function DbObject($table_name) {
        global $app;

        if (!isset($app)) {
            die("No app found in DbObject()!\n");
        }

        $this->app =& $app;

        $this->db =& $this->app->db;
        $this->config =& $this->app->config;
        $this->log =& $this->app->log;
        $this->page =& $this->app->page;

        $this->avail_langs =& $this->app->avail_langs;
        $this->lang =& $this->app->lang;
        $this->dlang =& $this->app->dlang;

        $this->table_name = $table_name;

        $this->fields = array();
        $this->indexes = array();
        $this->filters = array();

        $this->set_indefinite();  // initialize object to be indefinie

        $this->insert_select_from();
    }
//
    function get_plural_name() {
        return $this->get_message($this->get_plural_resource_name());
    }

    function get_singular_name() {
        return $this->get_message($this->get_singular_resource_name());
    }

    function get_plural_resource_name() {
        return "{$this->table_name}s";
    }

    function get_singular_resource_name() {
        return "$this->table_name";
    }

    function get_quantity_str($n) {
        return("{$n}&nbsp;" . (
            ($n == 1) ?
            $this->get_singular_name() :
            $this->get_plural_name()
        ));
    }
//
    function get_primary_key_name() {
        // Return name of the PRIMARY KEY column.
        return "id";
    }

    function get_primary_key_value() {
        // Return value of the PRIMARY KEY member variable.
        $pr_key_name = $this->get_primary_key_name();
        return $this->{$pr_key_name};
    }

    function is_definite() {
        // Return true if PRIMARY KEY member variable is non-zero.
        return ($this->get_primary_key_value() != 0);
    }

    function set_indefinite() {
        // Set PRIMARY KEY member variable to zero.
        $this->set_field_value($this->get_primary_key_name(), 0);
    }
//
    function get_full_table_name() {
        return $this->db->get_full_table_name($this->table_name);
    }

    function set_field_value($field_name, $field_value) {
        $this->{$field_name} = $field_value;
    }

//
    function get_field_names(
        $field_names_to_include = null, $field_names_to_exclude = null
    ) {
        if (is_null($field_names_to_include)) {
            $field_names = array_keys($this->fields);
        } else {
            $field_names = $this->expand_multilingual_field_names(
                $field_names_to_include,
                $this->get_multilingual_field_names($field_names_to_include)
            );
        }

        if (!is_null($field_names_to_exclude)) {
            $expanded_field_names_to_exclude = $this->expand_multilingual_field_names(
                $field_names_to_exclude,
                $this->get_multilingual_field_names($field_names_to_exclude)
            );
            $field_names = array_diff($field_names, $expanded_field_names_to_exclude);
        }

        return $field_names;
    }

    function get_field_names_without_multilingual_expansion(
        $field_names_to_include = null, $field_names_to_exclude = null
    ) {
        $result_field_names = array();
        $field_names = $this->get_field_names($field_names_to_include, $field_names_to_exclude);
        foreach ($field_names as $field_name) {
            if ($this->is_field_multilingual_child($field_name)) {
                continue;
            }
            $result_field_names[] = $field_name;
        }
        return $result_field_names;
    }

    function get_field_names_with_lang_subst($field_names, $lang) {
        $result_field_names = array();
        foreach ($field_names as $field_name) {
            if ($this->is_field_multilingual($field_name)) {
                $result_field_names[] = "{$field_name}_{$lang}";
            } else {
                $result_field_names[] = $field_name;
            }
        }
        return $result_field_names;
    }

    function is_field_multilingual($field_name) {
        return $this->fields[$field_name]["multilingual"];
    }

    function is_field_multilingual_child($field_name) {
        return $this->fields[$field_name]["multilingual_child"];
    }

    function has_multilingual_fields($field_names) {
        foreach ($field_names as $field_name) {
            if ($this->is_field_multilingual($field_name)) {
                return true;
            }
        }
        return false;
    }

    function get_multilingual_field_names($field_names = null) {
        if (is_null($field_names)) {
            $field_names = array_keys($this->fields);
        }
        $multilingual_field_names = array();
        foreach ($field_names as $field_name) {
            if ($this->is_field_multilingual($field_name)) {
                $multilingual_field_names[] = $field_name;
            }
        }
        return $multilingual_field_names;
    }

    function expand_multilingual_field_names($field_names, $multilingual_field_names) {
        $expanded_field_names = $field_names;
        foreach ($multilingual_field_names as $multilingual_field_name) {
            foreach ($this->avail_langs as $lang) {
                $full_multilingual_field_name = "{$multilingual_field_name}_{$lang}";
                unset_array_value_if_exists(
                    $full_multilingual_field_name, $expanded_field_names
                );
                $expanded_field_names[] = $full_multilingual_field_name;
            }
            unset_array_value_if_exists(
                $multilingual_field_name, $expanded_field_names
            );
            $expanded_field_names[] = $multilingual_field_name;
        }
        return $expanded_field_names;
    }

//
    function get_message($name) {
        return $this->app->messages->get_value($name);
    }

    function create_db_object($obj_name) {
        return $this->app->create_db_object($obj_name);
    }
//
    function insert_field($field_info) {
        $field_array_index = count($this->fields);
        $field_name = get_param_value($field_info, "field", null);
        if (is_null($field_name)) {
            die("{$this->table_name}: field name not specified for field {$field_array_index}!");
        }
        $field_name_alias = get_param_value($field_info, "field_alias", null);

        $table_name = get_param_value($field_info, "table", null);
        $table_name_alias = get_param_value($field_info, "table_alias", null);
        
        if (is_null($table_name) || $table_name == $this->table_name) {
            $table_name = $this->table_name;
            if (is_null($table_name_alias) && is_null($field_name_alias)) {
                // case of real or calculated field from current table
                $multilingual = get_param_value($field_info, "multilingual", 0);
                $field_select_expression = get_param_value($field_info, "select", null);
                if (is_null($field_select_expression)) {
                    $field_select_expression = $this->create_field_select_expression(
                        if_null($table_name_alias, $table_name),
                        $field_name,
                        $multilingual
                    );
                    $default_create = 1;
                } else {
                    $default_create = 0;
                }
                if ($multilingual) {
                    $new_field_info = $field_info;
                    $new_field_info["multilingual"] = 0;
                    $new_field_info["multilingual_child"] = 1;

                    foreach ($this->avail_langs as $lang) {
                        $new_field_info["field"] = "{$field_name}_{$lang}";
                        $this->insert_field($new_field_info);
                    }
                }
                $field_name_alias = $field_name;
            } else {
                // case of alias to real field from current table
                if (!isset($this->fields[$field_name])) {
                    die("{$table_name}: cannot find field '{$field_name}'");
                }
                $field_info = $this->fields[$field_name];
                $table_name_alias = if_null($table_name_alias, $table_name);
                $field_select_expression = $this->create_field_select_expression(
                    $table_name_alias,
                    $field_name,
                    $field_info["multilingual"]
                );
                $field_info["multilingual"] = 0;
                $field_info["multilingual_child"] = 0;
                
                $field_name_alias = (is_null($field_name_alias)) ?
                    "{$table_name_alias}_{$field_name}" :
                    "{$table_name_alias}_{$field_name_alias}";
                $default_create = 0;
            }
            $multilingual = get_param_value($field_info, "multilingual", 0);
            $multilingual_child = get_param_value($field_info, "multilingual_child", 0);

            $field_type = get_param_value($field_info, "type", null);
            if (is_null($field_type)) {
                die("{$table_name}: field type for '{$field_name}' not specified!");
            }

            $width = null;
            $prec = null;
            $input = null;
            switch ($field_type) {
            case "primary_key":
                $initial_field_value = 0;
                $width = get_param_value($field_info, "width", 11);
                
                $this->insert_index(array(
                    "type" => "primary_key",
                    "fields" => $field_name,
                ));
                break;
            case "foreign_key":
                $initial_field_value = 0;
                $width = get_param_value($field_info, "width", 11);
                $input = get_param_value($field_info, "input", array());
                $input["type"] = get_param_value($input, "type", "text");
                $input["type_attrs"] = get_param_value($input, "type_attrs", array());
                $input["values"] = get_param_value($input, "values", null);
                
                $this->insert_index(array(
                    "type" => "index",
                    "fields" => $field_name,
                ));
                break;
            case "integer":
                $initial_field_value = get_param_value($field_info, "value", 0);
                $width = get_param_value($field_info, "width", 11);
                break;
            case "double":
                $initial_field_value = get_param_value($field_info, "value", 0.0);
                $width = get_param_value($field_info, "width", 16);
                $prec = get_param_value($field_info, "prec", 2);
                break;
            case "currency":
                $initial_field_value = get_param_value($field_info, "value", 0.0);
                $width = get_param_value($field_info, "width", 10);
                $prec = get_param_value($field_info, "prec", 2);
                break;
            case "boolean":
                $initial_field_value = get_param_value($field_info, "value", 0);
                $width = get_param_value($field_info, "width", 1);
                break;
            case "enum":
                $initial_field_value = get_param_value($field_info, "value", null);
                if (is_null($initial_field_value)) {
                    die(
                        "{$table_name}: initial field 'value' for enum" .
                        " '{$field_name}' not specified!"
                    );
                }
                $input = get_param_value($field_info, "input", null);
                if (is_null($input)) {
                    die(
                        "{$table_name}: 'input' for enum" .
                        " '{$field_name}' not specified!"
                    );
                }
                break;
            case "varchar":
                $width = get_param_value($field_info, "width", 255);
                $initial_field_value = get_param_value($field_info, "value", "");
                $input = get_param_value($field_info, "input", array());
                $input["type"] = get_param_value($input, "type", "text");
                $default_input_attrs = array(
                    "maxlength" => $width,
                );
                $input["type_attrs"] = get_param_value($input, "type_attrs", $default_input_attrs);
                break;
            case "text":
                $initial_field_value = get_param_value($field_info, "value", "");
                $input = get_param_value($field_info, "input", array());
                $input["type"] = get_param_value($input, "type", "textarea");
                $default_input_attrs = array(
                    "cols" => 60,
                    "rows" => 9,
                );
                $input["type_attrs"] = get_param_value($input, "type_attrs", $default_input_attrs);
                break;
            case "blob":
                $initial_field_value = get_param_value($field_info, "value", "");
                break;
            case "datetime":
                $initial_field_value = get_param_value($field_info, "value", "0000-00-00 00:00:00");
                break;
            case "date":
                $initial_field_value = get_param_value($field_info, "value", "0000-00-00");
                break;
            case "time":
                $initial_field_value = get_param_value($field_info, "value", "00:00:00");
                break;
            default:
                die("{$table_name}: unknown type '{$field_type}' for field '{$field_name}'");
            }

            $attr = get_param_value($field_info, "attr", "");
            
            $create = ($multilingual) ?
                false :
                get_param_value($field_info, "create", $default_create);
            $store = ($create && $field_name != $this->get_primary_key_name()) ?
                get_param_value($field_info, "store", true) :
                false;
            $update = ($create && $field_name != $this->get_primary_key_name()) ?
                get_param_value($field_info, "update", true) :
                false;

            $read = get_param_value($field_info, "read", true);
            $print = get_param_value($field_info, "print", true);

            $join_info = get_param_value($field_info, "join", null);
            if (!is_null($join_info)) {
                $this->insert_join($join_info, $field_name);
            }

            $index_type = get_param_value($field_info, "index", null);
            if ($create && !is_null($index_type)) {
                $this->insert_index(array(
                    "type" => $index_type,
                    "fields" => $field_name,
                ));
            }
        } else {
            // insert info for field from another table
            $obj = $this->create_db_object($table_name);
            if (is_null($obj)) {
                die("{$this->table_name}: cannot find joined '{$table_name}'!");
            }

            if (!isset($obj->fields[$field_name])) {
                die(
                    "{$this->table_name}: cannot find field " .
                    "'{$field_name}' in joined '{$table_name}'!"
                );
            }

            $field_info2 = $obj->fields[$field_name];
            $field_type = $field_info2["type"];

            if (is_null($table_name_alias)) {
                $field_select_expression = $field_info2["select"];
                $table_name_alias = $table_name;
            } else {
                $field_select_expression = $this->create_field_select_expression(
                    $table_name_alias,
                    $field_name,
                    $field_info2["multilingual"]
                );
            }
            $field_name_alias = (is_null($field_name_alias)) ?
                "{$table_name_alias}_{$field_name}" :
                "{$table_name_alias}_{$field_name_alias}";

            $initial_field_value = $field_info2["value"];
            $width = get_param_value($field_info2, "width", null);
            $prec = get_param_value($field_info2, "prec", null);

            $attr = $field_info2["attr"];
            
            $create = 0;
            $store = 0;
            $update = 0;

            $read = $field_info2["read"];
            $print = $field_info2["print"];
            $input = $field_info2["input"];

            $multilingual = 0;
            $multilingual_child = 0;
        }
        
        $this->fields[$field_name_alias] = array(
            "type" => $field_type,
            "value" => $initial_field_value,
            "width" => $width,
            "prec" => $prec,

            "select" => $field_select_expression,
            "attr" => $attr,
            
            "create" => $create,
            "store" => $store,
            "update" => $update,

            "read" => $read,
            "print" => $print,
            "input" => $input,

            "multilingual" => $multilingual,
            "multilingual_child" => $multilingual_child,
        );

        $this->set_field_value($field_name_alias, $initial_field_value);
    }

    function create_field_select_expression(
        $table_name_alias,
        $field_name,
        $multilingual
    ) {
        if ($multilingual) {
            $field_select_expression =
                "IF ({$table_name_alias}.{$field_name}_{$this->lang} = '', " .
                "{$table_name_alias}.{$field_name}_{$this->dlang}, " .
                "{$table_name_alias}.{$field_name}_{$this->lang}" .
                ")";
        } else {
            $field_select_expression = "{$table_name_alias}.{$field_name}";
        }
        return $field_select_expression;
    }
//
    function insert_index($index_info) {
        $index_type = $index_info["type"];
        $index_field_names = $index_info["fields"];
        if (!is_array($index_field_names)) {
            $index_field_names = array($index_field_names);
        }
        $index_name = $this->create_index_name($index_type, $index_field_names);
        $this->indexes[$index_name] = array(
            "type" => $index_type,
            "fields" => $index_field_names,
        );
    }

    function create_index_name($index_type, $index_field_names) {
        if ($index_type == "primary_key") {
            return "PRIMARY";
        } else {
            return strtoupper($index_type{0}) . "_" . join("__", $index_field_names);
        }
    }
//
    function insert_join($join_info, $field_name = null) {
        $join_type = $join_info["type"];
        switch ($join_type) {
        case "inner":
            $join_type_str = "INNER";
            break;
        case "left":
            $join_type_str = "LEFT";
            break;
        case "right":
            $join_type_str = "RIGHT";
            break;
        default:
            die("{$this->table_name}: bad join type '{$join_type}'!");
        }
        
        $join_table_name = get_param_value($join_info, "table", null);
        if (is_null($join_table_name)) {
            die("{$this->table_name}: table name not specified in join!");
        }
        $join_table_name_alias = get_param_value($join_info, "table_alias", $join_table_name);

        if (is_null($field_name)) {
            $join_condition = get_param_value($join_info, "condition", null);
            if (is_null($join_condition)) {
                die("{$this->table_name}: condition expression not specified in join!");
            }
        } else {
            $join_table_field_name = get_param_value(
                $join_info, "field", $this->get_primary_key_name()
            );
            $join_condition =
                "{$this->table_name}.{$field_name} = " .
                "{$join_table_name_alias}.{$join_table_field_name}";
        }

        $this->select_from .=
            " {$join_type_str} JOIN {%{$join_table_name}_table%} AS {$join_table_name_alias}" .
            " ON {$join_condition}";
    }
//
    function insert_filter($filter_info) {
        if (!isset($filter_info["value"])) {
            $filter_info["value"] = $this->get_nonset_filter_value($filter_info);
        }
        $this->filters[] = $filter_info;
    }

    function get_nonset_filter_value($filter_info) {
        if (isset($filter_info["input"]["values"]["data"]["nonset_value_caption_pair"])) {
            $nonset_value_caption_pair =
                $filter_info["input"]["values"]["data"]["nonset_value_caption_pair"];
            $nonset_value = get_value_from_value_caption_pair($nonset_value_caption_pair);
        } else {
            $nonset_value = "";
        }
        return $nonset_value;
    }
//
    function insert_select_from($select_from = null) {
        // Store FROM clause for SELECT query.
        $this->select_from = is_null($select_from) ?
            "{%{$this->table_name}_table%} AS {$this->table_name}" :
            $select_from;
    }

//  Db tables management functions (create, update, delete)
    function create_table() {
        $create_table_expression = $this->get_create_table_expression();
        if ($create_table_expression != "") {
            $this->run_query(
                "CREATE TABLE IF NOT EXISTS {%{$this->table_name}_table%} " .
                "({$create_table_expression})"
            );
        }
    }

    function get_create_table_expression() {
        $expressions = array();
        foreach ($this->fields as $field_name => $field_info) {
            if (!$field_info["create"]) {
                continue;
            }
            $expressions[] = $this->get_create_field_expression($field_name);
        }
        foreach (array_keys($this->indexes) as $index_name) {
            $expressions[] = $this->get_create_index_expression($index_name);
        }
        return implode(", ", $expressions);
    }

    function get_create_field_expression($field_name) {
        $field_info = $this->fields[$field_name];
        $field_type = $field_info["type"];

        $type_expression = $this->get_create_field_type_expression($field_name);
        $allow_null = " NOT NULL";
        $default = "";
        
        if ($field_type == "primary_key") {
            $attr = " AUTO_INCREMENT";
        } else {
            $attr = ($field_info["attr"] == "") ?
                "" :
                " {$field_info['attr']}";
        }

        return "{$field_name} {$type_expression}{$allow_null}{$default}{$attr}";
    }

    function get_create_field_type_expression($field_name) {
        $field_info = $this->fields[$field_name];
        $field_type = $field_info["type"];

        switch ($field_type) {
        case "primary_key":
        case "foreign_key":
            $type_expression = "INT({$field_info['width']})";
            break;
        case "integer":
            $type_expression = "INT({$field_info['width']})";
            break;
        case "double":
            $type_expression = "DOUBLE({$field_info['width']},{$field_info['prec']})";
            break;
        case "currency":
            $type_expression = "DECIMAL({$field_info['width']},{$field_info['prec']})";
            break;
        case "boolean":
            $type_expression = "TINYINT({$field_info['width']})";
            break;
        case "enum":
            $enum_value_caption_pairs = $field_info["input"]["values"]["data"]["array"];
            $enum_values = get_values_from_value_caption_pairs($enum_value_caption_pairs);
            $enum_values_expression = "'" . join("','", $enum_values) . "'";
            $type_expression = "ENUM({$enum_values_expression})";
            break;
        case "varchar":
            if ($field_info["width"] <= 3) {
                $type_expression = "CHAR({$field_info['width']})";
            } else {
                $type_expression = "VARCHAR({$field_info['width']})";
            }
            break;
        case "text":
            $type_expression = "LONGTEXT";
            break;
        case "blob":
            $type_expression = "LONGBLOB";
            break;
        case "datetime":
            $type_expression = "DATETIME";
            break;
        case "date":
            $type_expression = "DATE";
            break;
        case "time":
            $type_expression = "TIME";
            break;
        default:
            die("{$this->table_name}: unknown field type '{$field_type}'!");
        }
        
        return $type_expression;
    }

    function get_create_index_expression($index_name) {
        $index_info = $this->indexes[$index_name];
        $index_type = $index_info["type"];
        $index_field_names_str = join(", ", $index_info["fields"]);

        switch ($index_type) {
        case "primary_key":
            $expression = "PRIMARY KEY ({$index_field_names_str})";
            break;
        case "index":
            $expression = "INDEX {$index_name} ({$index_field_names_str})";
            break;
        case "unique":
            $expression = "UNIQUE {$index_name} ({$index_field_names_str})";
            break;
        case "fulltext":
            $expression = "FULLTEXT {$index_name} ({$index_field_names_str})";
            break;
        default:
            die("{$this->table_name}: unknown index type '{$index_type}'!");
        }
        
        return $expression;
    }
//
    function update_table() {
        $update_table_expression = $this->get_update_table_expression();
        if ($update_table_expression != "") {
            $this->run_query(
                "ALTER TABLE {%{$this->table_name}_table%} " .
                "{$update_table_expression}"
            );
        }
    }
        
    function get_update_table_expression() {
        // Managing fields
        $actual_fields_info = $this->db->get_actual_table_fields_info($this->table_name);
        $actual_field_names = array_keys($actual_fields_info);
        $field_names = $this->get_field_names_without_multilingual_expansion();

        $field_names_to_drop = array_diff($actual_field_names, $field_names);

        $last_field_name = "";
        $expressions = array();

        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];

            if ($field_info["multilingual"]) {
                foreach ($this->avail_langs as $lang) {
                    $new_field_name = "{$field_name}_{$lang}";
                    if (in_array($new_field_name, $actual_field_names)) {
                        if ($this->is_field_differ_from_actual_field(
                            $new_field_name, $actual_fields_info[$new_field_name])
                        ) {
                            $create_expression =
                                $this->get_create_field_expression($new_field_name);
                            $expressions[] = "MODIFY COLUMN {$create_expression}";
                        }
                        unset_array_value_if_exists($new_field_name, $field_names_to_drop);
                    } else {
                        if (in_array($field_name, $actual_field_names) && $lang == $this->dlang) {
                            $create_expression =
                                $this->get_create_field_expression($new_field_name);
                            $expressions[] = "CHANGE COLUMN {$field_name} {$create_expression}";
                        } else {
                            $after_field_str = ($last_field_name == "") ?
                                " FIRST" : " AFTER {$last_field_name}";
                            $create_expression =
                                $this->get_create_field_expression($new_field_name);
                            $expressions[] = "ADD COLUMN {$create_expression}{$after_field_str}";
                        }
                    }
                    $last_field_name = $new_field_name;
                }
            } else {
                if (in_array($field_name, $actual_field_names)) {
                    if (!$field_info["create"]) {
                        $field_names_to_drop[] = $field_name;
                    } else {
                        if ($this->is_field_differ_from_actual_field(
                            $field_name, $actual_fields_info[$field_name])
                        ) {
                            $create_expression = $this->get_create_field_expression($field_name);
                            $expressions[] = "MODIFY COLUMN {$create_expression}";
                        }
                        $last_field_name = $field_name;
                    }
                } else {
                    if (!$field_info["create"]) {
                        continue;
                    }
                    $old_field_name = "{$field_name}_{$this->dlang}";
                    if (in_array($old_field_name, $actual_field_names)) {
                        $create_expression =
                            $this->get_create_field_expression($field_name);
                        $expressions[] = "CHANGE COLUMN {$old_field_name} {$create_expression}";
                        unset_array_value_if_exists($old_field_name, $field_names_to_drop);
                    } else {               
                        $after_field_str = ($last_field_name == "") ?
                            " FIRST" : " AFTER {$last_field_name}";
                        $create_expression = $this->get_create_field_expression($field_name);
                        $expressions[] = "ADD COLUMN {$create_expression}{$after_field_str}";
                    }
                    $last_field_name = $field_name;
                }
            }
        }
        foreach ($field_names_to_drop as $field_name) {
            $expressions[] = "DROP COLUMN {$field_name}";
        }

        // Managing indexes
        $actual_indexes_info = $this->db->get_actual_table_indexes_info($this->table_name);
        $actual_index_names = array_keys($actual_indexes_info);
        $index_names = array_keys($this->indexes);

        $index_names_to_add = array_diff($index_names, $actual_index_names);
        $index_names_to_drop = array_diff($actual_index_names, $index_names);

        foreach ($index_names_to_add as $index_name) {
            $index_info = $this->indexes[$index_name];
            $create_expression = $this->get_create_index_expression($index_name);
            $expressions[] = "ADD {$create_expression}";
        }
        foreach ($index_names_to_drop as $index_name) {
            $index_info = $actual_indexes_info[$index_name];
            $index_type = $this->get_index_type_from_index_info(
                $index_name, $index_info
            );
            $create_expression = ($index_type == "primary_key") ?
                "DROP PRIMARY KEY" :
                "DROP INDEX {$index_name}";
            $expressions[] = $create_expression;
        }

        return join(", ", $expressions);
    }

    function is_field_differ_from_actual_field($field_name, $actual_field_info) {
        $field_info = $this->fields[$field_name];

        // Difference in type:
        $type_expression =
            strtoupper($this->get_create_field_type_expression($field_name));
        $actual_type_expression =
            strtoupper($actual_field_info["Type"]);
        if ($type_expression != $actual_type_expression) {
            return true;
        }

        // Difference in null:
        $allow_null = false;
        $actual_allow_null = ($actual_field_info["Null"] == "YES");
        if (
            ($allow_null && !$actual_allow_null) ||
            (!$allow_null && $actual_allow_null)
        ) {
            return true;
        }

//        // Difference in defaults:
//        $default = "";
//        $actual_default = strtoupper($actual_field_info["Default"]);
//        if (is_null($default)) {
//            if ($actual_default != "NULL") {
//                return true;
//            }
//        } else if (strtoupper($default) != $actual_default) {
//            return true;
//        }
        
        return false;
    }

    function get_index_type_from_index_info($index_name, $index_info) {
        if ($index_name == "PRIMARY") {
            return "primary_key";
        } else if ($index_info["Non_unique"] == 1) {
            return "index";
        } else {
            return "unique";
        }
    }

    function delete_table() {
        $this->db->drop_table($this->table_name);
    }
//
    function store(
        $fields_names_to_store = null,
        $fields_names_to_not_store = null
    ) {
        // Store data to database table
        $this->log->write("DbObject", "store()", 3);

        $field_names = $this->get_field_names(
            $fields_names_to_store, $fields_names_to_not_store
        );

        $use_store_flag = (is_null($fields_names_to_store));

        $comma = "";
        $fields_expression = "";
        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];
            if ($use_store_flag && !$field_info["store"]) {
                continue;
            }
            
            $fields_expression .= $comma;
            $comma = ", ";
            $fields_expression .= $this->get_update_field_expression($field_name);
        }

        $this->run_query(
            "INSERT INTO {%{$this->table_name}_table%} SET {$fields_expression}"
        );

        $pr_key_name = $this->get_primary_key_name();
        if ($this->fields[$pr_key_name]["type"] == "primary_key") {
            $this->set_field_value($pr_key_name, $this->db->get_last_autoincrement_id());
        }
    }

    function update(
        $fields_names_to_update = null,
        $fields_names_to_not_update = null
    ) {
        // Update data in database table
        $this->log->write("DbObject", "update()", 3);

        $field_names = $this->get_field_names(
            $fields_names_to_update, $fields_names_to_not_update
        );

        $use_update_flag = (is_null($fields_names_to_update));

        $comma = "";
        $fields_expression = "";
        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];
            if ($use_update_flag && !$field_info["update"]) {
                continue;
            }
            
            $fields_expression .= $comma;
            $comma = ", ";
            $fields_expression .= $this->get_update_field_expression($field_name);
        }
        $where_str = $this->get_default_where_str(false);
        $this->run_query(
            "UPDATE {%{$this->table_name}_table%} SET {$fields_expression} WHERE {$where_str}"
        );
    }

    function get_update_field_expression($field_name) {
        $field_info = $this->fields[$field_name];
        $field_type = $field_info["type"];

        $field_value = $this->{$field_name};
        switch ($field_type) {
        case "primary_key":
        case "foreign_key":
        case "integer":
        case "double":
        case "currency":
        case "boolean":
            $field_value_str = $field_value;
            break;
        case "enum":
        case "varchar":
        case "text":
        case "blob":
        case "datetime":
        case "date":
        case "time":
            $field_value_str = qw($field_value);
            break;
        }
        
        return "{$field_name}={$field_value_str}";
    }

    function save($refetch_after_save = true) {
        $was_definite = $this->is_definite();

        if ($was_definite) {
            $this->update();
        } else {
            $this->store();
        }
        
        if ($refetch_after_save) {
            $this->fetch();
        }

        return $was_definite;
    }
//
    function del() {
        $this->del_where(
            $this->get_default_where_str(false)
        );
    }

    function del_where($where_str) {
        $this->run_query(
            "DELETE FROM {%{$this->table_name}_table%} " .
            "WHERE {$where_str}"
        );
    }

    function del_cascade() {
        $relations = $this->get_restrict_relations();
        foreach ($relations as $relation) {
            list($table_name, $field_name) = $relation;

            $obj = $this->create_db_object($table_name);
            $obj_relations = $obj->get_restrict_relations();

            $this->log->write(
                "DelCascade", "Trying delete from '{$table_name}'", 3
            );

            if (count($obj_relations) == 0) {
                $obj->del_where("{$field_name} = {$this->id}");
            } else {
                $objects_to_delete = $this->fetch_db_objects_list($obj, array(
                    "where" => "{$table_name}.{$field_name} = {$this->id}",
                ));
                foreach ($objects_to_delete as $obj) {
                    $obj->del_cascade();
                }
            }
        }

        return $this->del();
    }

    function get_restrict_relations() {
        return array();
    }

//  CGI functions:
    function read(
        $field_names_to_read = null,
        $field_names_to_not_read = null
    ) {
        // Get data from CGI and store to object values.
        $this->log->write("DbObject", "read()", 3);

        $field_names = $this->get_field_names(
            $field_names_to_read, $field_names_to_not_read
        );

        $use_read_flag = is_null($field_names_to_read);

        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];
            if ($use_read_flag && !$field_info["read"]) {
                continue;
            }

            $param_value = param("{$this->table_name}_{$field_name}");
            $field_type = $field_info["type"];

            switch ($field_type) {
            case "primary_key":
            case "foreign_key":
                $field_value = $this->get_key_field_value($param_value);
                break;
            case "integer":
                $field_value = $this->get_integer_field_value($param_value);
                break;
            case "double":
                $field_value = $this->get_double_field_value($param_value);
                break;
            case "currency":
                $field_value = $this->get_currency_field_value($param_value);
                break;
            case "boolean":
                $field_value = $this->get_boolean_field_value($param_value);
                break;
            case "enum":
                $field_value = $this->get_enum_field_value(
                    $param_value, $field_info["input"]["values"]["data"]["array"]
                );
                break;
            case "varchar":
                if ($this->is_field_multilingual($field_name)) {
                    $default_lang_field_value =
                        $this->{"{$field_name}_{$this->dlang}"};
                    $current_lang_field_value = 
                        $this->{"{$field_name}_{$this->lang}"};
                    $field_value = ($current_lang_field_value == "") ?
                        $default_lang_field_value :
                        $current_lang_field_value;
                } else {
                    $field_value = $this->get_varchar_field_value($param_value);
                }
                break;
            case "text":
                if ($this->is_field_multilingual($field_name)) {
                    $default_lang_field_value =
                        $this->{"{$field_name}_{$this->dlang}"};
                    $current_lang_field_value = 
                        $this->{"{$field_name}_{$this->lang}"};
                    $field_value = ($current_lang_field_value == "") ?
                        $default_lang_field_value :
                        $current_lang_field_value;
                } else {
                    $field_value = $this->get_text_field_value($param_value);
                }
                break;
            case "blob":
                $field_value = $this->get_blob_field_value($param_value);
                break;
            case "datetime":
                $field_value = $this->get_datetime_field_value($param_value);
                break;
            case "date":
                $field_value = $this->get_date_field_value($param_value);
                break;
            case "time":
                $field_value = $this->get_time_field_value($param_value);
                break;
            }

            if (!is_null($field_value)) {
                $this->set_field_value($field_name, $field_value);
            }
        }
    }

    function get_key_field_value($param_value) {
        if (is_null($param_value)) {
            return null;
        }
        return intval($param_value);
    }

    function get_integer_field_value($param_value) {
        if (is_null($param_value)) {
            return null;
        }
        return $this->app->get_php_integer_value($param_value);
    }

    function get_double_field_value($param_value) {
        if (is_null($param_value)) {
            return null;
        }
        return $this->app->get_php_double_value($param_value);
    }

    function get_currency_field_value($param_value) {
        if (is_null($param_value)) {
            return null;
        }
        return $this->app->get_php_double_value($param_value);
    }

    function get_boolean_field_value($param_value) {
        if (intval($param_value) == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    function get_enum_field_value($enum_value, $enum_value_caption_pairs) {
        if (is_null($enum_value)) {
            return null;
        }
        return get_selected_value($enum_value_caption_pairs, $enum_value, $enum_value);
    }

    function get_varchar_field_value($param_value) {
        if (is_null($param_value)) {
            return null;
        }
        return $param_value;
    }

    function get_text_field_value($param_value) {
        if (is_null($param_value)) {
            return null;
        }
        return convert_crlf2lf($param_value);
    }

    function get_blob_field_value($param_value) {
        if (is_null($param_value)) {
            return null;
        }
        return $param_value;
    }

    function get_datetime_field_value($app_datetime) {
        if (is_null($app_datetime)) {
            return null;
        }
        return $this->app->get_db_datetime($app_datetime);
    }

    function get_date_field_value($app_date) {
        if (is_null($app_date)) {
            return null;
        }
        return $this->app->get_db_date($app_date);
    }
    
    function get_time_field_value($app_time) {
        if (is_null($app_time)) {
            return null;
        }
        return $this->app->get_db_time($app_time);
    }
//
    function read_filters() {
        foreach ($this->filters as $i => $filter_info) {
            $filter_name = $filter_info["name"];
            $filter_relation = $filter_info["relation"];
            
            $param_value = param("{$this->table_name}_{$filter_name}_{$filter_relation}");
            if (is_null($param_value)) {
                $this->filters[$i]["value"] = $this->get_nonset_filter_value($filter_info);
            } else {
                $this->filters[$i]["value"] = $param_value;
            }
        }
    }

    function get_filters_query_ex() {
        $wheres = array();
        $havings = array();
        foreach ($this->filters as $filter_info) {
            // If filter has its own field type and select - use them
            // (useful for filters on expanded resultsets)
            // else take it from corresponding field with the same name
            $filter_name = $filter_info["name"];
            if (isset($filter_info["type"])) {
                $field_info = array();
                $field_type = $filter_info["type"];
                $field_select = $filter_info["select"];
            } else {
                $field_info = $this->fields[$filter_name];
                $field_type = $field_info["type"];
                $field_select = $field_info["select"];
            }
            
            $filter_value = $filter_info["value"];
            $nonset_filter_value = $this->get_nonset_filter_value($filter_info);
            if (strval($filter_value) == strval($nonset_filter_value)) {
                continue;
            }

            switch ($field_type) {
            case "primary_key":
            case "foreign_key":
                $db_value = $this->get_key_field_value($filter_value);
                break;
            case "integer":
                $db_value = $this->get_integer_field_value($filter_value);
                break;
            case "double":
                $db_value = $this->get_double_field_value($filter_value);
                break;
            case "currency":
                $db_value = $this->get_currency_field_value($filter_value);
                break;
            case "boolean":
                $db_value = $this->get_boolean_field_value($filter_value);
                break;
            case "enum":
                $default_enum_value_caption_pairs = array(
                    array($filter_value, ""),
                );
                $db_value = $this->get_enum_field_value(
                    $filter_value, $default_enum_value_caption_pairs
                );
                break;
            case "varchar":
                $db_value = $this->get_varchar_field_value($filter_value);
                break;
            case "text":
                $db_value = $this->get_text_field_value($filter_value);
                break;
            case "blob":
                $db_value = $this->get_blob_field_value($filter_value);
                break;
            case "datetime":
                $db_value = $this->get_datetime_field_value($filter_value);
                break;
            case "date":
                $db_value = $this->get_date_field_value($filter_value);
                break;
            case "time":
                $db_value = $this->get_time_field_value($filter_value);
                break;
            }
                
            switch ($filter_info["relation"]) {
            case "less":
                $wheres[] = "{$field_select} <= " . qw($db_value);
                break;
            case "greater":
                $wheres[] = "{$field_select} >= " . qw($db_value);
                break;
            case "equal":
                $wheres[] = "{$field_select} = " . qw($db_value);
                break;
            case "like":
                $wheres[] = "{$field_select} LIKE " . lqw($db_value, "%", "%");
                break;
            case "having_equal":
                $havings[] = "({$field_select} = " . qw($db_value) . ")";
                break;
            case "having_less":
                $havings[] = "({$field_select} <= " . qw($db_value) . ")";
                break;
            case "having_greater":
                $havings[] = "({$field_select} >= " . qw($db_value) . ")";
                break;
            }
        }
        return array(
            "where" => join(" AND ", $wheres),
            "having" => join(" AND ", $havings),
        );
    }

    function get_filters_params() {
        $params = array();
        foreach ($this->filters as $filter_info) {
            $filter_name = $filter_info["name"];
            $filter_relation = $filter_info["relation"];
            $filter_value = $filter_info["value"];
            $nonset_filter_value = $this->get_nonset_filter_value($filter_info);
            if ($filter_value != $nonset_filter_value) {
                $params["{$this->table_name}_{$filter_name}_{$filter_relation}"] = $filter_value;
            }
        }
        return $params;
    }
//
    function read_order_by($default_order_by_fields) {
        $this->order_by = array();

        if (!is_array($default_order_by_fields)) {
            $default_order_by_fields = array($default_order_by_fields);
        }
        
        $order_by_fields = param("order_by");
        if (!is_array($order_by_fields)) {
            if (is_null($order_by_fields) || trim($order_by_fields) == "") {
                $order_by_fields = $default_order_by_fields;
            } else {
                $order_by_fields = array($order_by_fields);
            }
        }

        $field_names = $this->get_field_names();
        foreach ($order_by_fields as $order_by_field) {
            $order_by_field_parts = explode(" ", $order_by_field, 2);
            $order_by_field_name = $order_by_field_parts[0];
            if (!in_array($order_by_field_name, $field_names)) {
                continue;
            }
            $order_by_direction = (isset($order_by_field_parts[1])) ?
                $order_by_field_parts[1] : "asc";
            $this->order_by[] = array(
                "field_name" => $order_by_field_name,
                "direction" => $order_by_direction,
            );
        }
    }

    function get_order_by_params() {
        $order_by_values = array();
        foreach ($this->order_by as $order_by_info) {
            $order_by_values[] = "{$order_by_info['field_name']} {$order_by_info['direction']}";
        }
        return array(
            "order_by" => $order_by_values,
        );
    }

    function get_order_by_query_ex() {
        $order_by_sqls = array();
        foreach ($this->order_by as $order_by_info) {
            $order_by_direction_sql = ($order_by_info["direction"] == "asc") ?
                "ASC" :
                "DESC";
            $order_by_sqls[] = "{$order_by_info['field_name']} {$order_by_direction_sql}";
        }
        return array(
            "order_by" => join(", ", $order_by_sqls),
        );
    }
//
    function init_print_param($params, $param_name, $default_value) {
        $this->print_params[$param_name] =
            get_param_value($params, $param_name, $default_value);
    }

    function init_print_params($params) {
        $this->print_params = array();
        $this->init_print_param($params, "templates_dir", $this->table_name);
        $this->init_print_param($params, "context", "");
        $this->init_print_param($params, "row", array());
        $this->init_print_param($params, "row_number", 0);
        $this->init_print_param($params, "row_parity", 0);
        $this->init_print_param($params, "custom_params", array());
    }

    function assign_values($values) {
        $this->page->assign($values);
    }

    function print_values($params = array()) {
        $this->init_print_params($params);
        $h = array();

        $field_names = $this->get_field_names();

        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];
            if (!$field_info["print"]) {
                continue;
            }
            $field_type = $field_info["type"];
            if ($field_type == "blob") {
                continue;
            }

            $template_var = "{$this->table_name}_{$field_name}";
            $value = $this->{$field_name};

            switch ($field_type) {
            case "primary_key":
                $h1 = $this->print_primary_key_field_value($template_var, $value);
                break;
            case "foreign_key":
                $h1 = $this->print_foreign_key_field_value($template_var, $value);
                break;
            case "integer":
                $h1 = $this->print_integer_field_value($template_var, $value);
                break;
            case "double":
                $h1 = $this->print_double_field_value($template_var, $value, $field_info["prec"]);
                break;
            case "currency":
                $h1 = $this->print_currency_field_value($template_var, $value, $field_info["prec"]);
                break;
            case "boolean":
                $value_captions = array(
                    0 => $this->get_message("no"),
                    1 => $this->get_message("yes"),
                );
                $h1 = $this->print_boolean_field_value($template_var, $value, $value_captions);
                break;
            case "enum":
                $h1 = $this->print_enum_field_value(
                    $template_var, $value, $field_info["input"]["values"]["data"]["array"]
                );
                break;
            case "varchar":
                $h1 = $this->print_varchar_field_value($template_var, $value);
                break;
            case "text":
                $h1 = $this->print_text_field_value($template_var, $value);
                break;
            case "datetime":
                $h1 = $this->print_datetime_field_value($template_var, $value);
                break;
            case "date":
                $h1 = $this->print_date_field_value($template_var, $value);
                break;
            case "time":
                $h1 = $this->print_time_field_value($template_var, $value);
                break;
            }

            $h = array_merge($h, $h1);
        }

        $this->assign_values($h);
        return $h;
    }
//
    function print_primary_key_field_value($template_var, $value) {
        return array(
            "{$template_var}" => $value,
        );
    }

    function print_foreign_key_field_value($template_var, $value) {
        return array(
            "{$template_var}" => $value,
        );
    }

    function print_integer_field_value($template_var, $value) {
        return array(
            "{$template_var}" => $this->app->get_app_integer_value($value),
            "{$template_var}_orig" => $value,
        );
    }

    function print_double_field_value($template_var, $value, $decimals) {
        return array(
            "{$template_var}" => $this->app->get_app_double_value($value, $decimals),
            "{$template_var}_2" => $this->app->get_app_double_value($value, 2),
            "{$template_var}_5" => $this->app->get_app_double_value($value, 5),
            "{$template_var}_orig" => $value,
        );
    }

    function print_currency_field_value($template_var, $value, $decimals) {
        return array(
            "{$template_var}" => $this->app->get_app_currency_value($value, $decimals),
            "{$template_var}_with_sign" =>
                $this->app->get_app_currency_with_sign_value($value, $decimals),
            "{$template_var}_orig" => $value,
        );
    }

    function print_boolean_field_value($template_var, $value, $value_captions) {
        return array(
            "{$template_var}" => $value_captions[$value],
            "{$template_var}_orig" => $value,
        );
    }

    function print_enum_field_value($template_var, $enum_value, $enum_value_caption_pairs) {
        $enum_caption = null;
        foreach ($enum_value_caption_pairs as $enum_value_caption_pair) {
            if ($enum_value == get_value_from_value_caption_pair($enum_value_caption_pair)) {
                $enum_caption = get_caption_from_value_caption_pair($enum_value_caption_pair);
                break;
            }
        }
        if (is_null($enum_caption)) {
            $enum_caption = get_caption_from_value_caption_pair($enum_value_caption_pairs[0]);
        } 
        return array(
            "{$template_var}" => $enum_caption,
            "{$template_var}_orig" => $enum_value,
        );
    }

    function print_varchar_field_value($template_var, $value) {
        $safe_value = get_html_safe_string($value);
        return array(
            "{$template_var}" => $safe_value,
            "{$template_var}_nobr" => str_replace(" ", "&nbsp;", $safe_value),
            "{$template_var}_orig" => $value,
        );
    }

    function print_text_field_value($template_var, $value) {
        $safe_value = get_html_safe_string($value);
        return array(
            "{$template_var}" => $safe_value,
            "{$template_var}_lf2br" => convert_lf2br($safe_value),
            "{$template_var}_nobr" => str_replace(" ", "&nbsp;", $safe_value),
            "{$template_var}_orig" => $value,
        );
    }

    function print_datetime_field_value($template_var, $db_datetime) {
        return array(
            "{$template_var}" => get_html_safe_string($this->app->get_app_datetime($db_datetime)),
            "{$template_var}_orig" => get_html_safe_string($db_datetime),
        );
    }

    function print_date_field_value($template_var, $db_date) {
        return array(
            "{$template_var}" => get_html_safe_string($this->app->get_app_date($db_date)),
            "{$template_var}_orig" => get_html_safe_string($db_date),
        );
    }

    function print_time_field_value($template_var, $db_time) {
        return array(
            "{$template_var}" => get_html_safe_string($this->app->get_app_time($db_time)),
            "{$template_var}_orig" => get_html_safe_string($db_time),
        );
    }
//
    function print_form_values($params = array()) {
        $printed_values = $this->print_values($params);

        $h = array();

        $field_names = $this->get_field_names();
        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];
            if (!$field_info["print"]) {
                continue;
            }
            $field_type = $field_info["type"];
            if ($field_type == "blob") {
                continue;
            }

            $input_type = isset($field_info["input"]["type"]) ?
                $field_info["input"]["type"] :
                "text";
            $input_attrs = isset($field_info["input"]["type_attrs"]) ?
                $field_info["input"]["type_attrs"] :
                array();
            $values_info = isset($field_info["input"]["values"]) ?
                $field_info["input"]["values"] :
                null;

            $template_var = "{$this->table_name}_{$field_name}";
            $value = $this->{$field_name};

            switch ($field_type) {
            case "primary_key":
                $h1 = $this->print_primary_key_field_form_value($template_var, $value);
                break;
            case "foreign_key":
                $h1 = $this->print_foreign_key_field_form_value(
                    $template_var, $value, $input_type, $input_attrs, $values_info
                );
                break;
            case "integer":
                $h1 = $this->print_integer_field_form_value($template_var, $value);
                break;
            case "double":
                $h1 = $this->print_double_field_form_value(
                    $template_var, $value, $field_info["prec"]
                );
                break;
            case "currency":
                $h1 = $this->print_currency_field_form_value(
                    $template_var, $value, $field_info["prec"]
                );
                break;
            case "boolean":
                $h1 = $this->print_boolean_field_form_value($template_var, $value);
                break;
            case "enum":
                $h1 = $this->print_enum_field_form_value(
                    $template_var, $value, $input_type, $input_attrs, $values_info
                );
                break;
            case "varchar":
                if ($this->is_field_multilingual($field_name)) {
                    $h1 = $this->print_multilingual_varchar_field_form_value(
                        $template_var, $value, $h
                    );
                } else {
                    $h1 = $this->print_varchar_field_form_value(
                        $template_var, $value, $input_type, $input_attrs
                    );
                }
                break;
            case "text":
                if ($this->is_field_multilingual($field_name)) {
                    $h1 = $this->print_multilingual_text_field_form_value(
                        $template_var, $value, $h
                    );
                } else {
                    $h1 = $this->print_text_field_form_value(
                        $template_var, $value, $input_type, $input_attrs
                    );
                }
                break;
            case "datetime":
                $h1 = $this->print_datetime_field_form_value($template_var, $value);
                break;
            case "date":
                $h1 = $this->print_date_field_form_value($template_var, $value);
                break;
            case "time":
                $h1 = $this->print_time_field_form_value($template_var, $value);
                break;
            }

            $h = array_merge($h, $h1);
        }

        $this->assign_values($h);
        return $h + $printed_values;
    }
//
    function print_primary_key_field_form_value($template_var, $value) {
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $value),
            "{$template_var}_input" => print_html_input("text", $template_var, $value),
        );
    }

    function print_foreign_key_field_form_value(
        $template_var, $value, $input_type, $input_attrs, $values_info
    ) {
        switch ($input_type) {
        case "text":
            $h1 = $this->print_text_input_form_value($template_var, $value);
            break;
        case "radio":
            $h1 = $this->print_radio_input_form_value($template_var, $value, $values_info);
            break;
        case "select":
            $h1 = $this->print_select_input_form_value(
                $template_var, $value, $input_attrs, $values_info
            );
            break;
        case "main_select":
            $dependency_info = $values_info["dependency"];
            $dependent_field_name = get_param_value($dependency_info, "field", "unknown");
            $dependent_select_name = "{$this->table_name}_{$dependent_field_name}";
            
            $h1 = $this->print_main_select_input_form_value(
                $template_var,
                $value,
                $input_attrs,
                $dependent_select_name,
                $dependency_info,
                $values_info
            );
            break;
        default:
            $h1 = array();
        }

        return array_merge(
            $h1,
            array(
                "{$template_var}_hidden" => print_html_hidden($template_var, $value),
            )
        );
    }

    function print_integer_field_form_value($template_var, $value) {
        $app_integer_value = $this->app->get_app_integer_value($value);
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $app_integer_value),
            "{$template_var}_input" => print_html_input("text", $template_var, $app_integer_value),
        );
    }
    
    function print_double_field_form_value($template_var, $value, $decimals) {
        $app_double_value = $this->app->get_app_double_value($value, $decimals);
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $app_double_value),
            "{$template_var}_input" => print_html_input("text", $template_var, $app_double_value),
        );
    }

    function print_currency_field_form_value($template_var, $value, $decimals) {
        $app_currency_value = $this->app->get_app_currency_value($value, $decimals);
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $app_currency_value),
            "{$template_var}_input" => print_html_input("text", $template_var, $app_currency_value),
        );
    }

    function print_boolean_field_form_value($template_var, $value) {
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $value),
            "{$template_var}_input" => print_html_checkbox($template_var, $value),
        );
    }

    function print_enum_field_form_value(
        $template_var, $enum_value, $input_type, $input_attrs, $values_info
    ) {
        switch ($input_type) {
        case "radio":
            $h1 = $this->print_radio_input_form_value($template_var, $enum_value, $values_info);
            break;
        case "select":
            $h1 = $this->print_select_input_form_value(
                $template_var, $enum_value, $input_attrs, $values_info
            );
            break;
        default:
            $h1 = array();
        }

        return array_merge(
            $h1,
            array(
                "{$template_var}_hidden" => print_html_hidden($template_var, $enum_value),
            )
        );
    }

    function print_varchar_field_form_value(
        $template_var, $str_value, $input_type, $input_attrs
    ) {
        $h = array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $str_value),
        );

        switch ($input_type) {
        case "text":
        case "password":
            $h["{$template_var}_input"] =
                print_html_input($input_type, $template_var, $str_value, $input_attrs);
            break;
        }

        return $h;
    }

    function print_multilingual_varchar_field_form_value(
        $template_var, $str_value, $printed_form_values
    ) {
        return $this->print_multilingual_field_form_value(
            $template_var, $str_value, $printed_form_values
        );
    }

    function print_text_field_form_value(
        $template_var, $str_value, $input_type, $input_attrs
    ) {
        $h = array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $str_value),
        );

        switch ($input_type) {
        case "textarea":
            $h["{$template_var}_input"] =
                print_html_textarea($template_var, $str_value, $input_attrs);
            break;
        }

        return $h;
    }

    function print_multilingual_text_field_form_value(
        $template_var, $str_value, $printed_form_values
    ) {
        return $this->print_multilingual_field_form_value(
            $template_var, $str_value, $printed_form_values
        );
    }

    function print_multilingual_field_form_value(
        $template_var, $str_value, $printed_form_values
    ) {
        $lang_inputs_with_caption = array();
        foreach ($this->avail_langs as $lang_resource) {
            $lang_str = $this->get_message($lang_resource);
            $lang_template_var = "";
            $lang_input = $printed_form_values["{$template_var}_{$lang_resource}_input"];

            $lang_inputs_with_caption[] =
                "<tr>\n" .
                "<th>{$lang_str}:</th>\n" .
                "<td>{$lang_input}</td>\n" .
                "</tr>\n";
        }
        return array(
            "{$template_var}_hidden" =>
                print_html_hidden($template_var, $str_value),
            "{$template_var}_input" => 
                "<table>\n" .
                join("", $lang_inputs_with_caption) .
                "</table>\n",
        );
    }

    function print_datetime_field_form_value($template_var, $db_datetime) {
        $app_datetime = $this->app->get_app_datetime($db_datetime);
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $app_datetime),
            "{$template_var}_input" => print_html_input("text", $template_var, $app_datetime),
        );
    }

    function print_date_field_form_value($template_var, $db_date) {
        $app_date = $this->app->get_app_date($db_date);
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $app_date),
            "{$template_var}_input" => print_html_input("text", $template_var, $app_date),
        );
    }

    function print_time_field_form_value($template_var, $db_time) {
        $app_time = $this->app->get_app_time($db_time);
        return array(
            "{$template_var}_hidden" => print_html_hidden($template_var, $app_time),
            "{$template_var}_input" => print_html_input("text", $template_var, $app_time),
        );
    }
//
    function print_text_input_form_value($template_var, $value) {
        return array(
            "{$template_var}_input" => print_html_input("text", $template_var, $value),
        );
    }

    function print_checkbox_input_form_value($template_var, $value) {
        return array(
            "{$template_var}_input" => print_html_checkbox($template_var, $value),
        );
    }

    function print_radio_input_form_value(
        $template_var, $value, $values_info, $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info);
        $value_caption_pairs = $this->expand_value_caption_pairs_with_begin_end(
            $value_caption_pairs, $values_info
        );

        if (!is_null($alt_values_info)) {
            $values_info = $alt_values_info;
        }
        $value_caption_pairs = $this->expand_value_caption_pairs_with_nonset(
            $value_caption_pairs, $values_values_info
        );
                
        return array(
            "{$template_var}_input" => print_html_radio_group(
                $template_var, $value_caption_pairs, $value
            ),
        );
    }

    function print_select_input_form_value(
        $template_var, $value, $input_attrs, $values_info, $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info);
        $value_caption_pairs = $this->expand_value_caption_pairs_with_begin_end(
            $value_caption_pairs, $values_info
        );
        
        if (!is_null($alt_values_info)) {
            $values_info = $alt_values_info;
        }
        $value_caption_pairs = $this->expand_value_caption_pairs_with_nonset(
            $value_caption_pairs, $values_info
        );

        return array(
            "{$template_var}_input" => print_html_select(
                $template_var, $value_caption_pairs, $value, $input_attrs
            ),
        );
    }

    function print_main_select_input_form_value(
        $template_var,
        $value,
        $input_attrs,
        $dependent_select_name,
        $dependency_info,
        $values_info,
        $alt_values_info = null
    ) {
        $value_caption_pairs = $this->get_value_caption_pairs($values_info);
        $value_caption_pairs = $this->expand_value_caption_pairs_with_begin_end(
            $value_caption_pairs, $values_info
        );
        
        if (!is_null($alt_values_info)) {
            $values_info = $alt_values_info;
        }
        $value_caption_pairs = $this->expand_value_caption_pairs_with_nonset(
            $value_caption_pairs, $values_info
        );

        $form_name = get_param_value($dependency_info, "form_name", "form");
        $dependent_field_name = get_param_value($dependency_info, "field", "unknown");
        $dependency_key_field_name = get_param_value($dependency_info, "key_field_name", "unknown");
        $dependency_query_ex = get_param_value($dependency_info, "query_ex", array());

        $dependent_values_info = $this->fields[$dependent_field_name]["input"]["values"];
        $main_select_name = $template_var;

        $input_attrs["onchange"] =
            "updateDependentSelect(this, '{$dependent_select_name}'); " .
            "if (this.form.{$dependent_select_name}.onchange) { " .
                "this.form.{$dependent_select_name}.onchange(); " .
            "}";

        $dependency_array = $this->get_select_dependency_array(
            $value_caption_pairs,
            $dependent_values_info,
            $dependency_key_field_name,
            $dependency_query_ex
        );

        $dependency_js = create_select_dependency_js(
            $form_name,
            $main_select_name,
            $dependent_select_name,
            $dependency_array
        );

        return array(
            "{$template_var}_input" => print_html_select(
                $template_var, $value_caption_pairs, $value, $input_attrs
            ),
            "{$template_var}_dependency_js" => $dependency_js,
        );
    }

    function get_value_caption_pairs($values_info) {
        switch ($values_info["source"]) {
        case "array":
            $value_caption_pairs = $values_info["data"]["array"];
            break;
        case "db_object":
            $data_info = $values_info["data"];
            
            $obj_name = get_param_value($data_info, "obj_name", null);
            $values_field_name = get_param_value($data_info, "values_field_name", "id");
            $captions_field_name = get_param_value($data_info, "captions_field_name", "name");
            $query_ex = get_param_value($data_info, "query_ex", array());

            $value_caption_pairs = $this->get_value_caption_pairs_from_db_object(
                $obj_name, $values_field_name, $captions_field_name, $query_ex
            );
            break;
        case "function":
            $function_name = $values_info["data"]["function"];
            $value_caption_pairs = $this->{$function_name}();
            break;
        }
        return $value_caption_pairs;
    }

    function get_value_caption_pairs_from_db_object(
        $obj_name, $values_field_name, $captions_field_name, $query_ex
    ) {
        $objects = $this->fetch_db_objects_list($obj_name, $query_ex);
        $value_caption_pairs = array();
        foreach ($objects as $obj) {
            $value_caption_pairs[] = array(
                $obj->{$values_field_name},
                $obj->{$captions_field_name},
            );
        }
        return $value_caption_pairs;
    }

    function expand_value_caption_pairs_with_begin_end($value_caption_pairs, $values_info) {
        if (isset($values_info["data"]["begin_value_caption_pairs"])) {
            $value_caption_pairs = array_merge(
                $values_info["data"]["begin_value_caption_pairs"],
                $value_caption_pairs
            );
        }
        if (isset($values_info["data"]["end_value_caption_pairs"])) {
            $value_caption_pairs = array_merge(
                $value_caption_pairs,
                $values_info["data"]["end_value_caption_pairs"]
            );
        }
        return $value_caption_pairs;
    }

    function expand_value_caption_pairs_with_nonset($value_caption_pairs, $values_info) {
        if (isset($values_info["data"]["nonset_value_caption_pair"])) {
            $value_caption_pairs = array_merge(
                array($values_info["data"]["nonset_value_caption_pair"]),
                $value_caption_pairs
            );
        }
        return $value_caption_pairs;
    }
    
    function get_select_dependency_array(
        $main_select_value_caption_pairs,
        $dependent_values_info,
        $dependency_key_field_name,
        $dependency_query_ex
    ) {
        $dependent_query_ex = get_param_value(
            $dependent_values_info["data"], "query_ex", array()
        );

        $query_ex = new SelectQuery();
        $query_ex->expand($dependent_query_ex);
        $query_ex->expand($dependency_query_ex);

        $main_select_values = get_values_from_value_caption_pairs(
            $main_select_value_caption_pairs
        );
        
        if (isset($dependent_values_info["data"]["nonset_value_caption_pair"])) {
            $dependent_select_nonset_value = get_value_from_value_caption_pair(
                $dependent_values_info["data"]["nonset_value_caption_pair"]
            );
        } else {
            $dependent_select_nonset_value = 0;
        }

        $dependency_array = array();
        foreach ($main_select_values as $main_select_value) {
            if ($main_select_value == $dependent_select_nonset_value) {
                $dependency_array[] = array($dependent_select_nonset_value);
                continue;
            }

            $final_query_ex = $query_ex;
            $final_query_ex->expand(array(
                "where" => "{$dependency_key_field_name} = {$main_select_value}",
            ));
            $dependent_values_info["data"]["query_ex"] = $final_query_ex;
            
            $dependent_select_value_caption_pairs =
                $this->get_value_caption_pairs($dependent_values_info);
            $dependent_select_value_caption_pairs =
                $this->expand_value_caption_pairs_with_begin_end(
                    $dependent_select_value_caption_pairs, $dependent_values_info
                );
            $dependent_select_value_caption_pairs =
                $this->expand_value_caption_pairs_with_nonset(
                    $dependent_select_value_caption_pairs, $dependent_values_info
                );

            $dependency_array[] = get_values_from_value_caption_pairs(
                $dependent_select_value_caption_pairs
            );
        }
        return $dependency_array;
    }
//
    function print_filter_form_values() {
        $h = array();

        foreach ($this->filters as $filter_info) {
            $filter_name = $filter_info["name"];
            $filter_relation = $filter_info["relation"];
            
            $template_var = "{$this->table_name}_{$filter_name}_{$filter_relation}";
            $h1 = $this->print_filter_input_form_values($template_var, $filter_info);
            
            $h = array_merge($h, $h1);
        }

        $this->assign_values($h);
        return $h;
    }

    function print_filter_input_form_values($template_var, $filter_info) {
        $filter_name = $filter_info["name"];
        $filter_relation = $filter_info["relation"];
        $filter_value = $filter_info["value"];
        $filter_input_type = isset($filter_info["input"]["type"]) ?
            $filter_info["input"]["type"] :
            "text";
        $filter_input_attrs = isset($filter_info["input"]["type_attrs"]) ?
            $filter_info["input"]["type_attrs"] :
            array();

        $values_info = null;
        $alt_values_info = null;
        if (isset($filter_info["input"]["values"])) {
            $values_info = $filter_info["input"]["values"];
            if ($values_info["source"] == "field") {
                $alt_values_info = $values_info;
                $values_info = $this->fields[$filter_name]["input"]["values"];
            }
        }

        switch ($filter_input_type) {
        case "text":
            $h1 = $this->print_text_input_form_value($template_var, $filter_value);
            break;
        case "checkbox":
            $h1 = $this->print_checkbox_input_form_value($template_var, $filter_value);
            break;
        case "radio":
            $h1 = $this->print_radio_input_form_value(
                $template_var, $filter_value, $values_info, $alt_values_info
            );
            break;
        case "select":
            $h1 = $this->print_select_input_form_value(
                $template_var,
                $filter_value,
                $filter_input_attrs,
                $values_info,
                $alt_values_info
            );
            break;
        case "listbox":
            $filter_input_attrs["multiple"] = null;
            $h1 = $this->print_select_input_form_value(
                $template_var,
                $filter_value,
                $filter_input_attrs,
                $values_info,
                $alt_values_info
            );
            break;
        case "main_select":
            $dependency_info = $values_info["dependency"];
            $dependent_field_name = get_param_value($dependency_info, "field", "unknown");
            $dependent_select_name =
                "{$this->table_name}_{$dependent_field_name}_{$filter_relation}";

            $h1 = $this->print_main_select_input_form_value(
                $template_var,
                $filter_value,
                $filter_input_attrs,
                $dependent_select_name,
                $dependency_info,
                $values_info,
                $alt_values_info
            );
            break;
        default:
            $h1 = array();
        }

        return array_merge(
            $h1,
            array(
                "{$template_var}_hidden" => print_html_hidden($template_var, $filter_value),
            )
        );
    }

//  Objects validation for store/update and validation helpers
    function validate($old_obj = null, $field_names_to_validate = null) {
        return array();
    }

    function validate_not_empty_field($field_name) {
        if ($this->is_field_multilingual($field_name)) {
            foreach ($this->avail_langs as $lang) {
                $field_names = $this->get_field_names_with_lang_subst(
                    array($field_name), $lang
                );
                if (!$this->validate_not_empty_field($field_names[0])) {
                    return false;
                }
            }
            return true;
        } else {
            return (is_value_not_empty($this->{$field_name}));
        }
    }

    function validate_email_field($field_name) {
        return is_value_email($this->{$field_name});
    }

    function validate_unique_field($field_name, $old_obj) {
        if (is_array($field_name)) {
            $field_names = $field_name;
        } else {
            $field_names = array($field_name);
        }

        $was_definite = is_null($old_obj) ? false : $old_obj->is_definite();
        $multilingual_field_names = $this->get_multilingual_field_names($field_names);

        if (count($multilingual_field_names) == 0) {
            if ($was_definite) {
                $should_check_db_table = false;
                foreach ($field_names as $field_name) {
                    if ($this->{$field_name} != $old_obj->{$field_name}) {
                        $should_check_db_table = true;
                        break;
                    }
                }
            } else {
                $should_check_db_table = true;
            }

            if ($should_check_db_table) {
                if ($this->are_field_values_exist($field_names)) {
                    return false;
                }            
            }
        } else {
            foreach ($this->avail_langs as $lang) {
                $field_names_to_validate = $this->get_field_names_with_lang_subst(
                    $field_names, $lang
                );
                if (!$this->validate_unique_field(
                    $field_names_to_validate, $old_obj
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    function are_field_values_exist($field_names) {
        $query = new SelectQuery(array(
            "from" => "{%{$this->table_name}_table%} AS {$this->table_name}",
            "where" => $this->create_where_expression($field_names),
        ));
        return $this->db->get_select_query_num_rows($query);
    }

    function create_where_expression($field_names) {
        $where_expressions = array();
        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];
            $where_expressions[] =
                "{$field_info['select']} = " . qw($this->{$field_name});
        }
        return join(" AND ", $where_expressions);
    }

    function should_validate_field($field_name, $field_names_to_validate) {
        return (
            is_null($field_names_to_validate) ||
            in_array($field_name, $field_names_to_validate)
        );
    }
//
    // check reference integrity
    function check_restrict_relations_before_delete() {
        $table_link_counters = array();
        foreach ($this->get_restrict_relations() as $relation) {
            list($dep_table_name, $dep_table_field) = $relation;

            $query = new SelectQuery(array(
                "select" => "id",
                "from"   => "{%{$dep_table_name}_table%}",
                "where"  => "{$dep_table_field} = {$this->id}",
            ));

            $n = $this->db->get_select_query_num_rows($query);
            if ($n > 0) {
                if (isset($table_link_counters[$dep_table_name])) {
                    $table_link_counters[$dep_table_name] += $n;
                } else {
                    $table_link_counters[$dep_table_name] = $n;
                }
            }
        }

        $messages = array();
        if (count($table_link_counters) != 0) {
            $dep_objs_data = array();
            foreach ($table_link_counters as $dep_table_name => $n) {
                $dep_obj = $this->create_db_object($dep_table_name);
                $dep_objs_data[] = $dep_obj->get_quantity_str($n);
            }
            $messages[] = new ErrorStatusMsg(
                "cannot_delete_record_because_it_has_links",
                array(
                    "main_obj_name" => $this->get_singular_name(),
                    "dep_objs_data" => join(", ", $dep_objs_data),
                )    
            );
        }

        return $messages;
    }
//
    function get_select_query(
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        $field_names = $this->get_field_names(
            $field_names_to_select, $field_names_to_not_select
        );

        $comma = "";
        $fields_expression = "";
        foreach ($field_names as $field_name) {
            $field_info = $this->fields[$field_name];

            $fields_expression .= $comma;
            $comma = ", ";
            $fields_expression .= "{$field_info['select']} AS {$field_name}";
        }

        return new SelectQuery(array(
            "select" => $fields_expression,
            "from" => $this->select_from,
        ));
    }

    function get_expanded_select_query(
        $query_ex = array(),
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        $query = $this->get_select_query(
            $field_names_to_select, $field_names_to_not_select
        );
        $query->expand($query_ex);
        return $query;
    }

    function run_expanded_select_query(
        $query_ex = array(),
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        return $this->run_select_query(
            $this->get_expanded_select_query(
                $query_ex, $field_names_to_select, $field_names_to_not_select
            )
        );
    }

    function run_query($query_str) {
        return $this->db->run_query($query_str);    
    }

    function run_select_query($query) {
        return $this->db->run_select_query($query);
    }
//
    function get_default_where_str($use_table_alias = true) {
        // Return default WHERE condition for fetching one object.
        $pr_key_name  = $this->get_primary_key_name();
        $pr_key_value = $this->get_primary_key_value();
        $pr_key_value_str = qw($pr_key_value);

        if ($use_table_alias) {
            return "{$this->table_name}.{$pr_key_name} = {$pr_key_value_str}";
        } else {
            return "{$pr_key_name} = {$pr_key_value_str}";
        }
    }
//
    function fetch(
        $where_str = null,
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        // Fetch data from database table using given WHERE condition
        // Return true if ONE record found
        if (is_null($where_str)) {
            $where_str = $this->get_default_where_str();
        }

        $query_ex = array(
            "where" => $where_str,
        );

        $res = $this->run_expanded_select_query(
            $query_ex,
            $field_names_to_select,
            $field_names_to_not_select
        );

        if ($res->get_num_rows() != 1) {  // record not found
            return false;
        }

        $row = $res->fetch();
        $this->fetch_row($row);

        return true;
    }

    function fetch_row($row) {
        // Fetch data from query result row.
        foreach ($row as $field_name => $field_value) {
            $this->set_field_value($field_name, $field_value);
        }
    }

    function fetch_db_object(
        $obj_name,
        $id,
        $where_str = "1",
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        return $this->app->fetch_db_object(
            $obj_name,
            $id,
            $where_str,
            $field_names_to_select,
            $field_names_to_not_select
        );
    }

    function fetch_db_objects_list($obj_name, $query_ex) {
        return $this->app->fetch_db_objects_list($obj_name, $query_ex);
    }

//  Uploaded image helpers
    function fetch_image($image_id_field_name = "image_id") {
        $image_id = $this->{$image_id_field_name};
        return $this->fetch_db_object("image", $image_id);
    }

    function fetch_image_without_content($image_id_field_name = "image_id") {
        $image_id = $this->{$image_id_field_name};
        return $this->fetch_db_object("image", $image_id, "1", null, array("content"));
    }

    function print_image($image_id_field_name = "image_id", $template_var = "_image") {
        $image = $this->fetch_image_without_content($image_id_field_name);
        
        $h = $image->print_values();
        
        $filename = ($image->is_definite()) ?
            "{$template_var}.html" : "{$template_var}_empty.html";
        
        $templates_dir = $this->print_params["templates_dir"];
        $this->page->parse_file_new_if_exists(
            "{$templates_dir}/{$filename}", "{$this->table_name}{$template_var}"
        );

        return $h;
    }

    function del_image($image_id_field_name = "image_id") {
        $image_id = $this->{$image_id_field_name};
        if ($image_id != 0) {
            $image = $this->create_db_object("image");
            $image->del_where("id = {$image_id}");
        }
    }

//    function validate_image_upload($input_name = "image_file") {
//        $messages = array();
//
//        if (!Image::was_uploaded($input_name)) {
//            return $messages;
//        }
//
//        switch ($_FILES[$input_name]["error"]) {
//            case UPLOAD_ERR_OK:
//                break;
//            case UPLOAD_ERR_INI_SIZE:
//            case UPLOAD_ERR_FORM_SIZE:
//                $messages[] = new ErrorStatusMsg("too_big_filesize");
//                break;
//            case UPLOAD_ERR_PARTIAL:
//                $messages[] = new ErrorStatusMsg("file_was_not_uploaded_completely");
//                break;
//        }
//
//        return $messages;
//    }
//
//    function process_image_upload(
//        $image_id_field_name = "image_id", $input_name = "image_file"
//    ) {
//        if (Image::was_uploaded($input_name)) {
//            $image = $this->fetch_image_without_content($image_id_field_name);
//            $image->read_uploaded_content($input_name);
//            
//            if ($image->is_definite()) {
//                $image->update();
//            } else {
//                $image->store();
//                $this->set_field_value($image_id_field_name, $image->id);
//            }
//        }
//    }
}

?>