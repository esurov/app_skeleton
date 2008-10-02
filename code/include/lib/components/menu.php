<?php

class Menu extends TemplateComponent {
    
    var $name;
    var $template_name;
    var $item_template_name;
    var $item_selected_template_name = "";
    var $item_delimiter_template_name = "";
    var $item_has_sub_menu_template_name = "";
    var $print_delimiter_before_selected_item = true;
    var $print_delimiter_after_selected_item = true;
    var $print_sub_menu_always = false;

    var $items = array();

    var $parent_menu = null;
//
    function add_item(&$item) {
        $this->items[] =& $item;
    }

    function remove_item(&$item) {
        $this->remove_item_by_name($item->name);
    }

    function remove_item_by_name($name) {
        $menu_item =& $this->get_item_by_name($name);
        if (!is_null($menu_item)) {
            unset($menu_item);
        }
    }

    function hide_item_by_name($name) {
        $menu_item =& $this->get_item_by_name($name);
        if (!is_null($menu_item)) {
            $menu_item->is_visible = false;
        }
    }

    function hide_items_by_names($names) {
        foreach ($names as $name) {
            $this->hide_item_by_name($name);
        }
    }

    function &get_item_by_name($name) {
        $result_item = null;
        $n = count($this->items);
        for ($i = 0; $i < $n; $i++) {
            $menu_item =& $this->items[$i];
            if ($menu_item->name == $name) {
                $result_item =& $menu_item;
                break;
            }
        }
        return $result_item;
    }
//
    function get_sub_menu_template_var() {
        return "sub_{$this->template_var}";
    }
//
    function load_from_xml($xml_filename) {
        $this->app->page->verbose_turn_off();
        $xml_file_content = $this->app->print_file_if_exists(
            "{$this->templates_dir}/{$xml_filename}"
        );
        $this->app->page->verbose_restore();

        $menu_xml = new MenuXml($this->app->html_charset);
        $menu_xml->menu =& $this;
        $menu_xml->parse($xml_file_content);
    }
//
    function print_values() {
        return $this->print_menu_items($this);
    }

    function print_menu_items(&$menu) {
        $this->app->print_raw_value("{$menu->template_var}_items", "");

        $n = count($menu->items);
        for ($i = 0; $i < $n; $i++) {
            $menu_item =& $menu->items[$i];

            if (!$menu_item->is_visible) {
                continue;
            }
        
            if ($menu->print_sub_menu_always || $menu_item->is_selected) {
                if ($menu_item->has_sub_menu()) {
                    $this->print_menu_items($menu_item->sub_menu);
                } else {
                    if ($menu->print_sub_menu_always) {
                        $this->app->print_raw_value($menu->get_sub_menu_template_var(), "");
                    }
                }
            }
            $item_template_name = ($menu_item->is_selected) ?
                $menu_item->item_selected_template_name :
                $menu_item->item_template_name;

            $caption = $menu_item->caption;
            if (is_null($caption)) {
                $caption_resource = "{$menu->name}_item.{$menu_item->name}";
                $caption = $this->get_lang_str($caption_resource);
                if (is_null($caption)) {
                    $this->process_fatal_error(
                        "Cannot find language resource '{$caption_resource}' for menu item!"
                    );
                }
            }
            $this->app->print_values(array(
                "name" => $menu_item->name,
                "url" => $menu_item->url,
            ));

            if ($menu_item->is_html) {
                $this->app->print_raw_value("caption", $caption);
            } else {
                $this->app->print_value("caption", $caption);
            }

            if ($menu_item->has_sub_menu() && $menu->item_has_sub_menu_template_name != "") {
                $this->app->print_file_new(
                    "{$menu->templates_dir}/{$menu->item_has_sub_menu_template_name}",
                    "has_sub_menu"
                );
            } else {
                $this->app->print_raw_value("has_sub_menu", "");
            }
            
            $this->app->print_file(
                "{$menu->templates_dir}/{$item_template_name}",
                "{$menu->template_var}_items"
            );

            if ($i != $n - 1) {
                if ($menu->items[$i + 1]->is_selected) {
                    $print_delimiter = $menu->print_delimiter_before_selected_item;
                } else if ($menu_item->is_selected) {
                    $print_delimiter = $menu->print_delimiter_after_selected_item;
                } else {
                    $print_delimiter = true;
                }
                
                if ($print_delimiter && $menu->item_delimiter_template_name != "") {
                    $this->app->print_file(
                        "{$menu->templates_dir}/{$menu->item_delimiter_template_name}",
                        "{$menu->template_var}_items"
                    );
                }
            }
        }
        
        return $this->app->print_file_new_if_exists(
            "{$menu->templates_dir}/{$menu->template_name}",
            $menu->template_var
        );
    }

