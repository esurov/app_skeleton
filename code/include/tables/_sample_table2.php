<?php

class SampleTable2 extends SampleTable {

    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "another_id",
            "type" => "foreign_key",
        ));
    }

}

?>