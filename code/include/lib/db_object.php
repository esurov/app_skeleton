<?php

// Base class for all MySQL table based classes
class DbObject extends AppObject {

    var $db; // Db (from app)

    var $_table_name;  // Db table name without db specific prefix

    var $_fields;
    var $_orig_field_values;
    var $_indexes;
    
    var $_select_from; // FROM clause for SELECT query

    var $_filters;
    var $_order_by;

    var $_templates_dir;
    var $_template_var_prefix;
    var $_input_name_prefix;
    var $_input_name_suffix;

    // Context name for print_values(), print_form_values()
    var $_context;

    // Custom params which depend on current context
    var $_custom_params;

    function _init($params) {
        parent::_init($params);

        $this->_table_name = get_param_value($params, "table_name", null);
        if (is_null($this->_table_name)) {
            $this->process_fatal_error_required_param_not_found("table_name");
        }

        $this->_fields = array();
        $this->_orig_field_values = array();
        $this->_indexes = array();

        $this->insert_select_from();

        $this->_filters = array();
    }
//
    function set_app() {
        parent::set_app();

        $this->db =& $this->app->db;
    }
//
    function get_table_class_name() {
        return $this->get_class_name_without_suffix();
    }

    function get_full_table_name() {
        return $this->db->get_full_table_name($this->_table_name);
    }

    function get_plural_name() {
        return $this->get_lang_str($this->get_plural_lang_resource());
    }

    function get_singular_name() {
        return $this->get_lang_str($this->get_singular_lang_resource());
    }

    function get_plural_lang_resource() {
        return "{$this->_table_name}s";
    }

    function get_singular_lang_resource() {
        return "{$this->_table_name}";
    }

    function get_quantity_str($n) {
        $quantity_str = ($n == 1) ? $this->get_singular_name() : $this->get_plural_name();
        return("{$n} {$quantity_str}");
    }
//
    // Return name of the PRIMARY KEY column
    function get_primary_key_name() {
        return "id";
    }

    // Return value of the PRIMARY KEY column
    function get_primary_key_value() {
        $pr_key_name = $this->get_primary_key_name();
        return $this->{$pr_key_name};
    }

    // Return original value of the PRIMARY KEY column
    function get_orig_primary_key_value() {
        $pr_key_name = $this->get_primary_key_name();
        return $this->get_orig_field_value($pr_key_name);
    }

    // Return true if PRIMARY KEY member variable is non-zero
    function is_definite() {
        return ($this->get_primary_key_value() != 0);
    }

    // Return true if original PRIMARY KEY member variable was non-zero
    function was_definite() {
        return ($this->get_orig_primary_key_value() != 0);
    }
//
    function &get_field_info($field_name) {
        return $this->_fields[$field_name];
    }

    function set_field_value($field_name, $field_value) {
        $this->{$field_name} = $field_value;
    }

    function set_field_values_from_row($row) {
        // Set values to DbObject fields from query result row
        foreach ($row as $field_name => $field_value) {
            $this->set_field_value($field_name, $field_value);
        }
    }

    function reset_field_values() {
        $field_names = array_keys($this->_fields);
        foreach ($field_names as $field_name) {
            $this->reset_field_value($field_name);
        }    
    }

    function reset_field_value($field_name) {
        $initial_field_value = $this->_fields[$field_name]["value"]; 
        $this->set_field_value($field_name, $initial_field_value);
        $this->set_orig_field_value($field_name, $initial_field_value);
    }

    function set_orig_field_value($field_name, $field_value) {
        $this->_orig_field_values[$field_name] = $field_value;
    }

    function get_orig_field_value($field_name) {
        return $this->_orig_field_values[$field_name];
    }

    function sync_orig_field_values() {
        $field_names = array_keys($this->_fields);
        foreach ($field_names as $field_name) {
            $this->sync_orig_field_value($field_name);
        }    
    }

    function sync_orig_field_value($field_name) {
        $this->set_orig_field_value($field_name, $this->{$field_name});
    }

