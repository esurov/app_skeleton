<?php

class CategoryBrowser extends TemplateComponent {
    
    var $current_category1_id;
    var $current_category2_id;
    var $current_category3_id;

    function _init($params) {
        parent::_init($params);

        $this->current_category1_id = 0;
        $this->current_category2_id = 0;
        $this->current_category3_id = 0;
    }

    function _print_values() {
        parent::_print_values();

        $categories1_list =& $this->create_object(
            "ObjectsList",
            array(
                "templates_dir" => "{$this->templates_dir}/categories1",
                "template_var" => "categories1",
                "template_var_prefix" => "category1",
                "objects" => $this->fetch_categories1(),
                "context" => "category_browser_list_item",
                "custom_params" => array(
                    "selected_item_id" => $this->current_category1_id,
                ),
            )
        );
        $categories1_list->print_values();

        if ($this->current_category1_id == 0) {
            $this->app->print_file(
                "{$this->templates_dir}/categories2/list_no_parent_selected.html",
                "categories2"
            );
        } else {
            $categories2_list =& $this->create_object(
                "ObjectsList",
                array(
                    "templates_dir" => "{$this->templates_dir}/categories2",
                    "template_var" => "categories2",
                    "template_var_prefix" => "category2",
                    "objects" => $this->fetch_categories2(),
                    "context" => "category_browser_list_item",
                    "custom_params" => array(
                        "selected_item_id" => $this->current_category2_id,
                    ),
                )
            );
            $categories2_list->print_values();
        }

        if ($this->current_category2_id == 0) {
            $this->app->print_file(
                "{$this->templates_dir}/categories3/list_no_parent_selected.html",
                "categories3"
            );
        } else {
            $categories3_list =& $this->create_object(
                "ObjectsList",
                array(
                    "templates_dir" => "{$this->templates_dir}/categories3",
                    "template_var" => "categories3",
                    "template_var_prefix" => "category3",
                    "objects" => $this->fetch_categories3(),
                    "context" => "category_browser_list_item",
                    "custom_params" => array(
                        "selected_item_id" => $this->current_category3_id,
                    ),
                )
            );
            $categories3_list->print_values();
        }
        
        return $this->app->print_file("{$this->templates_dir}/body.html", $this->template_var);
    }

    function fetch_categories1() {
        return $this->app->fetch_db_objects_list(
            "Category1",
            array(
                "order_by" => "position ASC",
            )
        );
    }

    function fetch_categories2() {
        return $this->app->fetch_db_objects_list(
            "Category2",
            array(
                "where" => "category1_id = {$this->current_category1_id}",
                "order_by" => "position ASC",
            )
        );
    }

    function fetch_categories3() {
        return $this->app->fetch_db_objects_list(
            "Category3",
            array(
                "where" => "category2_id = {$this->current_category2_id}",
                "order_by" => "position ASC",
            )
        );
    }

}

?>