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

    function expand($q) {
        // Add more statements to the clauses using given array
        
        if (isset($q["distinct"])) {
            $this->distinct = $q["distinct"];
        }
        $this->select .= isset($q["select"]) ? " ,   $q[select] " : "";
        $this->from   .= isset($q["from"  ]) ? "     $q[from]   " : "";
        $this->where  .= isset($q["where" ]) ? " AND $q[where]  " : "";
        $this->limit   = isset($q["limit" ]) ? "     $q[limit]  " : "";

        $qwote = empty($this->order_by) ? "" : ", ";
        $this->order_by .= isset($q["order_by"]) ? $qwote . " $q[order_by]" : "";

        $qwote = empty($this->group_by) ? "" : ", ";
        $this->group_by .= isset($q["group_by"]) ? $qwote . " $q[group_by]" : "";

        $qwote = empty($this->having) ? "" : " AND ";
        $this->having .= isset($q["having"]) ? $qwote . "$q[having]" : "";
    }
}

?>