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

?>