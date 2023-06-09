<?php

class _ObjectsList extends ObjectTemplateComponent {

    var $_current_obj_idx;
    var $_num_objects;

    function &_fetch_list_object() {
        $obj = false;
        return $obj;
    }

    function _get_num_objects() {
        return $this->_num_objects;
    }
//
    function _print_values() {
        parent::_print_values();

        return $this->_print_list();
    }

    function _print_list() {
        $this->app->print_raw_value("{$this->template_var_prefix}_items", "");

        $this->_current_obj_idx = 0;
        while ($obj =& $this->_fetch_list_object()) {
            $this->_print_object_values($obj);

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
        
        $this->app->print_file_new(
            "{$this->templates_dir}/{$list_items_template_name}",
            "{$this->template_var_prefix}_list"
        );

        return $this->app->print_file_new(
            "{$this->templates_dir}/list.{$this->templates_ext}",
            $this->template_var
        );
    }

    function _print_object_values(&$obj) {
        $list_item_parity = $this->_current_obj_idx % 2;
        $list_item_class = ($list_item_parity == 0) ?
            "list_item_even" :
            "list_item_odd";

        $obj->print_values(array(
             "templates_dir" => $this->templates_dir,
             "template_var_prefix" => $this->template_var_prefix,
             "context" => $this->context,
             "custom_params" => $this->custom_params,
             
             // These params may be accessed in DbObject::print_values()
             // using get_param_value() only when printing list item
             "list_item_number" => $this->_current_obj_idx + 1,
             "list_item_parity" => $list_item_parity,
             "list_item_class" => $list_item_class,
             "list_items_count" => $this->_get_num_objects(),
        ));

        $this->app->print_raw_values(array(
            "list_item_parity" => $list_item_parity,
            "list_item_class" => $list_item_class,
        ));
    }

}

class ObjectsList extends _ObjectsList {

    var $objects;

    function _init($params) {
        parent::_init($params);

        $this->objects = get_param_value($params, "objects", null);
        if (is_null($this->objects)) {
            $this->process_fatal_error_required_param_not_found("objects");
        }
        if ($this->template_var_prefix == "") {
            $this->process_fatal_error_required_param_not_found("template_var_prefix");
        }
    }
//
    function _on_before_print_values() {
        parent::_on_before_print_values();

        $this->_num_objects = count($this->objects);
    }

    function &_fetch_list_object() {
        if ($this->_current_obj_idx == $this->_get_num_objects()) {
            $obj = false;
        } else {
            $obj =& $this->objects[$this->_current_obj_idx];
        }
        return $obj;
    }

}

class QueryObjectsList extends _ObjectsList {

    var $query;
    
    var $_res;

    function _init($params) {
        parent::_init($params);

        if (is_null($this->obj)) {
            $this->process_fatal_error_required_param_not_found("obj");
        }

        $this->query = get_param_value($params, "query", $this->obj->get_select_query());
        $query_ex = get_param_value($params, "query_ex", array());
        $this->query->expand($query_ex);
    }
//
    function _on_before_print_values() {
        parent::_on_before_print_values();

        $this->_res = $this->obj->run_select_query($this->query);
        $this->_num_objects = $this->_res->get_num_rows();
    }

    function &_fetch_list_object() {
        if ($row = $this->_res->fetch_next_row_to_db_object($this->obj)) {
            $obj =& $this->obj;
        } else {
            $obj = false;
        }
        return $obj;
    }

}

class PagedQueryObjectsList extends QueryObjectsList {

    var $default_order_by;

    var $filter_form_visible;
    var $filter_form_template_name;

    var $pager_visible;
    var $pager;

    var $_filters_suburl_params;
    var $_order_by_suburl_params;

    var $_action_suburl_params;
    var $_action_filters_suburl_params;
    var $_action_filters_order_by_suburl_params;

    var $_obj_quantity_str;