    function select_items_by_context($context) {
        $n = count($this->items);
        for ($i = 0; $i < $n; $i++) {
            $menu_item =& $this->items[$i];
            if ($menu_item->has_context($context)) {
                $menu_item->is_selected = true;
            }
            if ($menu_item->has_sub_menu()) {
                $menu_item->sub_menu->select_items_by_context($context);
            }
        }
    }

}

class MenuItem {
    
    var $name;
    var $caption = null;
    var $url;
    var $is_html;
    var $item_template_name;
    var $item_selected_template_name;
    var $contexts;

    var $sub_menu = null;

    var $is_visible = true;
    var $is_selected = false;

    function has_sub_menu() {
        return !is_null($this->sub_menu);
    }

    function has_context($context) {
        if (in_array($context, $this->contexts)) {
            return true;
        }
        if ($this->has_sub_menu()) {
            $n = count($this->sub_menu->items);
            for ($i = 0; $i < $n; $i++) {
                $menu_item =& $this->sub_menu->items[$i];
                if ($menu_item->has_context($context)) {
                    return true;
                }
            }
        }
        return false;
    }

}

class MenuXml extends XML {

    var $menu = null;
    var $current_menu = null;
    var $current_menu_item = null;

    function MenuXml($charset) {
        parent::XML($charset);
    }

    function tag_open($parser, $tag, $attributes) {
        parent::tag_open($parser, $tag, $attributes);

        switch ($tag) {
        case "menu":
            if (is_null($this->current_menu)) {
                $this->current_menu =& $this->menu;
                $menu =& $this->menu;
            } else {
                $menu = new Menu($this->current_menu->app);
                $menu->parent_menu =& $this->current_menu;
                $menu->templates_dir = $menu->parent_menu->templates_dir;
                $menu->template_var = $menu->parent_menu->get_sub_menu_template_var();

                $this->current_menu =& $menu;
                $this->current_menu_item->sub_menu =& $menu;
            }

            $menu->name = $attributes["name"];
            $menu->template_name = $attributes["template"];
            $menu->item_template_name = get_param_value(
                $attributes,
                "item_template",
                ""
            );
            $menu->item_selected_template_name = get_param_value(
                $attributes,
                "item_selected_template",
                $menu->item_template_name
            );
            $menu->item_delimiter_template_name = get_param_value(
                $attributes,
                "item_delimiter_template",
                ""
            );
            $menu->item_has_sub_menu_template_name = get_param_value(
                $attributes,
                "item_has_sub_menu_template",
                ""
            );
            $print_delimiter_before_selected_item_str = get_param_value(
                $attributes,
                "print_delimiter_before_selected_item",
                "true"
            );
            $menu->print_delimiter_before_selected_item =
                ($print_delimiter_before_selected_item_str == "true") ? true : false;
            $print_delimiter_after_selected_item_str = get_param_value(
                $attributes,
                "print_delimiter_after_selected_item",
                "true"
            );
            $menu->print_delimiter_after_selected_item =
                ($print_delimiter_after_selected_item_str == "true") ? true : false;

            $print_sub_menu_always_str = get_param_value(
                $attributes,
                "print_sub_menu_always",
                "false"
            );
            $menu->print_sub_menu_always =
                ($print_sub_menu_always_str == "true") ? true : false;
            break;
        case "menu_item":
            $menu_item = new MenuItem();

            $menu_item->name = $attributes["name"];
            $menu_item->url = $attributes["url"];

            $is_html_str = get_param_value($attributes, "is_html", "true");
            $menu_item->is_html = ($is_html_str == "true") ? true : false;

            $menu_item->item_template_name = get_param_value(
                $attributes,
                "item_template",
                $this->current_menu->item_template_name
            );
            $menu_item->item_selected_template_name = get_param_value(
                $attributes,
                "item_selected_template",
                $this->current_menu->item_selected_template_name
            );

            $contexts = get_param_value($attributes, "contexts", "");
            $menu_item->contexts = preg_split('/[\s,]+/', $contexts);

            $this->current_menu->add_item($menu_item);
            
            $this->current_menu_item =& $menu_item;
            break;
        }
    }

    function tag_close($parser, $tag) {
        parent::tag_close($parser, $tag);

        switch ($tag) {
        case "menu":
            $this->current_menu =& $this->current_menu->parent_menu;
            break;
        }
    }

    function cdata($parser, $cdata) {
    }

}

?>