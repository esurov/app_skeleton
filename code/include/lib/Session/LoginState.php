<?php

class OA_LoginState {
    var $stateParams; 

    function OA_LoginState($idleTimeout, $maxTimeout, $loginId) {
        $this->stateParams = array(
            "idle_timeout" => $idleTimeout,
            "max_timeout" => $maxTimeout,
            "time_created" => time(),
            "time_updated" => time(),
            "login_id" => $loginId,
        );
    }

    function isExpired() {
        $totalTimeElapsed = time() - $this->stateParams["time_created"];
        $idleTimeElapsed = time() - $this->stateParams["time_updated"];

        $isExpiredByIdleTimeout = $idleTimeElapsed > $this->stateParams["idle_timeout"];
        $isExpiredByMaxTimeout = $totalTimeElapsed > $this->stateParams["max_timeout"];
        $isExpired = ($isExpiredByIdleTimeout || $isExpiredByMaxTimeout);

        if (!$isExpired) {
            $this->stateParams["time_updated"] = time();
        }
        return $isExpired;
    }

    function getLoginId() {
        return $this->stateParams["login_id"];
    }
}

?>
