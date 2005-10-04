<?php

class Menu extends XML {
    var $name = "";
    var $template_name = "";
    var $item_template_name = "";
    var $item_current_template_name = "";
    var $item_delimiter_template_name = "";

    var $items = array();

    var $last_item_name = "";

    function Menu(&$app) {
        parent::XML($app);
    }

    function tag_open($parser, $tag, $attributes) {
        parent::tag_open($parser, $tag, $attributes);

        switch($tag) {
        case "menu":
            $this->name = $attributes["name"];
            $this->template_name = $attributes["template"];
            $this->item_template_name =
                get_param_value($attributes, "item_template", "");
            $this->item_current_template_name =
                get_param_value($attributes, "item_current_template", "");
            $this->item_delimiter_template_name =
                get_param_value($attributes, "item_delimiter_template", "");
            break;
        case "menu_item":
            $this->last_item_name = $attributes["name"];
            $this->items[$this->last_item_name] = array("url" => $attributes["url"]);

            $item_template_name =
                get_param_value($attributes, "item_template", null);
            if (!is_null($item_template_name)) {
                $this->items[$this->last_item_name]["item_template"] = $item_template_name;
            }
            $item_current_template_name =
                get_param_value($attributes, "item_current_template", null);
            if (!is_null($item_current_template_name)) {
                $this->items[$this->last_item_name]["item_current_template"] = 
                    $item_current_template_name;
            }
            break;
        case "actions":
            $this->items[$this->last_item_name]["actions"] = array();
            break;
        case "action":
            $this->items[$this->last_item_name]["actions"][] = $attributes["name"];
            break;
        }
    }

    function cdata($parser, $cdata) {
    }

    function tag_close($parser, $tag) {
    }
//
    function load_file($xml_file_path) {
        $old_print_template_name = $this->app->page->print_template_name;
        $this->app->page->print_template_name = false;
        $xml_file_content = $this->app->print_file_if_exists($xml_file_path);
        $this->app->page->print_template_name = $old_print_template_name;
        return $xml_file_content;
    }

    function print_menu($params = array()) {
        $templates_dir = get_param_value($params, "templates_dir", ".");
        $template_var = get_param_value($params, "template_var", null);
        $current_action = get_param_value($params, "current_action", null);

        $this->app->print_raw_value("menu_items", "");
        $n = count($this->items);
        foreach ($this->items as $item_name => $item_info) {
            $caption_resource = "{$this->name}_item_{$item_name}";
            $caption = $this->app->get_message($caption_resource);
            if (is_null($caption)) {
                die("Cannot find resource '{$caption_resource}' for menu item!");
            }
            $this->app->print_values(array(
                "name" => $item_name,
                "caption" => $caption,
                "url" => $item_info["url"],
            ));

            $item_actions = $item_info["actions"];
            if (in_array($current_action, $item_actions)) {
                $item_template_name = get_param_value(
                    $item_info, "item_current_template", $this->item_current_template_name
                );
            } else {
                $item_template_name = get_param_value(
                    $item_info, "item_template", $this->item_template_name
                );
            }
            $this->app->print_file("{$templates_dir}/{$item_template_name}", "menu_items");
            
            $n--;
            if ($n != 0) {
                $this->app->print_file(
                    "{$templates_dir}/{$this->item_delimiter_template_name}", "menu_items"
                );
            }
        }
        $this->app->print_file_new_if_exists(
            "{$templates_dir}/{$this->template_name}", $template_var
        );
    }
}

?>