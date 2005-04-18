<?php

class CustomApp extends SqlApp {
    var $lang;
    var $dlang;
    var $avail_langs;

    function CustomApp($tables) {
        parent::SqlApp("app_skeleton", $tables);

        $this->avail_langs = $this->get_avail_langs();
        $this->dlang = $this->config->value("default_language");
        $this->lang = $this->get_current_lang();

        $this->messages = new Config();
        $this->messages->ignore_comments = false;
        $this->messages->read("lang/default.txt");
        $this->messages->read("lang/{$this->lang}.txt");
    }

    function get_current_lang() {
        $cur_lang = Session::get_param("current_language");
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
            Session::set_param("current_language", $new_lang);
        }
    }

    function add_session_status_message($new_msg) {
        if (Session::has_param("status_messages")) {
            $old_msgs = Session::get_param("status_messages");
        } else {
            $old_msgs = array();
        }
        $msgs = array_merge($old_msgs, array($new_msg));
        Session::set_param("status_messages", $msgs);
    }

    function get_and_delete_session_status_messages() {
        if (!Session::has_param("status_messages")) {
            return array();
        } else {
            $msgs = Session::get_param("status_messages");
            Session::unset_param("status_messages");
            return $msgs;
        }
    }
}


?>