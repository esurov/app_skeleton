<?php

class CustomApp extends SqlApp {
    var $lang;
    var $dlang;
    var $avail_langs;

    function CustomApp($tables) {
        parent::SqlApp("!!!", $tables);

        $this->avail_langs = $this->get_avail_langs();
        $this->dlang = $this->config->value("default_language");
        $this->lang = $this->get_current_lang();

        $this->messages = new Config();
        $this->messages->read("lang/{$this->lang}.txt");
    }

    function get_current_lang() {
        $cur_lang = OA_Session::getParam("current_language");
        if (!$this->is_valid_lang($cur_lang)) {
            $cur_lang = $this->config->value('default_language');
        }
        return $cur_lang;
    }

    function get_avail_langs() {
        return explode(",", $this->config->value("languages"));
    }

    function is_valid_lang($lang) {
        if (is_null($lang) || !in_array($lang, $this->avail_langs)) {
            return false;
        } else {
            return true;
        }
    }

    function set_current_lang($new_lang) {
        if ($this->is_valid_lang($new_lang)) {
            OA_Session::setParam("current_language", $new_lang);
        }
    }
}


?>
