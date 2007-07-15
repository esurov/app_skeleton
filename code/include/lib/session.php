<?php

class Session extends AppObject {

    function _init($params) {
        parent::_init($params);

        ini_set(
            "session.use_trans_sid",
            $this->get_config_value("php_session.use_trans_sid", 0)
        );
        ini_set(
            "session.use_cookies",
            $this->get_config_value("php_session.use_cookies", 1)
        );
        ini_set(
            "session.use_only_cookies",
            $this->get_config_value("php_session.use_only_cookies", 1)
        );
        ini_set(
            "session.cache_limiter",
            $this->get_config_value("php_session.cache_limiter", "none")
        );
        ini_set(
            "session.gc_maxlifetime",
            $this->get_config_value("php_session.gc_maxlifetime", 14400)
        );
        session_start();
    }

    function &get_param($name) {
        return $_SESSION[$name];
    }

    function set_param($name, &$value) {
        $_SESSION[$name] =& $value;
    }

    function unset_param($name) {
        if ($this->has_param($name)) {
            unset($_SESSION[$name]);
        }
    }

    function has_param($name) {
        return array_key_exists($name, $_SESSION);
    }

    function clear() {
        $_SESSION = array();
    }

}

class LoginSession extends Session {

    function _init($params) {
        parent::_init($params);
    }

    function &get_login_state() {
        return $this->get_param("login_state");
    }

    function &create_login_state($idle_timeout, $max_timeout, $login_id) {
        $login_state = new SessionLoginState($idle_timeout, $max_timeout, $login_id);
        $this->set_param("login_state", $login_state);
        return $login_state;
    }

    function destroy_login_state() {
        $this->unset_param("login_state");
        $this->destroy_saved_request_params();
    }

    function save_request_params() {
        $this->set_param("saved_request_get_params", $_GET);
        $this->set_param("saved_request_post_params", $_POST);
    }

    function get_saved_request_params() {
        return $this->get_saved_request_post_params() + $this->get_saved_request_get_params();
    }

    function get_saved_request_get_params() {
        return ($this->has_param("saved_request_get_params")) ?
            $this->get_param("saved_request_get_params") :
            array();
    }

    function get_saved_request_post_params() {
        return ($this->has_param("saved_request_post_params")) ?
            $this->get_param("saved_request_post_params") :
            array();
    }

    function destroy_saved_request_params() {
        $this->unset_param("saved_request_get_params");
        $this->unset_param("saved_request_post_params");
    }

}

class SessionLoginState {
    var $state_params; 

    function SessionLoginState($idle_timeout, $max_timeout, $login_id) {
        $this->state_params = array(
            "idle_timeout" => $idle_timeout,
            "max_timeout" => $max_timeout,
            "time_created" => time(),
            "time_updated" => time(),
            "login_id" => $login_id,
        );
    }

    function is_expired() {
        $idle_time_elapsed = time() - $this->state_params["time_updated"];
        $total_time_elapsed = time() - $this->state_params["time_created"];

        $idle_timeout = $this->state_params["idle_timeout"];
        $max_timeout = $this->state_params["max_timeout"];

        $is_expired_by_idle_timeout = ($idle_timeout != 0 && $idle_time_elapsed > $idle_timeout);
        $is_expired_by_max_timeout = ($max_timeout != 0 && $total_time_elapsed > $max_timeout);
                
        return ($is_expired_by_idle_timeout || $is_expired_by_max_timeout);
    }

    function get_login_id() {
        return $this->state_params["login_id"];
    }

    function update() {
        $this->state_params["time_updated"] = time();
    }

}

?>