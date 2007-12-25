<?php

// Class for custom functionality of all DbObject-derived classes
class CustomDbObject extends DbObject {

    function store($context = null, $context_params = array()) {
        if ($this->is_field_exist("created")) {
            $this->created = $this->app->get_db_now_datetime();
        }
        if ($this->is_field_exist("updated")) {
            $this->updated = $this->app->get_db_now_datetime();
        }

        parent::store($context, $context_params);
    }

    function update($context = null, $context_params = array()) {
        if ($this->is_field_exist("updated")) {
            $this->updated = $this->app->get_db_now_datetime();
        }

        parent::update($context, $context_params);
    }

}

?>