    function was_field_value_changed($field_name) {
        return ($this->{$field_name} != $this->get_orig_field_value($field_name));
    }
//
    function get_field_names(
        $field_names_to_include = null,
        $field_names_to_exclude = null
    ) {
        if (is_null($field_names_to_include)) {
            $field_names = array_keys($this->_fields);
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
        $field_names_to_include = null,
        $field_names_to_exclude = null
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

    function is_field_exist($field_name) {
        return isset($this->_fields[$field_name]);
    }

    function is_field_multilingual($field_name, $field_info = null) {
        if (is_null($field_info)) {
            $field_info = $this->_fields[$field_name];
        }
        return get_param_value($field_info, "multilingual", 0);
    }

    function is_field_multilingual_child($field_name) {
        return $this->_fields[$field_name]["multilingual_child"];
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
            $field_names = array_keys($this->_fields);
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
            $full_field_names = $this->get_full_field_names_for_multilingual_field_name(
                $multilingual_field_name
            );
            foreach ($full_field_names as $full_field_name) {
                unset_array_value_if_exists($full_field_name, $expanded_field_names);
                $expanded_field_names[] = $full_field_name;
            }
            unset_array_value_if_exists($multilingual_field_name, $expanded_field_names);
            $expanded_field_names[] = $multilingual_field_name;
        }
        return $expanded_field_names;
    }

    function get_full_field_names_for_multilingual_field_name($multilingual_field_name) {
        $full_field_names = array();
        foreach ($this->app->avail_langs as $lang) {
            $full_field_names[] = "{$multilingual_field_name}_{$lang}";
        }
        return $full_field_names;
    }
//
    function insert_field($field_info) {
        $field_array_index = count($this->_fields);
        $field_name = get_param_value($field_info, "field", null);
        if (is_null($field_name)) {
            $this->process_fatal_error(
                "{$this->_table_name}: Field name param 'field' not specified " .
                "for field with index {$field_array_index}!"
            );
        }
        $field_name_sql_alias = get_param_value($field_info, "field_sql_alias", null);

        $obj_class_name = get_param_value($field_info, "obj_class", null);
        $table_name_sql_alias = get_param_value($field_info, "table_sql_alias", null);
        
        if (is_null($obj_class_name) || ($obj_class_name == $this->get_table_class_name())) {
            if (is_null($table_name_sql_alias) && is_null($field_name_sql_alias)) {
                // Case of real or calculated field from current DbObject
                $multilingual = get_param_value($field_info, "multilingual", 0);
                $field_select_expression = get_param_value($field_info, "select", null);
                if (is_null($field_select_expression)) {
                    $field_select_expression = $this->create_field_select_expression(
                        is_null($table_name_sql_alias) ? $this->_table_name : $table_name_sql_alias,
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

                    foreach ($this->app->avail_langs as $lang) {
                        $new_field_info["field"] = "{$field_name}_{$lang}";
                        $this->insert_field($new_field_info);
                    }
                }
                $field_name_sql_alias = $field_name;
            } else {
                // Case of alias to real field from current DbObject
                if (!isset($this->_fields[$field_name])) {
                    $this->process_fatal_error(
                        "Cannot find field '{$field_name}'"
                    );
                }
                $field_info = $this->_fields[$field_name];
                if (is_null($table_name_sql_alias)) {
                    $table_name_sql_alias = $this->_table_name;
                }
                if (is_null($field_name_sql_alias)) {
                    $field_name_sql_alias = $field_name;
                }
                $field_select_expression = $this->create_field_select_expression(
                    $table_name_sql_alias,
                    $field_name,
                    $field_info["multilingual"]
                );
                $field_info["multilingual"] = 0;
                $field_info["multilingual_child"] = 0;
                $default_create = 0;
            }
            $multilingual = get_param_value($field_info, "multilingual", 0);
            $multilingual_child = get_param_value($field_info, "multilingual_child", 0);

            $field_type = get_param_value($field_info, "type", null);
            if (is_null($field_type)) {
                $this->process_fatal_error(
                    "Field type for '{$field_name}' not specified!"
                );
            }

            $default_index_type = null;
            $width = null;
            $prec = null;
            $input = get_param_value($field_info, "input", array());
            $input["type_attrs"] = get_param_value($input, "type_attrs", array());
            switch ($field_type) {
            case "primary_key":
                $initial_field_value = 0;
                $width = get_param_value($field_info, "width", 11);
                $default_index_type = "primary_key";
                break;
            case "foreign_key":
                $initial_field_value = 0;
                $width = get_param_value($field_info, "width", 11);
                $input["type"] = get_param_value($input, "type", "text");
                $input["values"] = get_param_value($input, "values", null);
                $default_index_type = "index";
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
                    $this->process_fatal_error(
                        "Initial field 'value' for enum '{$field_name}' not specified!"
                    );
                }
                if (is_null(get_param_value($field_info, "input", null))) {
                    $this->process_fatal_error(
                        "'input' for enum '{$field_name}' not specified!"
                    );
                }
                break;
            case "varchar":
                $width = get_param_value($field_info, "width", 255);
                $initial_field_value = get_param_value($field_info, "value", "");
                $input["type"] = get_param_value($input, "type", "text");
                $input["type_attrs"]["maxlength"] = get_param_value(
                    $input["type_attrs"],
                    "maxlength",
                    $width
                );
                break;
            case "text":
                $initial_field_value = get_param_value($field_info, "value", "");
                $input["type"] = get_param_value($input, "type", "textarea");
                $input["type_attrs"]["cols"] = get_param_value($input["type_attrs"], "cols", 60);
                $input["type_attrs"]["rows"] = get_param_value($input["type_attrs"], "rows", 9);
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
                $this->process_fatal_error(
                    "Unknown field type '{$field_type}' for field '{$field_name}'"
                );
            }

            $attr = get_param_value($field_info, "attr", "");
            
            $create = ($multilingual) ?
                0 :
                get_param_value($field_info, "create", $default_create);
            $store = (!$create || $field_type == "primary_key") ?
                0 :
                get_param_value($field_info, "store", 1);
            $update = (!$create || $field_type == "primary_key") ?
                0 :
                get_param_value($field_info, "update", 1);

            $read = ($field_type == "primary_key") ?
                0 :
                get_param_value($field_info, "read", 1);

            $join_info = get_param_value($field_info, "join", null);
            if (!is_null($join_info)) {
                $this->insert_join($join_info, $field_name);
            }

            $index_type = get_param_value($field_info, "index", $default_index_type);
            if ($create && !is_null($index_type)) {
                $this->insert_index(array(
                    "type" => $index_type,
                    "fields" => $field_name,
                ));
            }
        } else {
            // Insert info for field from another DbObject
            $obj =& $this->create_db_object($obj_class_name);
            if (!isset($obj->_fields[$field_name])) {
                $this->process_fatal_error(
                    "Cannot find field '{$field_name}' in joined '{$obj_class_name}'!"
                );
            }

            $field_info2 = $obj->_fields[$field_name];
            $field_type = $field_info2["type"];

            if (is_null($table_name_sql_alias)) {
                $field_select_expression = $field_info2["select"];
                $table_name_sql_alias = $obj->_table_name;
            } else {
                $field_select_expression = $this->create_field_select_expression(
                    $table_name_sql_alias,
                    $field_name,
                    $field_info2["multilingual"]
                );
            }
            $field_name_sql_alias = (is_null($field_name_sql_alias)) ?
                "{$table_name_sql_alias}_{$field_name}" :
                "{$table_name_sql_alias}_{$field_name_sql_alias}";

            $initial_field_value = $field_info2["value"];
            $width = get_param_value($field_info2, "width", null);
            $prec = get_param_value($field_info2, "prec", null);

            $attr = $field_info2["attr"];
            
            $create = 0;
            $store = 0;
            $update = 0;

            $read = get_param_value($field_info, "read", 0);
            $input = get_param_value($field_info, "input", $field_info2["input"]);

            $multilingual = 0;
            $multilingual_child = 0;
        }
        
        $this->_fields[$field_name_sql_alias] = array(
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
            "input" => $input,

            "multilingual" => $multilingual,
            "multilingual_child" => $multilingual_child,
        );

        $this->reset_field_value($field_name_sql_alias);
    }

    function create_field_select_expression(
        $table_name_sql_alias,
        $field_name,
        $multilingual
    ) {
        if ($multilingual) {
            $field_select_expression =
                "IF (" .
                    "{$table_name_sql_alias}.{$field_name}_{$this->app->lang} = '', " .
                    "{$table_name_sql_alias}.{$field_name}_{$this->app->dlang}, " .
                    "{$table_name_sql_alias}.{$field_name}_{$this->app->lang}" .
                ")";
        } else {
            $field_select_expression = "{$table_name_sql_alias}.{$field_name}";
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
        $this->_indexes[$index_name] = array(
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
    function insert_join($join_info, $join_field_name = null) {
        $field_name_error_str = (is_null($join_field_name)) ?
            "" :
            " for field '{$join_field_name}'";

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
            $this->process_fatal_error(
                "Bad join type '{$join_type}'{$field_name_error_str}!"
            );
        }
        
        $joined_obj_class_name = get_param_value($join_info, "obj_class", null);
        if (is_null($joined_obj_class_name)) {
            $this->process_fatal_error(
                "Joined DbObject class name 'obj_class' not specified " .
                "in join info{$field_name_error_str}!"
            );
        }
        if ($joined_obj_class_name == $this->get_table_class_name()) {
            $joined_obj =& $this;
        } else {
            $joined_obj =& $this->create_db_object($joined_obj_class_name);
        }
        $joined_table_name_sql_alias = get_param_value(
            $join_info,
            "table_sql_alias",
            $joined_obj->_table_name
        );

        if (is_null($join_field_name)) {
            // Join SQL condition expression should be created manually and passed here
            $join_sql_condition = get_param_value($join_info, "condition", null);
            if (is_null($join_sql_condition)) {
                $this->process_fatal_error(
                    "Join SQL condition expression 'condition' not specified in join info!"
                );
            }
        } else {
            // Join SQL condition expression is created here automatically
            // (join is done with current DbObject)
            $joined_table_field_name = get_param_value(
                $join_info,
                "field",
                $this->get_primary_key_name()
            );
            $join_sql_condition =
                "{$this->_table_name}.{$join_field_name} = " .
                "{$joined_table_name_sql_alias}.{$joined_table_field_name}";
        }

        $this->_select_from .=
            " {$join_type_str} JOIN {%{$joined_obj->_table_name}_table%} " .
                "AS {$joined_table_name_sql_alias} " .
                "ON {$join_sql_condition}";
    }
//
    function insert_filter($filter_info) {
        if (!isset($filter_info["value"])) {
            $filter_info["value"] = $this->get_nonset_filter_value($filter_info);
        }
        $this->_filters[] = $filter_info;
    }

    function get_filter_by_name($filter_name) {
        foreach ($this->_filters as $filter_info) {
            if ($filter_info["name"] == $filter_name) {
                return $filter_info;
            }
        }
        return null;
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
        $this->_select_from = is_null($select_from) ?
            "{%{$this->_table_name}_table%} AS {$this->_table_name}" :
            $select_from;
    }

    // Db table maintenance functions (create, update, delete)
    function create_table($fake_run, &$run_info) {
        $create_table_expression = $this->get_create_table_expression();
        if ($create_table_expression != "") {
            $create_table_query = $this->db->get_create_table_query(
                $this->_table_name,
                $create_table_expression
            );
            if ($fake_run) {
                $run_info["table_names_to_create"][] = $this->_table_name;
                $run_info["create_table_queries"][$this->_table_name] =
                    $this->db->subst_table_prefix($create_table_query);
            } else {
                $this->run_query($create_table_query);
            }
        }
    }

    function get_create_table_expression() {
        $expressions = array();
        foreach ($this->_fields as $field_name => $field_info) {
            if (!$field_info["create"]) {
                continue;
            }
            $expressions[] = $this->get_create_field_expression($field_name);
        }
        foreach (array_keys($this->_indexes) as $index_name) {
            $expressions[] = $this->get_create_index_expression($index_name);
        }
        return join(",\n    ", $expressions);
    }

    function get_create_field_expression($field_name) {
        $field_info = $this->_fields[$field_name];
        $field_type = $field_info["type"];

        $type_expression = $this->get_create_field_type_expression($field_name);
        $allow_null = " NOT NULL";
        $default = "";
        
        if ($field_type == "primary_key") {
            $attr = " AUTO_INCREMENT";
        } else {
            $attr = ($field_info["attr"] == "") ? "" : " {$field_info['attr']}";
        }

        return "{$field_name} {$type_expression}{$allow_null}{$default}{$attr}";
    }

    function get_create_field_type_expression($field_name) {
        $field_info = $this->_fields[$field_name];
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
            $this->process_fatal_error(
                "{$this->_table_name}: Unknown field type '{$field_type}'!"
            );
        }
        
        return $type_expression;
    }

    function get_create_index_expression($index_name) {
        $index_info = $this->_indexes[$index_name];
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
            $this->process_fatal_error(
                "{$this->_table_name}: Unknown index type '{$index_type}'!"
            );
        }
        
        return $expression;
    }
//
    function update_table($fake_run, &$run_info) {
        $update_table_expression = $this->get_update_table_expression();
        if ($update_table_expression != "") {
            $update_table_query = $this->db->get_update_table_query(
                $this->_table_name,
                $update_table_expression
            );
            if ($fake_run) {
                $run_info["table_names_to_update"][] = $this->_table_name;
                $run_info["update_table_queries"][$this->_table_name] =
                    $this->db->subst_table_prefix($update_table_query);
            } else {
                $this->run_query($update_table_query);
            }
        }
    }
        
    function get_update_table_expression() {
        // Managing fields
        $actual_fields_info = $this->db->get_actual_table_fields_info($this->_table_name);
        $actual_field_names = array_keys($actual_fields_info);
        $field_names = $this->get_field_names_without_multilingual_expansion();

        $field_names_to_drop = array_diff($actual_field_names, $field_names);

        $last_field_name = "";
        $expressions = array();

        foreach ($field_names as $field_name) {
            $field_info = $this->_fields[$field_name];

            if ($field_info["multilingual"]) {
                foreach ($this->app->avail_langs as $lang) {
                    $new_field_name = "{$field_name}_{$lang}";
                    if (in_array($new_field_name, $actual_field_names)) {
                        if ($this->is_field_differ_from_actual_field(
                            $new_field_name,
                            $actual_fields_info[$new_field_name])
                        ) {
                            $create_expression = $this->get_create_field_expression($new_field_name);
                            $expressions[] = "MODIFY COLUMN {$create_expression}";
                        }
                        unset_array_value_if_exists($new_field_name, $field_names_to_drop);
                    } else {
                        if (
                            in_array($field_name, $actual_field_names) &&
                            $lang == $this->app->dlang
                        ) {
                            $create_expression = $this->get_create_field_expression($new_field_name);
                            $expressions[] = "CHANGE COLUMN {$field_name} {$create_expression}";
                        } else {
                            $after_field_str = ($last_field_name == "") ?
                                " FIRST" :
                                " AFTER {$last_field_name}";
                            $create_expression = $this->get_create_field_expression($new_field_name);
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
                            $field_name,
                            $actual_fields_info[$field_name]
                        )) {
                            $create_expression = $this->get_create_field_expression($field_name);
                            $expressions[] = "MODIFY COLUMN {$create_expression}";
                        }
                        $last_field_name = $field_name;
                    }
                } else {
                    if (!$field_info["create"]) {
                        continue;
                    }
                    $old_field_name = "{$field_name}_{$this->app->dlang}";
                    if (in_array($old_field_name, $actual_field_names)) {
                        $create_expression = $this->get_create_field_expression($field_name);
                        $expressions[] = "CHANGE COLUMN {$old_field_name} {$create_expression}";
                        unset_array_value_if_exists($old_field_name, $field_names_to_drop);
                    } else {               
                        $after_field_str = ($last_field_name == "") ?
                            " FIRST" :
                            " AFTER {$last_field_name}";
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
        $actual_indexes_info = $this->db->get_actual_table_indexes_info($this->_table_name);
        $actual_index_names = array_keys($actual_indexes_info);
        $index_names = array_keys($this->_indexes);

        $index_names_to_add = array_diff($index_names, $actual_index_names);
        $index_names_to_drop = array_diff($actual_index_names, $index_names);

        foreach ($index_names_to_add as $index_name) {
            $index_info = $this->_indexes[$index_name];
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

        return join(",\n    ", $expressions);
    }

    function is_field_differ_from_actual_field($field_name, $actual_field_info) {
        $field_info = $this->_fields[$field_name];

        // Difference in type:
        $type_expression = strtoupper($this->get_create_field_type_expression($field_name));
        $actual_type_expression = strtoupper($actual_field_info["Type"]);
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

        //Difference in attributes:
        if ($field_info["type"] == "primary_key") {
            $attr = "AUTO_INCREMENT";
        } else {
            $attr = ($field_info["attr"] == "") ? "" : strtoupper($field_info["attr"]);
        }
        $actual_attr = strtoupper($actual_field_info["Extra"]);
        if ($attr != $actual_attr) {
            return true;
        }

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
        $this->run_query($this->db->get_drop_table_query($this->_table_name));
    }
//
    // Should be redefined in child class
    // Used for both store() and update()
    function get_save_field_names_info($context, $context_params) {
        return array();
    }

    // Store field values to DB table
    // Wrapper for SQL INSERT query
    function store($context = null, $context_params = array()) {
        $field_names_info = $this->get_save_field_names_info($context, $context_params);
        $this->_store_values(
            get_param_value($field_names_info, "field_names_to_store", null),
            get_param_value($field_names_info, "field_names_to_not_store", null)
        );
    }

    function _store_values(
        $field_names_to_store = null,
        $field_names_to_not_store = null
    ) {
        $this->write_log(
            "_store_values()",
            DL_INFO
        );

        $field_names = $this->get_field_names(
            $field_names_to_store,
            $field_names_to_not_store
        );

        $use_store_flag = (is_null($field_names_to_store));

        $delimiter_str = "";
        $fields_expression = "";
        foreach ($field_names as $field_name) {
            $field_info = $this->_fields[$field_name];
            if ($use_store_flag && !$field_info["store"]) {
                continue;
            }
            
            $fields_expression .= $delimiter_str . $this->get_update_field_expression($field_name);
            $delimiter_str = ",\n    ";
        }

        $this->run_query($this->db->get_insert_query($this->_table_name, $fields_expression));

        $pr_key_name = $this->get_primary_key_name();
        if ($this->_fields[$pr_key_name]["type"] == "primary_key") {
            $this->set_field_value($pr_key_name, $this->db->get_last_autoincrement_id());
        }
        $this->sync_orig_field_values();
    }

    // Update field values in DB table
    // Wrapper for SQL UPDATE query
    function update($context = null, $context_params = array()) {
        $field_names_info = $this->get_save_field_names_info($context, $context_params);
        $this->_update_values(
            get_param_value($field_names_info, "field_names_to_update", null),
            get_param_value($field_names_info, "field_names_to_not_update", null)
        );
    }

    function _update_values(
        $field_names_to_update = null,
        $field_names_to_not_update = null
    ) {
        // Update data in database table
        $this->write_log(
            "_update_values()",
            DL_INFO
        );

        $field_names = $this->get_field_names(
            $field_names_to_update,
            $field_names_to_not_update
        );

        $use_update_flag = (is_null($field_names_to_update));

        $delimiter_str = "";
        $fields_expression = "";
        foreach ($field_names as $field_name) {
            $field_info = $this->_fields[$field_name];
            if ($use_update_flag) {
                if (!$field_info["update"] || !$this->was_field_value_changed($field_name)) {
                    continue;
                }
            }

            $fields_expression .= $delimiter_str . $this->get_update_field_expression($field_name);
            $delimiter_str = ",\n    ";
        }

        if ($fields_expression != "") {
            $this->run_query(
                $this->db->get_update_query(
                    $this->_table_name,
                    $fields_expression,
                    $this->get_default_where_str(false)
                )
            );
            $this->sync_orig_field_values();
        }
    }

    function get_update_field_expression($field_name) {
        $field_info = $this->_fields[$field_name];
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
        
        return "{$field_name} = {$field_value_str}";
    }

    // Save (store/update) DbObject to DB table and refetch it
    // if fields from related DbObjects need to be updated
    // returns true if object was updated and false if stored
    function save($refetch_after_save = false, $context = null, $context_params = array()) {
        $should_update = $this->was_definite();

        if ($should_update) {
            $this->update($context, $context_params);
        } else {
            $this->store($context, $context_params);
        }
        
        if ($refetch_after_save) {
            $this->fetch();
        }

        return $should_update;
    }
//
    // Delete single DbObject record from DB table
    // Wrapper for SQL DELETE query
    function del() {
        $this->del_where($this->get_default_where_str(false));
        $this->reset_field_values();
    }

    // Delete many DbObject records directly from DB table with specified criteria
    function del_where($where_str) {
        $this->run_query($this->db->get_delete_query($this->_table_name, $where_str));
    }

    // Delete single DbObject record from DB table with all related DbObjects
    function del_cascade() {
        $relations = $this->get_restrict_relations();
        foreach ($relations as $relation) {
            list($dep_obj_class_name, $key_field_name) = $relation;
            $dep_obj =& $this->create_db_object($dep_obj_class_name);

            $this->write_log(
                "del_cascade(): Trying to delete from '{$dep_obj->_table_name}'",
                DL_INFO
            );

            if (count($dep_obj->get_restrict_relations()) == 0) {
                $dep_obj->del_where("{$key_field_name} = {$this->id}");
            } else {
                $objects_to_delete = $this->fetch_db_objects_list(
                    $dep_obj,
                    array(
                        "where" => "{$dep_obj->_table_name}.{$key_field_name} = {$this->id}",
                    )
                );
                foreach ($objects_to_delete as $object_to_delete) {
                    $object_to_delete->del_cascade();
                }
            }
        }
        return $this->del();
    }

    function get_restrict_relations() {
        return array();
    }
//
    // CGI functions

    // Should be redefined in child class
    function get_read_field_names_info($context, $context_params) {
        return array();
    }
    
    // Read field values from CGI
    function read($context = null, $context_params = array()) {
        $field_names_info = $this->get_read_field_names_info($context, $context_params);
        $this->_read_values(
            get_param_value($field_names_info, "field_names_to_read", null),
            get_param_value($field_names_info, "field_names_to_not_read", null),
            get_param_value($field_names_info, "input_name_prefix", null)
        );
    }

    function _read_values(
        $field_names_to_read = null,
        $field_names_to_not_read = null,
        $input_name_prefix = null
    ) {
        // Get data from CGI and store to object values
        $this->write_log(
            "_read_values()",
            DL_INFO
        );

        if (is_null($input_name_prefix)) {
            $input_name_prefix = $this->_table_name;
        }

        $field_names = $this->get_field_names($field_names_to_read, $field_names_to_not_read);

        $use_read_flag = is_null($field_names_to_read);

        // Logger optimization
        $should_write_log = ($this->get_log_debug_level() >= DL_EXTRA_DEBUG);
        $log_message_text = "";
        
        foreach ($field_names as $field_name) {
            $field_info = $this->_fields[$field_name];
            $input_name = "{$input_name_prefix}_{$field_name}";
            if ($use_read_flag && ($field_info["read"] == 0)) {
                $field_read_skipped = true;
            } else {
                $type = $field_info["type"];
                if (
                    ($type == "varchar" || $type == "text") &&
                    $this->is_field_multilingual($field_name)
                ) {
                    $default_lang_field_value = $this->{"{$field_name}_{$this->app->dlang}"};
                    $current_lang_field_value = $this->{"{$field_name}_{$this->app->lang}"};
                    $field_value = ($current_lang_field_value == "") ?
                        $default_lang_field_value :
                        $current_lang_field_value;
                } else {
                    // Let read function to take type params from current field info
                    $type_params = $field_info;

                    $field_value = $this->app->read_value_by_type(
                        $input_name,
                        $type,
                        $type_params
                    );
                    
                    if (!is_null($field_value)) {
                        $this->set_field_value($field_name, $field_value);
                    }
                    
                    $field_read_skipped = false;
                }
            }

            if ($should_write_log) {
                if ($field_read_skipped) {
                    $status_str = "Field skipped ('read' == 0)!";
                } else {
                    if (is_null($field_value)) {
                        $status_str = "Field skipped (no CGI param found)!";
                    } else {
                        $status_str =
                            "Read value is: " .
                            qw(get_shortened_string($field_value, 300));
                    }
                }
                $log_message_text .=
                    "  Reading field '{$field_name}' from CGI param '{$input_name}'. " .
                    "{$status_str}\n";
            }
        }

        if ($should_write_log) {
            $this->write_log(
                "Read details:\n" .
                "{$log_message_text}",
                DL_EXTRA_DEBUG
            );
        }
    }
//
    function read_filters($input_name_prefix = null) {
        foreach (array_keys($this->_filters) as $i) {
            $this->read_filter($this->_filters[$i], $input_name_prefix);
        }
    }

    function read_filter(
        &$filter_info,
        $input_name_prefix = null
    ) {
        $filter_name = $filter_info["name"];
        $filter_relation = $filter_info["relation"];
        
        if (is_null($input_name_prefix)) {
            $input_name_prefix = $this->_table_name;
        }

        $param_name = "{$input_name_prefix}_{$filter_name}_{$filter_relation}";
        $param_sent_name = "__sent_{$param_name}";
        $param_value = param($param_name);

        // Hack: For boolean values represented by HTML checkbox
        if (!is_null(param($param_sent_name))) {
            $param_value = $this->app->read_boolean_value($param_name);
        }
        if (!is_null($param_value)) {
            $filter_info["value"] = $param_value;
        }
    }

    function get_filters_query_ex() {
        $filters_query_ex = new SelectQueryEx();
        foreach ($this->_filters as $filter_info) {
            $filters_query_ex->expand($this->get_filter_query_ex($filter_info));
        }
        return $filters_query_ex;
    }

    function get_filter_query_ex($filter_info) {
        $filter_value = $filter_info["value"];
        $nonset_filter_value = $this->get_nonset_filter_value($filter_info);
        if ((string) $filter_value == (string) $nonset_filter_value) {
            $filter_query_ex = array();
        } else {
            $filter_name = $filter_info["name"];
            $filter_relation = $filter_info["relation"];

            // If filter has no 'type' (field type) take it from field with filter's name
            if (!isset($filter_info["type"])) {
                $filter_info = $this->_fields[$filter_name];
            }
            $type = $filter_info["type"];

            $db_value = $this->app->get_db_value_by_type($filter_value, $type);
            
            // If filter has no 'select' (field select expression) take it
            // from field with filter's name
            if (!isset($filter_info["select"])) {
                $filter_info = $this->_fields[$filter_name];
            }
            $field_select_expression = $filter_info["select"];
            
            $filter_query_ex = $this->get_filter_query_ex_by_relation(
                $filter_relation,
                $db_value,
                $field_select_expression
            );
        }

        return $filter_query_ex;
    }

    function get_filter_query_ex_by_relation(
        $filter_relation,
        $db_value,
        $field_select_expression
    ) {
        $filter_query_ex = array();

        switch ($filter_relation) {
        case "equal":
        case "less":
        case "less_equal":
        case "greater":
        case "greater_equal":
            $relation_sign = $this->get_filter_relation_sign_by_relation($filter_relation);
            $db_value_quoted = qw($db_value);
            if (is_array($field_select_expression)) {
                $field_select_expressions = $field_select_expression;
            } else {
                $field_select_expressions = array($field_select_expression);
            }
            $subwheres_or = array();
            foreach ($field_select_expressions as $field_select_expression) {
                $subwheres_or[] =
                    "{$field_select_expression} {$relation_sign} {$db_value_quoted}";
            }
            $filter_query_ex["where"] = "(" . join(" OR ", $subwheres_or) . ")";
            break;
        case "like":
        case "like_many":
            if ($filter_relation == "like") {
                $keywords = array($db_value);
            } else {
                $keywords = preg_split('/[\s,]+/', $db_value);
            }
            if (count($keywords) != 0) {
                if (is_array($field_select_expression)) {
                    $field_select_expressions = $field_select_expression;
                } else {
                    $field_select_expressions = array($field_select_expression);
                }
                $subwheres_and = array();
                foreach ($keywords as $keyword) {
                    $db_value_lquoted = lqw($keyword, "%", "%");
                    $subwheres_or = array();
                    foreach ($field_select_expressions as $field_select_expression) {
                        $subwheres_or[] = "{$field_select_expression} LIKE {$db_value_lquoted}";
                }
                    $subwheres_and[] = "(" . join(" OR ", $subwheres_or) . ")";
                }
                $filter_query_ex["where"] = "(" . join(" AND ", $subwheres_and) . ")";
            }
            break;
        case "having_equal":
        case "having_less":
        case "having_less_equal":
        case "having_greater":
        case "having_greater_equal":
            $relation_sign = $this->get_filter_relation_sign_by_relation($filter_relation);
            $db_value_quoted = qw($db_value);
            $filter_query_ex["having"] =
                "({$field_select_expression} {$relation_sign} {$db_value_quoted})";
            break;
        }

        return $filter_query_ex;
    }

    function get_filter_relation_sign_by_relation($filter_relation) {
        switch ($filter_relation) {
        case "equal":
        case "having_equal":
            $filter_relation_sign = "=";
            break;
        case "less":
        case "having_less":
            $filter_relation_sign = "<";
            break;
        case "less_equal":
        case "having_less_equal":
            $filter_relation_sign = "<=";
            break;
        case "greater":
        case "having_greater":
            $filter_relation_sign = ">";
            break;
        case "greater_equal":
        case "having_greater_equal":
            $filter_relation_sign = ">=";
            break;
        default:
            $filter_relation_sign = "";
        }
        return $filter_relation_sign;
    }

    function get_filters_suburl_params($input_name_prefix = null) {
        if (is_null($input_name_prefix)) {
            $input_name_prefix = $this->_table_name;
        }

        $suburl_params = array();
        foreach ($this->_filters as $filter_info) {
            $filter_name = $filter_info["name"];
            $filter_relation = $filter_info["relation"];
            $filter_value = $filter_info["value"];
            $suburl_param_name = "{$input_name_prefix}_{$filter_name}_{$filter_relation}";
            $suburl_params[$suburl_param_name] = $filter_value;
        }
        
        return $suburl_params;
    }
//
    function read_order_by($default_order_by_fields) {
        $order_by_fields = param_array("order_by");

        // Check if all requested fields really exist else fallback to default_order_by fields
        $use_default_order_by_fields = false;
        if (count($order_by_fields) == 0) {
            $use_default_order_by_fields = true;
        } else {
            foreach ($order_by_fields as $order_by_field) {
                $order_by_field_parts = explode(" ", $order_by_field, 2);
                $order_by_field_name = $order_by_field_parts[0];
                if (!$this->is_field_exist($order_by_field_name)) {
                    $use_default_order_by_fields = true;
                    $this->write_log(
                        "read_order_by(): Field '{$order_by_field_name}' " .
                        "specified in 'order_by' CGI parameter not found in " .
                        "'{$this->_table_name}', trying to use " .
                        "'default_order_by' fields instead!",
                        DL_WARNING
                    );
                    break;
                }
            }
        }
        if ($use_default_order_by_fields) {
            if (!is_array($default_order_by_fields)) {
                $default_order_by_fields = array($default_order_by_fields);
            }
            
            // Check if all default_order_by fields really exist
            foreach ($default_order_by_fields as $default_order_by_field) {
                $default_order_by_field_parts = explode(" ", $default_order_by_field, 2);
                $default_order_by_field_name = $default_order_by_field_parts[0];
                if (!$this->is_field_exist($default_order_by_field_name)) {
                    $this->process_fatal_error(
                        "read_order_by(): Field '{$default_order_by_field_name}' " .
                        "specified in 'default_order_by' not found in '{$this->_table_name}'!"
                    );
                }
            }
            $order_by_fields = $default_order_by_fields;
        }

        $this->_order_by = array();
        foreach ($order_by_fields as $order_by_field) {
            $order_by_field_parts = explode(" ", $order_by_field, 2);
            $order_by_field_name = $order_by_field_parts[0];
            $order_by_direction = "asc";
            if (isset($order_by_field_parts[1])) {
                $order_by_direction = strtolower($order_by_field_parts[1]);
                if (!in_array($order_by_direction, array("asc", "desc"))) {
                    $order_by_direction = "asc";
                }
            }
            $this->_order_by[] = array(
                "field_name" => $order_by_field_name,
                "direction" => $order_by_direction,
            );
        }
    }

    function get_order_by_suburl_params() {
        $suburl_param_values = array();
        foreach ($this->_order_by as $order_by_info) {
            $suburl_param_values[] = "{$order_by_info['field_name']} {$order_by_info['direction']}";
        }
        if (count($suburl_param_values) == 1) {
            $suburl_param_values = $suburl_param_values[0];
        }
        return array(
            "order_by" => $suburl_param_values,
        );
    }

    function get_order_by_query_ex() {
        $order_by_query_ex = new SelectQueryEx();
        foreach ($this->_order_by as $order_by_info) {
            $order_by_query_ex->expand($this->get_order_by_field_query_ex($order_by_info));
        }
        return $order_by_query_ex;
    }

    function get_order_by_field_query_ex($order_by_info) {
        $order_by_direction_sql = strtoupper($order_by_info["direction"]);
        return array(
            "order_by" => "{$order_by_info['field_name']} {$order_by_direction_sql}",
        );
    }
//
    // Template printing functions
    function _init_print_param($params, $param_name, $default_value) {
        $this->{"_{$param_name}"} = get_param_value($params, $param_name, $default_value);
    }

    function print_values($params = array()) {
        $this->_init_print_param($params, "templates_dir", "");
        $this->_init_print_param($params, "template_var_prefix", $this->_table_name);
        $this->_init_print_param($params, "context", "");
        $this->_init_print_param($params, "custom_params", array());

        $field_names = $this->get_field_names();
        foreach ($field_names as $field_name) {
            $this->print_value(
                "{$this->_template_var_prefix}.{$field_name}",
                $this->{$field_name},
                $this->_fields[$field_name]
            );
        }
    }

    function print_value(
        $template_var,
        $db_value,
        $field_info
    ) {
        $field_type = $field_info["type"];
        if ($field_type == "blob") {
            return;
        }
        $values_info = (isset($field_info["input"]["values"])) ?
            $field_info["input"]["values"] :
            array();

        switch ($field_type) {
        case "primary_key":
            $this->app->print_primary_key_value(
                $template_var,
                $db_value
            );
            break;
        case "foreign_key":
            $this->app->print_foreign_key_value(
                $template_var,
                $db_value
            );
            break;
        case "integer":
            $input_info = get_param_value($field_info, "input", array());
            $this->app->print_integer_value(
                $template_var,
                $db_value,
                get_param_value($input_info, "nonset_value_caption_pair", null)
            );
            break;
        case "double":
            $input_info = get_param_value($field_info, "input", array());
            $this->app->print_double_value(
                $template_var,
                $db_value,
                $field_info["prec"],
                get_param_value($input_info, "nonset_value_caption_pair", null)
            );
            break;
        case "currency":
            $values_data_info = get_param_value($values_info, "data", array());
            $this->app->print_currency_value(
                $template_var,
                $db_value,
                $field_info["prec"],
                get_param_value($values_data_info, "sign", null),
                get_param_value($values_data_info, "sign_at_start", null),
                get_param_value($values_data_info, "nonset_value_caption_pair", null)
            );
            break;
        case "boolean":
            $values_data_info = get_param_value($values_info, "data", array());
            $this->app->print_boolean_value(
                $template_var,
                $db_value,
                get_param_value($values_data_info, "value_caption_pairs", null)
            );
            break;
        case "enum":
            $this->app->print_enum_value(
                $template_var,
                $db_value,
                $values_info["data"]["array"]
            );
            break;
        case "varchar":
            $this->app->print_varchar_value(
                $template_var,
                $db_value
            );
            break;
        case "text":
            $this->app->print_text_value(
                $template_var,
                $db_value
            );
            break;
        case "datetime":
            $this->app->print_datetime_value(
                $template_var,
                $db_value
            );
            break;
        case "date":
            $this->app->print_date_value(
                $template_var,
                $db_value
            );
            break;
        case "time":
            $this->app->print_time_value(
                $template_var,
                $db_value
            );
            break;
        }
    }
//
    function print_form_values($params = array()) {
        $this->print_values($params);

        $this->_init_print_param($params, "input_name_prefix", $this->_table_name);
        $this->_init_print_param($params, "input_name_suffix", "");

        $field_names = $this->get_field_names();
        foreach ($field_names as $field_name) {
            $this->print_form_value(
                "{$this->_template_var_prefix}.{$field_name}",
                "{$this->_input_name_prefix}_{$field_name}{$this->_input_name_suffix}",
                $this->{$field_name},
                $this->_fields[$field_name]
            );
        }

        $this->print_client_validation_js();
    }

    function print_form_value(
        $template_var,
        $input_name,
        $db_value,
        $field_info
    ) {
        $field_type = $field_info["type"];
        if ($field_type == "blob") {
            return;
        }

        $input_type = isset($field_info["input"]["type"]) ?
            $field_info["input"]["type"] :
            "text";
        $input_attrs = isset($field_info["input"]["type_attrs"]) ?
            $field_info["input"]["type_attrs"] :
            array();
        $values_info = isset($field_info["input"]["values"]) ?
            $field_info["input"]["values"] :
            array();

        switch ($field_type) {
        case "primary_key":
            $this->app->print_primary_key_form_value(
                $template_var,
                $input_name,
                $db_value
            );
            break;
        case "foreign_key":
            $dependency_info = get_param_value($values_info, "dependency", null);
            if (is_null($dependency_info)) {
                $input_type_params = null;
            } else {
                $dependent_field_name = $dependency_info["field"];
                $input_type_params = array(
                    "dependent_select_name" =>
                        "{$this->_input_name_prefix}_" .
                        "{$dependent_field_name}{$this->_input_name_suffix}",
                    "dependency_info" => $dependency_info,
                    "dependent_values_info" =>
                        $this->_fields[$dependent_field_name]["input"]["values"],
                );
            }
            $this->app->print_foreign_key_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_type,
                $input_attrs,
                $values_info,
                $input_type_params
            );
            break;
        case "integer":
            $input_info = get_param_value($field_info, "input", array());
            $this->app->print_integer_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs,
                get_param_value($input_info, "nonset_value_caption_pair", null)
            );
            break;
        case "double":
            $input_info = get_param_value($field_info, "input", array());
            $this->app->print_double_form_value(
                $template_var,
                $input_name,
                $db_value,
                $field_info["prec"],
                $input_attrs,
                get_param_value($input_info, "nonset_value_caption_pair", null)
            );
            break;
        case "currency":
            $this->app->print_currency_form_value(
                $template_var,
                $input_name,
                $db_value,
                $field_info["prec"],
                $input_attrs,
                $values_info
            );
            break;
        case "boolean":
            $this->app->print_boolean_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs
            );
            break;
        case "enum":
            $this->app->print_enum_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_type,
                $input_attrs,
                $values_info
            );
            break;
        case "varchar":
            if ($this->is_field_multilingual(null, $field_info)) {
                $this->app->print_multilingual_form_value($template_var);
            } else {
                $this->app->print_varchar_form_value(
                    $template_var,
                    $input_name,
                    $db_value,
                    $input_type,
                    $input_attrs
                );
            }
            break;
        case "text":
            if ($this->is_field_multilingual(null, $field_info)) {
                $this->app->print_multilingual_form_value($template_var);
            } else {
                $this->app->print_text_form_value(
                    $template_var,
                    $input_name,
                    $db_value,
                    $input_type,
                    $input_attrs
                );
            }
            break;
        case "datetime":
            $this->app->print_datetime_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs
            );
            break;
        case "date":
            $this->app->print_date_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs
            );
            break;
        case "time":
            $this->app->print_time_form_value(
                $template_var,
                $input_name,
                $db_value,
                $input_attrs
            );
            break;
        }
    }
