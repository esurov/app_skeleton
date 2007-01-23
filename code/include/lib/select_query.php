<?php

class SelectQueryEx {

    var $distinct;
    var $select;
    var $from;
    var $where;
    var $group_by;
    var $order_by;
    var $limit;

    function SelectQueryEx($q = array()) {
        $this->distinct = isset($q["distinct"]) ? $q["distinct"] : false;
        $this->select   = isset($q["select"  ]) ? $q["select"  ] : "";
        $this->from     = isset($q["from"    ]) ? $q["from"    ] : "";
        $this->where    = isset($q["where"   ]) ? $q["where"   ] : "";
        $this->group_by = isset($q["group_by"]) ? $q["group_by"] : "";
        $this->having   = isset($q["having"  ]) ? $q["having"  ] : "";
        $this->order_by = isset($q["order_by"]) ? $q["order_by"] : "";
        $this->limit    = isset($q["limit"   ]) ? $q["limit"   ] : "";
    }

    // Expand SQL select query clauses
    function expand($query_ex) {
        if (is_array($query_ex)) {

            // Expand from array with keys-clauses
            if (isset($query_ex["distinct"])) {
                $this->distinct = $query_ex["distinct"];
            }
            
            if (isset($query_ex["select"]) && is_value_not_empty($query_ex["select"])) {
                if (is_value_empty($this->select)) {
                    $this->select = $query_ex["select"];
                } else {
                    $this->select .= ", " . $query_ex["select"];
                }
            }
            
            if (isset($query_ex["from"]) && is_value_not_empty($query_ex["from"])) {
                if (is_value_empty($this->from)) {
                    $this->from = $query_ex["from"];
                } else {
                    $this->from .= " " . $query_ex["from"];
                }
            }
            
            if (isset($query_ex["where"]) && is_value_not_empty($query_ex["where"])) {
                if (is_value_empty($this->where)) {
                    $this->where = $query_ex["where"];
                } else {
                    $this->where .= " AND " . $query_ex["where"];
                }
            }
            
            if (isset($query_ex["group_by"]) && is_value_not_empty($query_ex["group_by"])) {
                if (is_value_empty($this->group_by)) {
                    $this->group_by = $query_ex["group_by"];
                } else {
                    $this->group_by .= ", " . $query_ex["group_by"];    
                }
            }
            
            if (isset($query_ex["having"]) && is_value_not_empty($query_ex["having"])) {
                if (is_value_empty($this->having)) {
                    $this->having = $query_ex["having"];
                } else {
                    $this->having .= " AND " . $query_ex["having"];
                }
            }
            
            if (isset($query_ex["order_by"]) && is_value_not_empty($query_ex["order_by"])) {
                if (is_value_empty($this->order_by)) {
                    $this->order_by = $query_ex["order_by"];
                } else {
                    $this->order_by .= ", " . $query_ex["order_by"];
                }
            }
            
            if (isset($query_ex["limit"]) && is_value_not_empty($query_ex["limit"])) {
                $this->limit = $query_ex["limit"];
            }
        } else {

            // Expand from SelectQueryEx object
            $this->distinct = $query_ex->distinct;
            
            if (is_value_not_empty($query_ex->select)) {
                if (is_value_empty($this->select)) {
                    $this->select = $query_ex->select;
                } else {
                    $this->select .= ", " . $query_ex->select;
                }
            }
            
            if (is_value_not_empty($query_ex->from)) {
                if (is_value_empty($this->from)) {
                    $this->from = $query_ex->from;
                } else {
                    $this->from .= " " . $query_ex->from;
                }
            }
            
            if (is_value_not_empty($query_ex->where)) {
                if (is_value_empty($this->where)) {
                    $this->where = $query_ex->where;
                } else {
                    $this->where .= " AND " . $query_ex->where;
                }
            }

            if (is_value_not_empty($query_ex->group_by)) {
                if (is_value_empty($this->group_by)) {
                    $this->group_by = $query_ex->group_by;
                } else {
                    $this->group_by .= ", " . $query_ex->group_by;
                }
            }
                
            if (is_value_not_empty($query_ex->having)) {
                if (is_value_empty($this->having)) {
                    $this->having = $query_ex->having;
                } else {
                    $this->having .= " AND " . $query_ex->having;
                }
            }
                
            if (is_value_not_empty($query_ex->order_by)) {
                if (is_value_empty($this->order_by)) {
                    $this->order_by = $query_ex->order_by;
                } else {
                    $this->order_by .= ", " . $query_ex->order_by;
                }
            }    
            
            if (is_value_not_empty($query_ex->limit)) {
                $this->limit = $query_ex->limit;
            }
        }
    }

}

class SelectQuery extends SelectQueryEx {

    function SelectQuery($q = array()) {
        parent::SelectQueryEx($q);
    }

    // Return complete query string assembled from clauses
    function get_string() {
        $distinct_str = ($this->distinct) ? "DISTINCT " : "";
        return
            "SELECT {$distinct_str}{$this->select}" .
            "\n  FROM {$this->from}" .
            ((is_value_empty($this->where))    ? "" : "\n  WHERE {$this->where}") .
            ((is_value_empty($this->group_by)) ? "" : "\n  GROUP BY {$this->group_by}") .
            ((is_value_empty($this->having))   ? "" : "\n  HAVING {$this->having}") .
            ((is_value_empty($this->order_by)) ? "" : "\n  ORDER BY {$this->order_by}") .
            ((is_value_empty($this->limit))    ? "" : "\n  LIMIT {$this->limit}");
    }

}

?>