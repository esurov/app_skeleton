<?php

class OrderedDbObject extends CustomDbObject {
    
    function _init($params) {
        parent::_init($params);

        $this->insert_field(array(
            "field" => "position",
            "type" => "integer",
            "value" => 0,
            "read" => 0,
            "update" => 0,
            "index" => "index",
        ));
    }
//
    function create_table($fake_run, &$run_info) {
        $this->_move_position_field_to_end();

        parent::create_table($fake_run, &$run_info);
    }

    function update_table($fake_run, &$run_info) {
        $this->_move_position_field_to_end();

        parent::update_table($fake_run, &$run_info);
    }

    function _move_position_field_to_end() {
        $position_field_info = $this->_fields["position"];
        unset($this->_fields["position"]);
        $this->_fields["position"] = $position_field_info;
    }
//
    function store(
        $field_names_to_store = null,
        $field_names_to_not_store = null
    ) {
        $last_position = $this->fetch_last_db_object_position($this->get_position_where_str());
        $this->position = $last_position + 1;

        parent::store($field_names_to_store, $field_names_to_not_store);
    }

    function update_position($update_position_to) {
        if (!$this->is_definite()) {
            return;
        }

        $neighbor_obj =& $this->fetch_neighbor_db_object(
            $update_position_to,
            $this->get_position_where_str()
        );
        if (is_null($neighbor_obj)) {
            return;
        }
        $tmp_position = $this->position;
        $this->position = $neighbor_obj->position;
        $neighbor_obj->position = $tmp_position;

        $this->update(array("position"));
        $neighbor_obj->update(array("position"));
    }

    // Should be redefined in child class if custom filter is required
    function get_position_where_str() {
        return "1";
    }

    function fetch_last_db_object_position($where_str = "1") {
        $query = new SelectQuery(array(
            "select" => "IFNULL(MAX(position), 0) as last_position",
            "from" => "{%{$this->_table_name}_table%}",
            "where" => $where_str,
        ));
        $res = $this->run_select_query($query);
        $row = $res->fetch_next_row();
        return $row["last_position"];
    }

    function fetch_prev_db_object_position($where_str = "1") {
        $query = new SelectQuery(array(
            "select" => "IFNULL(MAX(position), 0) AS prev_position",
            "from" => "{%{$this->_table_name}_table%}",
            "where" => "position < {$this->position} AND {$where_str}",
        ));
        $res = $this->run_select_query($query);
        $row = $res->fetch_next_row();
        return $row["prev_position"];
    }

    function fetch_next_db_object_position($where_str = "1") {
        $query = new SelectQuery(array(
            "select" => "IFNULL(MIN(position), 0) AS next_position",
            "from" => "{%{$this->_table_name}_table%}",
            "where" => "position > {$this->position} AND {$where_str}",
        ));
        $res = $this->run_select_query($query);
        $row = $res->fetch_next_row();
        return $row["next_position"];
    }

    function &fetch_neighbor_db_object($neighbor_type, $where_str = "1") {
        if ($neighbor_type == "prev") {
            $neighbor_obj_position = $this->fetch_prev_db_object_position($where_str);
        } else {
            $neighbor_obj_position = $this->fetch_next_db_object_position($where_str);
        }

        $neighbor_obj = null;
        if ($neighbor_obj_position != 0) {
            $neighbor_obj =& $this->create_db_object($this->get_table_class_name());
            if (!$neighbor_obj->fetch("position = {$neighbor_obj_position} AND {$where_str}")) {
                $neighbor_obj = null;
            }
        }
        return $neighbor_obj;
    }

}

?>