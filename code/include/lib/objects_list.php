<?php

class _ObjectsList {

    var $app;

    var $templates_dir;
    var $template_var_prefix;
    var $template_var;
    var $templates_ext;

    var $context;
    var $custom_params;

    var $_n_objects;

    function init($params) {
        $this->_n_objects = $this->get_num_objects();

        $obj_name = $this->get_obj_name();
        $this->template_var_prefix = get_param_value($params, "template_var_prefix", $obj_name);
        $this->templates_dir = get_param_value($params, "templates_dir", $obj_name);
        $this->templates_ext = get_param_value($params, "templates_ext", "html");
        $this->context = get_param_value($params, "context", "");
        $this->template_var = get_param_value(
            $params,
            "template_var",
            "{$this->template_var_prefix}_list"
        );
        $this->custom_params = get_param_value($params, "custom_params", array());
    }

    function print_values() {
        $this->app->print_custom_params($this->custom_params);

        $no_items_template_name = "{$this->templates_dir}/list_no_items.{$this->templates_ext}";
        if ($this->_n_objects == 0 && $this->app->is_file_exist($no_items_template_name)) {
            return $this->app->print_file($no_items_template_name, $this->template_var);
        } else {
            $this->app->print_raw_value("{$this->template_var_prefix}_items", "");

            $i = 0;
            while ($obj =& $this->fetch_object()) {
                $list_item_parity = $i % 2;
                $list_item_class = ($list_item_parity == 0) ?
                    "list-item-even" :
                    "list-item-odd";

                $this->app->print_raw_values(array(
                    "list_item_parity" => $list_item_parity,
                    "list_item_class" => $list_item_class,
                ));

                $obj->print_values(array(
                     "templates_dir" => $this->templates_dir,
                     "template_var_prefix" => $this->template_var_prefix,
                     "context" => $this->context,
                     "list_item_number" => $i + 1,
                     "list_item_parity" => $list_item_parity,
                     "list_item_class" => $list_item_class,
                     "list_items_count" => $this->_n_objects,
                     "custom_params" => $this->custom_params,
                ));

                if ($i > 0) {
                    $this->app->print_file_if_exists(
                        "{$this->templates_dir}/list_item_delimiter.{$this->templates_ext}",
                        "{$this->template_var_prefix}_items"
                    );
                }

                $this->app->print_file(
                    "{$this->templates_dir}/list_item.{$this->templates_ext}",
                    "{$this->template_var_prefix}_items"
                );
                
                $i++;
            }

            return $this->app->print_file(
                "{$this->templates_dir}/list_items.{$this->templates_ext}",
                $this->template_var
            );
        }
    }

    function &fetch_object() {
        $obj = false;
        return $obj;
    }

    function get_num_objects() {
        return null;
    }

    function get_obj_name() {
        return null;
    }

}

class ObjectsList extends _ObjectsList {

    var $objects;

    var $_obj_idx;

    function init($params) {
        $this->objects = get_param_value($params, "objects", null);
        if (is_null($this->objects)) {
            $this->app->process_fatal_error(
                "ObjectsList",
                "No objects in ObjectsList::init()"
            );
        }
        $this->_obj_idx = 0;

        parent::init($params);
    }

    function &fetch_object() {
        if ($this->_obj_idx == $this->_n_objects) {
            $obj = false;
        } else {
            $obj =& $this->objects[$this->_obj_idx++];
        }
        return $obj;
    }

    function get_num_objects() {
        return count($this->objects);
    }

    function get_obj_name() {
        return ($this->_n_objects == 0) ? "" : $this->objects[0]->table_name;
    }

}

class QueryObjectsList extends _ObjectsList {

    var $obj;
    var $res;

    function init($params) {
        $this->obj = get_param_value($params, "obj", null);
        if (is_null($this->obj)) {
            $this->app->process_fatal_error(
                "ObjectsQueryList",
                "No obj in ObjectsQueryList::init()"
            );
        }
        $query = get_param_value($params, "query", $this->obj->get_select_query());
        $query_ex = get_param_value($params, "query_ex", array());
        $query->expand($query_ex);
        
        $this->res = $this->obj->run_select_query($query);

        parent::init($params);
    }

    function &fetch_object() {
        if ($row = $this->res->fetch()) {
            $this->obj->fetch_row($row);
            $obj =& $this->obj;
        } else {
            $obj = false;
        }
        return $obj;
    }

    function get_num_objects() {
        return $this->res->get_num_rows();
    }

    function get_obj_name() {
        return $this->obj->table_name;
    }

}

?>