//
    function print_filter_form_values(
        $template_var_prefix = null,
        $input_name_prefix = null,
        $input_name_suffix = null
    ) {
        foreach ($this->_filters as $filter_info) {
            $this->print_filter_form_value(
                $filter_info,
                $template_var_prefix,
                $input_name_prefix,
                $input_name_suffix
            );
        }
    }

    function print_filter_form_value(
        $filter_info,
        $template_var_prefix = null,
        $input_name_prefix = null,
        $input_name_suffix = null
    ) {
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
        $values_source = null;
        if (isset($filter_info["input"]["values"])) {
            $values_info = $filter_info["input"]["values"];
            $values_source = $values_info["source"];
            if ($values_source == "field") {
                $alt_values_info = $values_info;
                $values_info = $this->_fields[$filter_name]["input"]["values"];
            }
        }

        if (is_null($template_var_prefix)) {
            $template_var_prefix = $this->_table_name;
        }
        if (is_null($input_name_prefix)) {
            $input_name_prefix = $this->_table_name;
        }
        if (is_null($input_name_prefix)) {
            $input_name_suffix = "";
        }

        $template_var = "{$template_var_prefix}.{$filter_name}.{$filter_relation}";
        $filter_input_name =
            "{$input_name_prefix}_{$filter_name}_{$filter_relation}{$input_name_suffix}";

        $this->app->print_value($template_var, $filter_value);
        $this->app->print_hidden_input_form_value($template_var, $filter_input_name, $filter_value);

        switch ($filter_input_type) {
        case "text":
            $this->app->print_text_input_form_value(
                $template_var,
                $filter_input_name,
                $filter_value,
                array_merge(
                    array("class" => "varchar_normal"),
                    $filter_input_attrs
                )
            );
            break;
        case "checkbox":
            $this->app->print_checkbox_input_form_value(
                $template_var,
                $filter_input_name,
                1,
                ($filter_value != 0),
                $filter_input_attrs
            );
            break;
        case "radio":
            $this->app->print_radio_group_input_form_value(
                $template_var,
                $filter_input_name,
                $filter_value,
                $filter_input_attrs,
                $values_info,
                $alt_values_info
            );
            break;
        case "select":
            $this->app->print_select_input_form_value(
                $template_var,
                $filter_input_name,
                $filter_value,
                $filter_input_attrs,
                $values_info,
                $alt_values_info
            );
            break;
        case "listbox":
            $filter_input_attrs["multiple"] = null;
            $this->app->print_select_input_form_value(
                $template_var,
                $filter_input_name,
                $filter_value,
                $filter_input_attrs,
                $values_info,
                $alt_values_info
            );
            break;
        case "main_select":
            $dependency_info = $values_info["dependency"];
            if ($values_source == "field") {
                $dependent_field_name = $dependency_info["field"];
                $dependent_filter_name = $dependent_field_name;
                $dependent_values_info = $this->_fields[$dependent_field_name]["input"]["values"];
            } else {
                $dependent_filter_name = $dependency_info["filter"];
                $dependent_filter_info = $this->get_filter_by_name($dependent_filter_name);
                $dependent_values_info = $dependent_filter_info["input"]["values"];
            }

            $dependent_select_name =
                "{$input_name_prefix}_{$dependent_filter_name}_" .
                    "{$filter_relation}{$input_name_suffix}";
            $this->app->print_main_select_input_form_value(
                $template_var,
                $filter_input_name,
                $filter_value,
                $filter_input_attrs,
                $values_info,
                $dependent_select_name,
                $dependency_info,
                $dependent_values_info,
                $alt_values_info
            );
            break;
        }
    }
