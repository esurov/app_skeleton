<?php

class TestApp extends CustomApp {
    function TestApp($tables) {
        parent::CustomApp("test", $tables);

        $e = array("valid_users" => array("everyone"));

        $this->actions = array(
            "pg_index" => $e,
        );
    }
//
    function pg_index() {
        $t = new Example();
//        $t->store();
        $query = $t->get_select_query();
var_dump($query);
        $this->db->run_select_query($query);

        $t->run_expanded_select_query(array(
            "order_by" => "id ASC",
        ));
        exit;
    }
}

?>