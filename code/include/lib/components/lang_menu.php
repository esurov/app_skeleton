<?php

class LangMenu extends TemplateComponent {

    var $avail_langs = array();
    var $current_lang = "";
    var $redirect_url_params = array();
    var $use_lang_in_redirect_url = false;

    function print_values() {
        $this->app->print_raw_value("lang_menu_items", "");
        foreach ($this->avail_langs as $lang) {
            if ($lang == $this->current_lang) {
                $this->app->print_values(array(
                    "current_lang" => $lang,
                    "current_lang.name" => $this->get_lang_str($lang),
                    "current_lang.name_native" => $this->get_lang_str("{$lang}_native"),
                    "current_lang.image_url" => $this->get_config_value(
                        "lang_image_url_{$lang}_current"
                    ),
                ));
                $item_template_name = "menu_item_current.html";
            } else {
                $this->app->print_values(array(
                    "new_lang" => $lang,
                    "new_lang.name" => $this->get_lang_str($lang),
                    "new_lang.name_native" => $this->get_lang_str("{$lang}_native"),
                    "new_lang.image_url" => $this->get_config_value("lang_image_url_{$lang}"),
                    "redirect_url_suburl" => create_suburl(array(
                        "redirect_url" => create_self_full_url(
                            $this->redirect_url_params,
                            ($this->use_lang_in_redirect_url) ? $lang : null
                        ),
                    )),
                ));
                $item_template_name = "menu_item.html";
            }
            $this->app->print_file(
                "{$this->templates_dir}/{$item_template_name}",
                "lang_menu_items"
            );
        }
        return $this->app->print_file_new("{$this->templates_dir}/menu.html", $this->template_var);
    }

}

?>