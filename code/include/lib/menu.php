<?php

class Menu extends XML {

    var $name = "";
    var $template_name = "";
    var $item_template_name = "";
    var $item_current_template_name = "";
    var $item_delimiter_template_name = "";
    var $print_delitimiter_before_current_item = true;
    var $print_delitimiter_after_current_item = true;

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
            $print_delitimiter_before_current_item_str =
                get_param_value($attributes, "print_delitimiter_before_current_item", "true");
            $this->print_delitimiter_before_current_item =
                ($print_delitimiter_before_current_item_str == "true") ? true : false;
            $print_delitimiter_after_current_item_str =
                get_param_value($attributes, "print_delitimiter_after_current_item", "true");
            $this->print_delitimiter_after_current_item =
                ($print_delitimiter_after_current_item_str == "true") ? true : false;
            break;
        case "menu_item":
            $this->last_item_name = $attributes["name"];
            $is_html_str = get_param_value($attributes, "is_html", "false");
            $this->items[$this->last_item_name] = array(
                "url" => $attributes["url"],
                "is_html" => ($is_html_str == "true") ? true : false,
            );

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
        case "contexts":
            $this->items[$this->last_item_name]["contexts"] = array();
            break;
        case "context":
            $this->items[$this->last_item_name]["contexts"][] = $attributes["name"];
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
        $context = get_param_value($params, "context", null);

        $this->app->print_raw_value("menu_items", "");

        $item_names = array_keys($this->items);
        $item_infos = array_values($this->items);

        $i = 0;
        $n = count($this->items);
        for ($i = 0; $i < $n; $i++) {
            $item_name = $item_names[$i];
            $item_info = $item_infos[$i];
        
            $caption = get_param_value($item_info, "caption", null);
            if (is_null($caption)) {
                $caption_resource = "{$this->name}_item_{$item_name}";
                $caption = $this->app->get_message($caption_resource);
                if (is_null($caption)) {
                    die("Cannot find resource '{$caption_resource}' for menu item!");
                }
            }
            $this->app->print_values(array(
                "name" => $item_name,
                "url" => $item_info["url"],
            ));

            if ($item_info["is_html"]) {
                $this->app->print_raw_value("caption", $caption);
            } else {
                $this->app->print_value("caption", $caption);
            }

            $is_item_current = $this->is_item_current(
                $context, $item_info["contexts"]
            );
            if ($is_item_current) {
                $item_template_name = get_param_value(
                    $item_info, "item_current_template", $this->item_current_template_name
                );
            } else {
                $item_template_name = get_param_value(
                    $item_info, "item_template", $this->item_template_name
                );
            }
            $this->app->print_file("{$templates_dir}/{$item_template_name}", "menu_items");
            
            if ($i != $n - 1) {
                $is_next_item_current = $this->is_item_current(
                    $context, $item_infos[$i + 1]["contexts"]
                );
                if ($is_next_item_current) {
                    $print_delimiter = $this->print_delitimiter_before_current_item;
                } else if ($is_item_current) {
                    $print_delimiter = $this->print_delitimiter_after_current_item;
                } else {
                    $print_delimiter = true;
                }
                
                if ($print_delimiter) {
                    $item_delimiter_template_name = get_param_value(
                        $item_info, "item_delimiter_template", $this->item_delimiter_template_name
                    );
                    $this->app->print_file(
                        "{$templates_dir}/{$item_delimiter_template_name}", "menu_items"
                    );
                }
            }
        }
        return $this->app->print_file_new_if_exists(
            "{$templates_dir}/{$this->template_name}", $template_var
        );
    }

    function is_item_current($context, $contexts) {
        return in_array($context, $contexts);
    }

}

?>