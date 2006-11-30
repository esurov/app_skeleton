<?php

class SelectQuery {

    var $distinct;
    var $select;
    var $from;
    var $where;
    var $group_by;
    var $order_by;
    var $limit;

    function SelectQuery($q = array()) {
        $this->distinct = isset($q["distinct"]) ? $q["distinct"] : false;
        $this->select   = isset($q["select"  ]) ? $q["select"  ] : "";
        $this->from     = isset($q["from"    ]) ? $q["from"    ] : "";
        $this->where    = isset($q["where"   ]) ? $q["where"   ] : "";
        $this->group_by = isset($q["group_by"]) ? $q["group_by"] : "";
        $this->having   = isset($q["having"  ]) ? $q["having"  ] : "";
        $this->order_by = isset($q["order_by"]) ? $q["order_by"] : "";
        $this->limit    = isset($q["limit"   ]) ? $q["limit"   ] : "";
    }

    function get_string() {
        // Return complete query string assembled from clauses
        $distinct_str = ($this->distinct) ? "DISTINCT " : "";
        return
            "SELECT {$distinct_str}{$this->select} " .
            "FROM {$this->from}" .
            ($this->where    ? " WHERE {$this->where}"       : "") .
            ($this->group_by ? " GROUP BY {$this->group_by}" : "") .
            ($this->having   ? " HAVING {$this->having}"     : "") .
            ($this->order_by ? " ORDER BY {$this->order_by}" : "") .
            ($this->limit    ? " LIMIT {$this->limit}"       : "");
    }

    function expand($query_ex) {
        // Add more statements to the clauses using given array or SelectQuery object
        if (is_array($query_ex)) {
            if (isset($query_ex["distinct"])) {
                $this->distinct = $query_ex["distinct"];
            }
            
            if (!empty($query_ex["select"])) {
                if (empty($this->select)) {
                    $this->select = $query_ex["select"];
                } else {
                    $this->select .= ", " . $query_ex["select"];
                }
            }
            
            if (!empty($query_ex["from"])) {
                if (empty($this->from)) {
                    $this->from = $query_ex["from"];
                } else {
                    $this->from .= " " . $query_ex["from"];
                }
            }
            
            if (!empty($query_ex["where"])) {
                if (empty($this->where)) {
                    $this->where = $query_ex["where"];
                } else {
                    $this->where .= " AND " . $query_ex["where"];
                }
            }
            
            if (!empty($query_ex["group_by"])) {
                if (empty($this->group_by)) {
                    $this->group_by = $query_ex["group_by"];
                } else {
                    $this->group_by .= ", " . $query_ex["group_by"];    
                }
            }
            
            if (!empty($query_ex["having"])) {
                if (empty($this->having)) {
                    $this->having = $query_ex["having"];
                } else {
                    $this->having .= " AND " . $query_ex["having"];
                }
            }
            
            if (!empty($query_ex["order_by"])) {
                if (empty($this->order_by)) {
                    $this->order_by = $query_ex["order_by"];
                } else {
                    $this->order_by .= ", " . $query_ex["order_by"];
                }
            }
            
            if (!empty($query_ex["limit"])) {
                $this->limit = $query_ex["limit"];
            }
        } else {
            $this->distinct = $query_ex->distinct;
            if (!empty($query_ex->select)) {
                if (empty($this->select)) {
                    $this->select = $query_ex->select;
                } else {
                    $this->select .= ", " . $query_ex->select;
                }
            }
            
            if (empty($this->from)) {
                $this->from = $query_ex->from;
            } else {
                $this->from .= " " . $query_ex->from;
            }
            
            if (!empty($query_ex->where)) {
                if (empty($this->where)) {
                    $this->where = $query_ex->where;
                } else {
                    $this->where .= " AND " . $query_ex->where;
                }
            }

            if (!empty($query_ex->group_by)) {
                if (empty($this->group_by)) {
                    $this->group_by = $query_ex->group_by;
                } else {
                    $this->group_by .= ", " . $query_ex->group_by;
                }
            }
                
            if (!empty($query_ex->having)) {
                if (empty($this->having)) {
                    $this->having = $query_ex->having;
                } else {
                    $this->having .= " AND " . $query_ex->having;
                }
            }
                
            if (!empty($query_ex->order_by)) {
                if (empty($this->order_by)) {
                    $this->order_by = $query_ex->order_by;
                } else {
                    $this->order_by .= ", " . $query_ex->order_by;
                }
            }    
            
            if (!empty($query_ex->limit)) {
                $this->limit = $query_ex->limit;
            }
        }
    }

}

?>