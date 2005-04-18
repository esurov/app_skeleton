<?php

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