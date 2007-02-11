<?php

class Session {

    function &get_param($name) {
        return $_SESSION[$name];
    }

    function set_param($name, &$value) {
        $_SESSION[$name] =& $value;
    }

    function unset_param($name) {
        if (Session::has_param($name)) {
            unset($_SESSION[$name]);
        }
    }

    function has_param($name) {
        return array_key_exists($name, $_SESSION);
    }

    function clear() {
        $_SESSION = array();
    }

    function &get_login_state() {
        return Session::get_param("login_state");        
    }

    function &create_login_state($idle_timeout, $max_timeout, $login_id) {
        $login_state = new SessionLoginState($idle_timeout, $max_timeout, $login_id);
        Session::set_param("login_state", $login_state);
        return $login_state;
    }

    function destroy_login_state() {
        Session::unset_param("login_state");
        Session::destroy_saved_request_params();
    }

    function save_request_params() {
        Session::set_param("saved_request_get_params", $_GET);
        Session::set_param("saved_request_post_params", $_POST);
    }

    function get_saved_request_params() {
        return Session::get_saved_request_post_params() + Session::get_saved_request_get_params();
    }

    function get_saved_request_get_params() {
        return (Session::has_param("saved_request_get_params")) ?
            Session::get_param("saved_request_get_params") :
            array();
    }

    function get_saved_request_post_params() {
        return (Session::has_param("saved_request_post_params")) ?
            Session::get_param("saved_request_post_params") :
            array();
    }

    function destroy_saved_request_params() {
        Session::unset_param("saved_request_get_params");
        Session::unset_param("saved_request_post_params");
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