//
    // Objects validation before store/update and validation helpers
    function validate($context = null, $context_params = array()) {
        $conditions = $this->get_validate_conditions($context, $context_params);
        $field_names_to_validate = $this->get_validate_field_names_for_context(
            $context,
            $context_params
        );

        $messages = array();
        foreach ($conditions as $condition_info) {
            $field_name = $condition_info["field"];
            if (!$this->should_validate_field($field_name, $field_names_to_validate)) {
                continue;
            }
            $this->validate_condition($messages, $condition_info);
        }
        return $messages;
    }

    // Should be redefined in child class
    function get_validate_conditions($context, $context_params) {
        return array();
    }

    // Should be redefined in child class if fields are different in each context
    function get_validate_field_names_for_context($context, $context_params) {
        return null;
    }

    function validate_condition(&$messages, $condition_info) {
        $field_name = $condition_info["field"];
        $type = $condition_info["type"];
        $param = get_param_value($condition_info, "param", null);

        if ($this->validate_condition_by_type($field_name, $type, $param)) {
            $condition_info = get_param_value($condition_info, "dependency", null);
            if (!is_null($condition_info)) {
                $this->validate_condition($messages, $condition_info);
            }
        } else {
            $resource = get_param_value($condition_info, "message", null);
            if (!is_null($resource)) {
                $messages[] = new ErrorStatusMsg(
                    $resource,
                    get_param_value($condition_info, "message_params", array())
                );
            }
        }
    }

    function validate_condition_by_type($field_name, $type, $param) {
        switch ($type) {
        case "regexp":
            $result = $this->validate_regexp_condition($field_name, $param);
            break;
        case "empty":
            $result = $this->validate_empty_condition($field_name);
            break;
        case "not_empty":
            $result = $this->validate_not_empty_condition($field_name);
            break;
        case "email":
            $result = $this->validate_email_condition($field_name);
            break;
        case "unique":
            $result = $this->validate_unique_condition($field_name);
            break;
        case "equal":
            $result = $this->validate_equal_condition($field_name, $param);
            break;
        case "not_equal":
            $result = $this->validate_not_equal_condition($field_name, $param);
            break;
        case "uploaded_file_types":
            $type = get_param_value($param, "type", "files");
            $custom_types_str = get_param_value($param, "custom_types", "*");
            if ($type != "custom") {
                $custom_types_str = trim(
                    $this->get_config_value("uploaded_{$type}_types_allowed")
                );
            }
            if ($custom_types_str == "*") {
                $file_types_allowed = null;
            } else {
                $file_types_allowed = preg_split('/\s*,\s*/', trim($custom_types_str));
            }
            $result = $this->validate_uploaded_file_types_condition(
                $param["input_name"],
                $file_types_allowed
            );
            break;
        default:
            $result = true;
        }
        return $result;
    }
