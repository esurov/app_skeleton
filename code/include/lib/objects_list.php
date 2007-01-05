<?php

class _ObjectsList extends ObjectTemplateComponent {

    var $_current_obj_idx;

    function _prepare_objects_list() {
    }

    function &_fetch_list_object() {
        $obj = false;
        return $obj;
    }

    function _get_num_objects() {
        return null;
    }
//
    function print_values() {
        $this->_prepare_objects_list();

        $this->print_list_custom_params();
        $content = $this->print_list();
        
        return $content;
    }

    function print_list_custom_params() {
        $this->app->print_custom_params($this->custom_params);
    }

    function print_list() {
        $this->app->print_raw_value("{$this->template_var_prefix}_items", "");

        $this->_current_obj_idx = 0;
        while ($obj =& $this->_fetch_list_object()) {
            $this->print_object_values($obj);

            if ($this->_current_obj_idx > 0) {
                $this->app->print_file_if_exists(
                    "{$this->templates_dir}/list_item_delimiter.{$this->templates_ext}",
                    "{$this->template_var_prefix}_items"
                );
            }

            $this->app->print_file(
                "{$this->templates_dir}/list_item.{$this->templates_ext}",
                "{$this->template_var_prefix}_items"
            );

            $this->_current_obj_idx++;
        }
        
        if (
            $this->_current_obj_idx == 0 &&
            $this->app->is_file_exist("{$this->templates_dir}/list_no_items.{$this->templates_ext}")
        ) {
            $list_items_template_name = "list_no_items.{$this->templates_ext}";
        } else {
            $list_items_template_name = "list_items.{$this->templates_ext}";
        }
        
        $this->app->print_file(
            "{$this->templates_dir}/{$list_items_template_name}",
            "{$this->template_var_prefix}_list"
        );

        return $this->app->print_file(
            "{$this->templates_dir}/list.{$this->templates_ext}",
            $this->template_var
        );
    }

    function print_object_values(&$obj) {
        $list_item_parity = $this->_current_obj_idx % 2;
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
             "list_item_number" => $this->_current_obj_idx + 1,
             "list_item_parity" => $list_item_parity,
             "list_item_class" => $list_item_class,
             "list_items_count" => $this->_get_num_objects(),
             "custom_params" => $this->custom_params,
        ));
    }

}

class ObjectsList extends _ObjectsList {

    var $objects;

    function init($params) {
        parent::init($params);

        $this->objects = get_param_value($params, "objects", null);
        if (is_null($this->objects)) {
            $this->app->process_fatal_error(
                "ObjectsList",
                "No 'objects' in ObjectsList::init()"
            );
        }
        if ($this->template_var_prefix == "") {
            $this->app->process_fatal_error(
                "ObjectsList",
                "No 'template_var_prefix' in ObjectsList::init()"
            );
        }
    }
//
    function &_fetch_list_object() {
        if ($this->_current_obj_idx == $this->_get_num_objects()) {
            $obj = false;
        } else {
            $obj =& $this->objects[$this->_current_obj_idx];
        }
        return $obj;
    }

    function _get_num_objects() {
        return count($this->objects);
    }

}

class QueryObjectsList extends _ObjectsList {

    var $query;
    
    var $_res;

    function init($params) {
        parent::init($params);

        if (is_null($this->obj)) {
            $this->app->process_fatal_error(
                "QueryObjectsList",
                "No 'obj' in QueryObjectsList::init()"
            );
        }

        $this->query = get_param_value($params, "query", $this->obj->get_select_query());
        $query_ex = get_param_value($params, "query_ex", array());
        $this->query->expand($query_ex);
    }
//
    function _prepare_objects_list() {
        $this->_res = $this->obj->run_select_query($this->query);
    }

    function &_fetch_list_object() {
        if ($row = $this->_res->fetch()) {
            $this->obj->fetch_row($row);
            $obj =& $this->obj;
        } else {
            $obj = false;
        }
        return $obj;
    }

