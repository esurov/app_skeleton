<?php

class OA_StatusMsg {
    var $type;
    var $resource;
    var $resource_params;

    function OA_StatusMsg($type, $resource, $resource_params = array()) {
        $this->type = $type;
        $this->resource = $resource;
        $this->resource_params = $resource_params;
    }
}

class OA_OkStatusMsg extends OA_StatusMsg {
    function OA_OkStatusMsg($resource, $resource_params = array()) {
        parent::OA_StatusMsg("ok", $resource, $resource_params);
    }
}

class OA_ErrorStatusMsg extends OA_StatusMsg {
    function OA_ErrorStatusMsg($resource, $resource_params = array()) {
        parent::OA_StatusMsg("err", $resource, $resource_params);
    }
}

?>