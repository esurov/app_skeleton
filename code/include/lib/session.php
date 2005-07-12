<?php

class Session {
    function &get_param($name) {
        if (Session::has_param($name)) {
            return $_SESSION[$name];
        } else {
            return "";
        }
    }

    function set_param($name, $value) {
        $_SESSION[$name] = $value;
    }

    function unset_param($name) {
        if (Session::has_param($name)) {
            unset($_SESSION[$name]);
        }
    }

    function has_param($name) {
        return isset($_SESSION[$name]);
    }

    function clear() {
        $_SESSION = array();
    }

    function &get_login_state() {
        if (Session::has_param("login_state")) {
            return Session::get_param("login_state");        
        } else {
            return null;
        }
    }

    function create_login_state($idle_timeout, $max_timeout, $login_id) {
        Session::set_param(
            "login_state",
            new SessionLoginState($idle_timeout, $max_timeout, $login_id)
        );
    }

    function destroy_login_state() {
        Session::unset_param("login_state");
    }

    function save_request_data() {
        Session::set_param("auto_login_get_vars", $_GET);
        Session::set_param("auto_login_post_vars", $_POST);
    }

    function restore_request_data() {
        $_GET = Session::get_param("auto_login_get_vars");
        $_POST = Session::get_param("auto_login_post_vars");
        Session::destroy_request_data();
    }

    function has_request_data() {
        return
            Session::has_param("auto_login_get_vars") &&
            Session::has_param("auto_login_post_vars");
    }

    function destroy_request_data() {
        Session::unset_param("auto_login_get_vars");
        Session::unset_param("auto_login_post_vars");
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
        $total_time_elapsed = time() - $this->state_params["time_created"];
        $idle_time_elapsed = time() - $this->state_params["time_updated"];

        $is_expired_by_idle_timeout =
            $total_time_elapsed > $this->state_params["idle_timeout"];
        $is_expired_by_max_timeout =
            $total_time_elapsed > $this->state_params["max_timeout"];
        $is_expired = ($is_expired_by_idle_timeout || $is_expired_by_max_timeout);

        if (!$is_expired) {
            $this->state_params["time_updated"] = time();
        }
        return $is_expired;
    }

    function get_login_id() {
        return $this->state_params["login_id"];
    }
}

?>