    function _init($params) {
        parent::_init($params);

        $this->default_order_by = get_param_value($params, "default_order_by", null);

        $this->pager_visible = get_param_value($params, "pager.visible", true);
        if ($this->pager_visible) {
            $pager_n_rows_per_page = get_param_value($params, "pager.n_rows_per_page", null);
            $pager_type = get_param_value($params, "pager.type", null);
            $pager_show_one_page = get_param_value($params, "pager.show_one_page", null);
            $pager_pages_title_str = get_param_value($params, "pager.pages_title_str", null);
            $pager_prev_page_str = get_param_value($params, "pager.prev_page_str", null);
            $pager_next_page_str = get_param_value($params, "pager.next_page_str", null);
            $pager_page_begin_str = get_param_value($params, "pager.page_begin_str", null);
            $pager_page_end_str = get_param_value($params, "pager.page_end_str", null);
            $pager_delimiter_str = get_param_value($params, "pager.delimiter_str", null);
            $pager_ignored_suburl_params = get_param_value(
                $params,
                "pager.ignored_suburl_params",
                null
            );
        }

        $this->filter_form_visible = get_param_value($params, "filter_form.visible", false);
        $this->filter_form_template_name = get_param_value(
            $params,
            "filter_form_template_name",
            "filter_form.{$this->templates_ext}"
        );

        // Make sub-URL params with all necessary parameters stored
        $action_suburl_param = array("action" => $this->app->action);
        $extra_suburl_params = $this->app->get_app_extra_suburl_params();

        $this->_action_suburl_params = $action_suburl_param;
        $this->_action_filters_suburl_params = $action_suburl_param;
        $this->_action_filters_order_by_suburl_params = $action_suburl_param;

        $this->_filters_suburl_params = array();
        if ($this->filter_form_visible) {
            // Apply filters to query
            $this->obj->read_filters();
            $this->_filters_suburl_params = $this->obj->get_filters_suburl_params();
            $this->query->expand($this->obj->get_filters_query_ex());

            $this->_action_filters_suburl_params += $this->_filters_suburl_params;
            $this->_action_filters_order_by_suburl_params += $this->_filters_suburl_params;
        }

        $this->_order_by_suburl_params = array();
        if (!is_null($this->default_order_by)) {
            // Apply ordering to query
            $this->obj->read_order_by($this->default_order_by);
            $this->_order_by_suburl_params = $this->obj->get_order_by_suburl_params();
            $this->query->expand($this->obj->get_order_by_query_ex());

            $this->_action_filters_order_by_suburl_params += $this->_order_by_suburl_params;
        }

        if (!is_null($this->custom_params)) {
            $this->_action_suburl_params += $this->custom_params;
            $this->_action_filters_suburl_params += $this->custom_params;
            $this->_action_filters_order_by_suburl_params += $this->custom_params;
        }

        $this->_action_suburl_params += $extra_suburl_params;
        $this->_action_filters_suburl_params += $extra_suburl_params;
        $this->_action_filters_order_by_suburl_params += $extra_suburl_params;

        if ($this->pager_visible) {
            $n_objects_total = $this->app->db->get_select_query_num_rows($this->query);

            if ($n_objects_total == 0) {
                $this->pager_visible = false;
            } else {
                $pager_suburl_params = $this->_action_filters_order_by_suburl_params;
                if (!is_null($pager_ignored_suburl_params)) {
                    foreach ($pager_ignored_suburl_params as $pager_ignored_suburl_param) {
                        unset($pager_suburl_params[$pager_ignored_suburl_param]);
                    }
                }
                $this->pager =& $this->create_object(
                    "Pager",
                    array(
                        "n_total_rows" => $n_objects_total,
                        "n_rows_per_page" => $pager_n_rows_per_page,
                        "suburl_params" => $pager_suburl_params,
                        "type" => $pager_type,
                        "show_one_page" => $pager_show_one_page,
                        "pages_title_str" => $pager_pages_title_str,
                        "prev_page_str" => $pager_prev_page_str,
                        "next_page_str" => $pager_next_page_str,
                        "page_begin_str" => $pager_page_begin_str,
                        "page_end_str" => $pager_page_end_str,
                        "delimiter_str" => $pager_delimiter_str,
                    )
                );
                $this->pager->read();
                $this->query->expand($this->pager->get_query_ex());
                $this->_obj_quantity_str = $this->obj->get_quantity_str($n_objects_total);
            }
        }
    }

    function _print_custom_params() {
        parent::_print_custom_params();

        $this->app->print_values(array(
            "action.suburl" => create_suburl($this->_action_suburl_params),
            "filters.suburl" => create_suburl($this->_filters_suburl_params),
            "order_by.suburl" => create_suburl($this->_order_by_suburl_params),
            "action_filters.suburl" => create_suburl($this->_action_filters_suburl_params),
            "action_filters_order_by.suburl" => create_suburl(
                $this->_action_filters_order_by_suburl_params
            ),
        ));
    }

    function _print_list() {
        if ($this->filter_form_visible) {
            $this->obj->print_filter_form_values(
                $this->template_var_prefix
            );
            $this->app->print_file_new_if_exists(
                "{$this->templates_dir}/{$this->filter_form_template_name}",
                "{$this->template_var_prefix}_filter_form"
            );
        }

        if ($this->pager_visible) {
            $this->pager->print_values();
            $this->app->print_value("total", $this->_obj_quantity_str);
        }

        return parent::_print_list();
    }

}

?>