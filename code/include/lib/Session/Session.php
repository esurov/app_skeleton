<?php

class OA_Session {
    function &getParam($name) {
        if (OA_Session::hasParam($name)) {
            return $_SESSION[$name];
        } else {
            return "";
        }
    }

    function setParam($name, $value) {
        $_SESSION[$name] = $value;
    }

    function unsetParam($name) {
        if (OA_Session::hasParam($name)) {
            unset($_SESSION[$name]);
        }
    }

    function hasParam($name) {
        return array_key_exists($name, $_SESSION);
    }

    function clear() {
        $_SESSION = array();
    }

    function &getLoginState() {
        if (OA_Session::hasParam("login_state")) {
            return OA_Session::getParam("login_state");        
        } else {
            return null;
        }
    }

    function createLoginState($idleTimeout, $maxTimeout, $loginId) {
        OA_Session::setParam(
            "login_state", new OA_LoginState($idleTimeout, $maxTimeout, $loginId)
        );
    }

    function destroyLoginState() {
        OA_Session::unsetParam("login_state");
    }
}

?>
