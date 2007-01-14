<?php

class LangMenu extends TemplateComponent {

    var $avail_langs = array();
    var $current_lang = "";

    function print_values() {
        $this->app->print_raw_value("lang_menu_items", "");
        foreach ($this->avail_langs as $lang) {
            if ($lang == $this->current_lang) {
                $this->app->print_raw_values(array(
                    "current_lang_name" => $this->app->get_message($lang),
                    "current_lang_image_url" =>
                        $this->get_config_value("lang_image_current_url_{$lang}"),
                ));
                $item_template_name = "menu_item_current.html";
            } else {
                $this->app->print_raw_values(array(
                    "new_lang" => $lang,
                    "new_lang_name" => $this->app->get_message($lang),
                    "new_lang_image_url" => $this->get_config_value("lang_image_url_{$lang}"),
                ));
                $item_template_name = "menu_item.html";
            }
            $this->app->print_file(
                "{$this->templates_dir}/{$item_template_name}",
                "lang_menu_items"
            );
        }
        return $this->app->print_file_new(
            "{$this->templates_dir}/menu.html",
            $this->template_var
        );
    }

}

?>