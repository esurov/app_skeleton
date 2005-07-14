<?php

class SelectQuery {

    var $distinct;
    var $select;
    var $from;
    var $where;
    var $group_by;
    var $order_by;
    var $limit;

    function SelectQuery($q) {
        $this->distinct = isset($q["distinct"]) ? $q["distinct"] : false;
        $this->select   = isset($q["select"  ]) ? $q["select"  ] : "*";
        $this->from     = isset($q["from"    ]) ? $q["from"    ] : "" ;
        $this->where    = isset($q["where"   ]) ? $q["where"   ] : "1";
        $this->group_by = isset($q["group_by"]) ? $q["group_by"] : "" ;
        $this->having   = isset($q["having"  ]) ? $q["having"  ] : "" ;
        $this->order_by = isset($q["order_by"]) ? $q["order_by"] : "" ;
        $this->limit    = isset($q["limit"   ]) ? $q["limit"   ] : "" ;
    }

    function get_string() {
        // Return complete query string assembled from clauses
        $distinct_str = ($this->distinct) ? "DISTINCT " : "";
        return
            "SELECT {$distinct_str}{$this->select} " .
            "FROM {$this->from} " .
            "WHERE {$this->where}" .
            ($this->group_by ? " GROUP BY {$this->group_by}" : "") .
            ($this->having   ? " HAVING {$this->having}"     : "") .
            ($this->order_by ? " ORDER BY {$this->order_by}" : "") .
            ($this->limit    ? " LIMIT {$this->limit}"       : "");
    }

    function expand($query_ex) {
        // Add more statements to the clauses using given array
        if (isset($query_ex["distinct"])) {
            $this->distinct = $query_ex["distinct"];
        }
        if (isset($query_ex["select"])) {
            $this->select .= ", " . $query_ex["select"];
        }
        if (isset($query_ex["from"])) {
            $this->from .= " " . $query_ex["from"];
        }
        if (isset($query_ex["where"])) {
            $this->where .= " AND " . $query_ex["where"];
        }
        if (isset($query_ex["group_by"]) && !empty($this->group_by)) {
            $this->group_by .= " " . $query_ex["group_by"];
        }
        if (isset($query_ex["having"]) && !empty($this->having)) {
            $this->having .= " AND " . $query_ex["having"];
        }
        if (isset($query_ex["order_by"]) && !empty($this->order_by)) {
            $this->order_by .= ", " . $query_ex["order_by"];
        }
        if (isset($query_ex["limit"])) {
            $this->limit = $query_ex["limit"];
        }
    }
}

?>