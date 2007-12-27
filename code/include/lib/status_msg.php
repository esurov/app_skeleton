<?php

class StatusMsg {

    var $type;
    var $resource;
    var $resource_params;

    function StatusMsg($type, $resource, $resource_params = array()) {
        $this->type = $type;
        $this->resource = "status_msg.{$resource}";
        $this->resource_params = $resource_params;
    }

}

class OkStatusMsg extends StatusMsg {

    function OkStatusMsg($resource, $resource_params = array()) {
        parent::StatusMsg("ok", $resource, $resource_params);
    }

}

class ErrorStatusMsg extends StatusMsg {

    function ErrorStatusMsg($resource, $resource_params = array()) {
        parent::StatusMsg("err", $resource, $resource_params);
    }

}

class NotifyStatusMsg extends StatusMsg {

    function NotifyStatusMsg($resource, $resource_params = array()) {
        parent::StatusMsg("notify", $resource, $resource_params);
    }

}


?>