//
    function validate_regexp_condition($field_name, $regexp) {
        return preg_match($regexp, $this->{$field_name});
    }

    function validate_empty_condition($field_name) {
        if ($this->is_field_multilingual($field_name)) {
            foreach ($this->app->avail_langs as $lang) {
                $field_names = $this->get_field_names_with_lang_subst(
                    array($field_name),
                    $lang
                );
                if (!$this->validate_empty_condition($field_names[0])) {
                    return false;
                }
            }
            return true;
        } else {
            return (is_value_empty($this->{$field_name}));
        }
    }

    function validate_not_empty_condition($field_name) {
        if ($this->is_field_multilingual($field_name)) {
            foreach ($this->app->avail_langs as $lang) {
                $field_names = $this->get_field_names_with_lang_subst(
                    array($field_name),
                    $lang
                );
                if (!$this->validate_not_empty_condition($field_names[0])) {
                    return false;
                }
            }
            return true;
        } else {
            return (is_value_not_empty($this->{$field_name}));
        }
    }

    function validate_email_condition($field_name) {
        return is_value_email($this->{$field_name});
    }

    function validate_unique_condition($field_name) {
        if (is_array($field_name)) {
            $field_names = $field_name;
        } else {
            $field_names = array($field_name);
        }

        if ($this->has_multilingual_fields($field_names)) {
            foreach ($this->app->avail_langs as $lang) {
                $field_names_to_validate = $this->get_field_names_with_lang_subst(
                    $field_names,
                    $lang
                );
                if (!$this->validate_unique_condition($field_names_to_validate)) {
                    return false;
                }
            }
        } else {
            if ($this->was_definite()) {
                $should_check_db_table = false;
                foreach ($field_names as $field_name) {
                    if ($this->was_field_value_changed($field_name)) {
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
        }
        return true;
    }
    
    function are_field_values_exist($field_names) {
        $query = new SelectQuery(array(
            "from" => "{%{$this->_table_name}_table%} AS {$this->_table_name}",
            "where" => $this->create_where_expression($field_names),
        ));
        return $this->db->get_select_query_num_rows($query);
    }

    function create_where_expression($field_names) {
        $where_expressions = array();
        foreach ($field_names as $field_name) {
            $field_info = $this->_fields[$field_name];
            $where_expressions[] = "{$field_info['select']} = " . qw($this->{$field_name});
        }
        return join(" AND ", $where_expressions);
    }

    function validate_equal_condition($field_name, $value) {
        return ($this->{$field_name} == $value);
    }

    function validate_not_equal_condition($field_name, $value) {
        return ($this->{$field_name} != $value);
    }

    function should_validate_field($field_name, $field_names_to_validate) {
        return (
            is_null($field_names_to_validate) ||
            in_array($field_name, $field_names_to_validate)
        );
    }
//
    function validate_uploaded_file_types_condition($input_name, $file_types_allowed) {
        if (is_null($file_types_allowed)) {
            return true;
        }

        $file_info = get_uploaded_file_info($input_name);
        $type = get_file_extension($file_info["name"]);
        
        return (in_array(strtolower($type), $file_types_allowed));
    }
//
    function print_client_validation_js() {
        $conditions = $this->get_validate_conditions($this->_context, array());
        $field_names_to_validate = $this->get_validate_field_names_for_context(
            $this->_context,
            array()
        );
        
        $client_validate_condition_strs = array();
        foreach ($conditions as $condition_info) {
            $field_name = $condition_info["field"];
            if (!$this->should_validate_field($field_name, $field_names_to_validate)) {
                continue;
            }
            
            if ($this->is_field_multilingual($field_name)) {
                foreach ($this->app->avail_langs as $lang) {
                    $client_validate_condition_strs[] = $this->get_client_validate_condition_str(
                        $condition_info,
                        $lang
                    );
                }
            } else {
                $client_validate_condition_str = $this->get_client_validate_condition_str(
                    $condition_info
                );
                if (!is_null($client_validate_condition_str)) {
                    $client_validate_condition_strs[] = $client_validate_condition_str;
                }
            }
        }
        $this->app->print_raw_value(
            "{$this->_template_var_prefix}.client_validation_js",
            create_client_validation_js($client_validate_condition_strs)
        );
    }

    function get_client_validate_condition_str($condition_info, $lang = null) {
        $dependent_condition_info = get_param_value($condition_info, "dependency", null);
        $dependent_validate_condition_str = (is_null($dependent_condition_info)) ?
            null :
            $this->get_client_validate_condition_str($dependent_condition_info, $lang);

        $field_name = $condition_info["field"];
        if (!is_null($lang)) {
            $full_field_name = "{$field_name}_{$lang}";
            if ($this->is_field_exist($full_field_name)) {
                $field_name = $full_field_name;
            }
        }
            
        $input_name = "{$this->_input_name_prefix}_{$field_name}{$this->_input_name_suffix}";
        $type = $condition_info["type"];
        
        switch ($type) {
        case "regexp":
        case "empty":
        case "not_empty":
        case "email":
        case "equal":
        case "not_equal":
        case "zip":
        case "phone":
        case "number_integer":
        case "number_double":
        case "number_equal":
        case "number_not_equal":
        case "number_greater":
        case "number_greater_equal":
        case "number_less":
        case "number_less_equal":
            $validate_condition_str = create_client_validate_condition_str(
                $input_name,
                $type,
                (string) $this->get_lang_str("status_msg.{$condition_info['message']}"),
                get_param_value($condition_info, "param", null),
                $dependent_validate_condition_str
            );
            break;
        default:
            $validate_condition_str = null;
        }
        
        return $validate_condition_str;
    }
//
    // check reference integrity
    function check_restrict_relations_before_delete() {
        $obj_dependency_counters = array();
        foreach ($this->get_restrict_relations() as $relation) {
            list($dep_obj_class_name, $key_field_name) = $relation;
            $dep_obj =& $this->create_db_object($dep_obj_class_name);

            $query = new SelectQuery(array(
                "select" => "id",
                "from"   => "{%{$dep_obj->_table_name}_table%}",
                "where"  => "{$key_field_name} = {$this->id}",
            ));

            $n = $this->db->get_select_query_num_rows($query);
            if ($n > 0) {
                if (isset($obj_dependency_counters[$dep_obj_class_name])) {
                    $obj_dependency_counters[$dep_obj_class_name] += $n;
                } else {
                    $obj_dependency_counters[$dep_obj_class_name] = $n;
                }
            }
        }

        $messages = array();
        if (count($obj_dependency_counters) != 0) {
            $dep_objs_data = array();
            foreach ($obj_dependency_counters as $dep_obj_class_name => $dependency_counter) {
                $dep_obj =& $this->create_db_object($dep_obj_class_name);
                $dep_objs_data[] = $dep_obj->get_quantity_str($dependency_counter);
            }
            $messages[] = new ErrorStatusMsg(
                "cannot_delete_record_because_it_has_restrict_relations",
                array(
                    "main_obj_name" => $this->get_singular_name(),
                    "dep_objs_info_str" => join(", ", $dep_objs_data),
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
            $field_names_to_select,
            $field_names_to_not_select
        );

        $comma = "";
        $fields_expression = "";
        foreach ($field_names as $field_name) {
            $field_info = $this->_fields[$field_name];

            $fields_expression .= $comma;
            $comma = ", ";
            $fields_expression .= "{$field_info['select']} AS {$field_name}";
        }

        return new SelectQuery(array(
            "select" => $fields_expression,
            "from" => $this->_select_from,
        ));
    }

    function get_expanded_select_query(
        $query_ex = array(),
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        $query = $this->get_select_query(
            $field_names_to_select,
            $field_names_to_not_select
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
                $query_ex,
                $field_names_to_select,
                $field_names_to_not_select
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
    function get_default_where_str($add_table_name_alias = true) {
        // Return default WHERE condition for fetching one object
        $pr_key_name  = $this->get_primary_key_name();
        $pr_key_value = $this->get_primary_key_value();
        $pr_key_value_str = qw($pr_key_value);

        if ($add_table_name_alias) {
            return "{$this->_table_name}.{$pr_key_name} = {$pr_key_value_str}";
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
        // Fetch data from db table using given WHERE condition
        // Return true if ONE record found
        
        if (is_null($where_str)) {
            $where_str = $this->get_default_where_str();
        }
        $res = $this->run_expanded_select_query(
            new SelectQueryEx(array(
                "where" => $where_str,
            )),
            $field_names_to_select,
            $field_names_to_not_select
        );

        if ($res->get_num_rows() != 1) {  // record not found
            return false;
        }

        $res->fetch_next_row_to_db_object($this);

        return true;
    }

    function &create_db_object($obj_class_name, $obj_params = array()) {
        return $this->app->create_db_object($obj_class_name, $obj_params);
    }

    function &fetch_db_object(
        $obj,
        $obj_id,
        $where_str = "1",
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        return $this->app->fetch_db_object(
            $obj,
            $obj_id,
            $where_str,
            $field_names_to_select,
            $field_names_to_not_select
        );
    }

    function fetch_db_objects_list(
        $obj,
        $query_ex,
        $field_names_to_select = null,
        $field_names_to_not_select = null
    ) {
        return $this->app->fetch_db_objects_list(
            $obj,
            $query_ex,
            $field_names_to_select,
            $field_names_to_not_select
        );
    }

    function fetch_rows_list($query) {
        return $this->app->fetch_rows_list($query);
    }
//
    // Uploaded image helper functions
    function del_image($image_id_field_name) {
        $image_id = $this->{$image_id_field_name};
        if ($image_id != 0) {
            $image =& $this->create_db_object("Image");
            $image->del_where("id = {$image_id}");
        }
    }

    // Uploaded file helper functions
    function del_file($file_id_field_name) {
        $file_id = $this->{$file_id_field_name};
        if ($file_id != 0) {
            $file =& $this->create_db_object("File");
            $file->del_where("id = {$file_id}");
        }
    }

    function get_files_total_size($file_id_field_name) {
        $query = new SelectQuery(array(
            "select" => "IFNULL(SUM(LENGTH(file.content)), 0) as total_size",
            "from" =>
                "{%{$this->_table_name}_table%} AS file_info " .
                    "INNER JOIN {%file_table%} AS file" .
                    " ON file_info.{$file_id_field_name} = file.id",
        ));
        $res = $this->run_select_query($query);
        $row = $res->fetch_next_row();
        return $row["total_size"];
    }

}

?>