    function _get_num_objects() {
        return $this->_res->get_num_rows();
    }

}

class PagedQueryObjectsList extends QueryObjectsList {

    var $default_order_by;

    var $filter_form_visible;
    var $filter_form_template_name;

    var $pager_visible;
    var $pager;

    var $_filters_params;
    var $_order_by_params;

    var $_action_suburl_params;
    var $_action_filters_suburl_params;
    var $_action_filters_order_by_suburl_params;

    var $_obj_quantity_str;

    function init($params) {
        parent::init($params);

        $this->default_order_by = get_param_value($params, "default_order_by", null);

        $this->pager_visible = get_param_value($params, "pager.visible", true);
        if ($this->pager_visible) {
            $pager_n_rows_per_page = get_param_value($params, "pager.n_rows_per_page", null);
            $pager_style = get_param_value($params, "pager.style", null);
        }

        $this->filter_form_visible = get_param_value($params, "filter_form.visible", false);
        $this->filter_form_template_name = get_param_value(
            $params,
            "filter_form_template_name",
            "filter_form.html"
        );

        $this->_filters_params = array();
        if ($this->filter_form_visible) {
            // Apply filters to query
            $this->obj->read_filters();
            $this->_filters_params = $this->obj->get_filters_params();
            $this->query->expand($this->obj->get_filters_query_ex());
        }

        $this->_order_by_params = array();
        if (!is_null($this->default_order_by)) {
            // Apply ordering to query
            $this->obj->read_order_by($this->default_order_by);
            $this->_order_by_params = $this->obj->get_order_by_params();
            $this->query->expand($this->obj->get_order_by_query_ex());
        }

        // Make sub-URL params with all necessary parameters stored
        $action_suburl_param = array("action" => $this->app->action);
        $extra_suburl_params = $this->app->get_app_extra_suburl_params();

        $this->_action_suburl_params =
            $action_suburl_param +
            $this->custom_params +
            $extra_suburl_params;
               
        $this->_action_filters_suburl_params =
            $action_suburl_param +
            $this->_filters_params +
            $this->custom_params +
            $extra_suburl_params;

        $this->_action_filters_order_by_suburl_params =
            $action_suburl_param +
            $this->_filters_params +
            $this->_order_by_params +
            $this->custom_params +
            $extra_suburl_params;

        if ($this->pager_visible) {
            $n_objects_total = $this->app->db->get_select_query_num_rows($this->query);

            if ($n_objects_total == 0) {
                $this->pager_visible = false;
            } else {
                $this->pager = $this->app->create_component(
                    "Pager",
                    array(
                        "n_rows_per_page" => $pager_n_rows_per_page,
                        "n_total_rows" => $n_objects_total,
                        "style" => $pager_style,
                        "suburl_params" => $this->_action_filters_order_by_suburl_params
                    )
                );
                $this->pager->read();
                $this->query->expand($this->pager->get_query_ex());

                $this->_obj_quantity_str = $this->obj->get_quantity_str($n_objects_total);
            }
        }
    }

    function print_list_custom_params() {
        parent::print_list_custom_params();

        $this->app->print_values(array(
            "action_suburl" => create_suburl($this->_action_suburl_params),
            "action_filters_suburl" => create_suburl($this->_action_filters_suburl_params),
            "action_filters_order_by_suburl" => create_suburl(
                $this->_action_filters_order_by_suburl_params
            ),
        ));
    }

    function print_list() {
        if ($this->filter_form_visible) {
            $this->app->print_values($this->_filters_params);
            $this->obj->print_filter_form_values();
            $this->app->print_file_new(
                "{$this->templates_dir}/{$this->filter_form_template_name}",
                "{$this->template_var_prefix}_filter_form"
            );
        }

        if ($this->pager_visible) {
            $this->pager->print_values();
            $this->app->print_value("total", $this->_obj_quantity_str);
        }

        return parent::print_list();
    }

}

?>