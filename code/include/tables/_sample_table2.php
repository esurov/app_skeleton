<?php

class SampleTable2 extends SampleTable {

    function init($params) {
        parent::init($params);

        $this->insert_field(array(
            "field" => "another_id",
            "type" => "foreign_key",
        ));
    }

}

?>