<?php

class CustomDbObject extends DbObject {
    /** string messages in current language (from App) */
    var $messages;

    /** template (from App) */
    var $page;

    /** current language (from App) */
    var $lang;

    function CustomDbObject($class_name) {
        parent::DbObject($class_name);

        $this->messages =& $this->app->messages;
        $this->page =& $this->app->page;
        $this->lang =& $this->app->lang;
    }

    function mysql2app_time($time) {
        list($hour, $minute, $second) = explode(":", $time);
        return "{$hour}.{$minute}.{$second}";
    }

    function app2mysql_time($time) {
        $parts = explode(".", $time);
        
        $hours = "00";
        $minutes = "00";
        $seconds = "00";

        $num_parts = count($parts);
        if ($num_parts >= 1) {
            $hours = $parts[0] % 24;
        }
        if ($num_parts >= 2) {
            $minutes = $parts[1] % 60;
        }
        if ($num_parts == 3) {
            $seconds = $parts[2] % 60;
        }
        return "{$hours}:{$minutes}:{$seconds}";
    }

    /** Multilingual attribute support added */
    function insert_field($field) {
        // adding non-language as virtual field
        if (isset($field["multilingual"]) && $field["multilingual"] == 1) {

            // adding language fields
            $child = $field;
            unset($child["multilingual"]);
            foreach ($this->app->avail_langs as $lang) {
                $child["column"] = $field["column"] . "_{$lang}";
                parent::insert_field($child);
            }

            $field["create"] = 0;
            $field["store"] = 0;
            $field["update"] = 0;
            //$field["read"] = 0;

            $table = isset($field["name"]) ? "" : $this->class_name;

            $current = "{$table}.{$field["column"]}_{$this->app->lang}";
            $default = "{$table}.{$field["column"]}_{$this->app->dlang}";

            $field["select"] = "if ({$current} != '', {$current}, {$default})";
        }

        if (isset($field["table"]) && ($field["table"] != $this->class_name)) {
            $alias = (isset($field["alias"])) ? $field["alias"] : $field["table"];

            $obj = new $this->app->tables[$field["table"]](true);
            $obj_field = $obj->fields[$field["column"]];
            if (
                isset($obj_field["multilingual"]) &&
                $obj_field["multilingual"]
            ) {

                $current = "{$alias}.{$field['column']}_{$this->app->lang}";
                $default = "{$alias}.{$field['column']}_{$this->app->dlang}";

                $field["select"] = "if ({$current} != '', {$current}, {$default})";
            }
        }

        parent::insert_field($field);
    }

    function get_message($name) {
        return $this->messages->value($name);
    }

    function read($fields_to_read = NULL) {
        $field_names = isset($fields_to_read) ?
            $fields_to_read : array_keys($this->fields);

        $this->add_multilingual(&$field_names);

        parent::read($field_names);

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];

            if (!$f["read"]) {
                continue;
            }

            $type  = $f["type"];
            $pname = $this->class_name . "_" . $name;
            $param_value = param($pname);

            if ($type == "date") {
                $value = (isset($param_value) && $param_value != "") ?
                    $this->app2mysql_date($param_value) : "";
                $this->$name = $value;
            }

            if ($type == "time") {
                $value = (isset($param_value) && $param_value != "") ?
                    $this->app2mysql_time($param_value) : "";
                $this->$name = $value;
            }
            $multi = $this->get_multilingual();
            $to_write = array_intersect($field_names, $multi);
            foreach ($to_write as $name) {
                $this->$name = $this->{"{$name}_{$this->app->dlang}"};
            }
        }
    }

    function write($fields_to_write = null) {
        $field_names = isset($fields_to_write) ?
            $fields_to_write : array_keys($this->fields);

        $h = parent::write($field_names);

        reset($field_names);
        while(list($i, $name) = each( $field_names ) ) {
            $f = $this->fields[$name];
            if(!$f["write"]) {
                continue;
            }
            $type  = $f["type"];
            $value = $this->$name;  // !!!

            $pname = $this->class_name . "_" . $name;

            switch($type) {
            case "date":
                if ($value == "0000-00-00") {
                    $value = "";
                } else {
                    $value = $this->mysql2app_date($value);
                }

                $h[$pname] = $value;
                break;
            case "time":
                $value = $this->mysql2app_time($value);
                $h[$pname] = $value;
                break;
            case "integer":
                $h[$pname . "_origin"] = $value;
                if (isset($f["input"]) && $f["input"] == "checkbox") {
                    $message = ($value == 0) ? "no" : "yes";
                    $value = $this->get_message($message);
                    $h[$pname] = $value;
                }
                break;
            }
        }

        return $h;
    }

    function write_form($fields_to_write = null) {
        $h = parent::write_form($fields_to_write);

        $field_names = isset($fields_to_write) ?
            $fields_to_write : array_keys($this->fields);

        $this->add_multilingual(&$field_names);

        reset($field_names);
        while (list($i, $name) = each($field_names)) {
            $f = $this->fields[$name];
            if(!$f["write"]) {
                continue;
            }
            $type = $f["type"];
            $value = isset($this->$name) ? $this->$name : "" ;
            $pname = $this->class_name . "_" . $name;

            $value = htmlspecialchars($value);

            switch($type) {
            case "date":
                if ($value == "0000-00-00" || $value == "") {
                    $value = "";
                } else {
                    $value = $this->mysql2app_date($value);
                }
                $h[$pname] = $value;
                $h[$pname . "_input"] =
                    "<input type=\"{$f['input']}\" " .
                    "name=\"{$pname}\" value=\"{$value}\">";

                break;
            case "time":
                $value = $this->mysql2app_time($value);
                $h[$pname] = $value;
                $h[$pname . "_input"] =
                    "<input type=\"{$f['input']}\" " .
                    "name=\"{$pname}\" value=\"{$value}\">";
                break;
            case "text":
                $h[$pname . "_input"] =
                    "<textarea name=\"{$pname}\" cols=\"60\" rows=\"9\">" .
                    $value . "</textarea>";
            }
        }

        $multi = $this->get_multilingual();
        $to_write = array_intersect($field_names, $multi);
        foreach ($to_write as $name) {
            $value = isset($this->$name) ? $this->$name : "" ;
            $pname = $this->class_name . "_" . $name;

            $lang_inputs = array();
            foreach ($this->app->avail_langs as $lang) {
                $lname = $pname . "_" . $lang;
                $lang_inputs[] =
                    "<tr><th>" .
                    $this->get_message($lang)  .
                    ":</th><td>" . $h[$lname . "_input"] . "</td></tr>\n";
            }
            $h[$pname . "_input"] =
                "<table>\n" . join("", $lang_inputs) . "</table>";
        }
        return $h;
    }

    function store($fields = NULL) {
        $this->change($fields);
        parent::store($fields);
    }

    function update($fields = NULL) {
        $this->change($fields);
        parent::update($fields);
    }

    function change($fields) {
        $field_names = isset($fields) ?
            $fields : array_keys($this->fields);

        $default = $this->app->dlang;

        foreach ($field_names as $name) {
            $f = $this->fields[$name];
            if (isset($f["multilingual"]) && ($f["multilingual"] == 1)) {
                if ($this->$name) {
                    $def_field = $name . "_" . $default;
                    if (!$this->$def_field) {
                        $this->$def_field = $this->$name;
                    }
                }
            }
        }
    }

    function get_multilingual() {
        $res = array();
        foreach ($this->fields as $name => $f) {
            if (isset($f["multilingual"]) && $f["multilingual"] == 1) {
                $res[] = $name;
            }
        }
        return $res;
    }

    function is_multilingual($name) {
        return
            (isset($this->fields[$name]) &&
            isset($this->fields[$name]["multilingual"]) &&
            $this->fields[$name]["multilingual"] == 1);
    }

    function add_multilingual(&$field_names) {
        foreach ($field_names as $field) {
            if ($this->is_multilingual($field)) {
                foreach ($this->app->avail_langs as $lang) {
                    $lname = "{$field}_{$lang}";
                    if (!in_array($lname, $field_names)) {
                        $field_names[] = $lname;
                    }
                }
            }
        }
    }

    function plural_name() {
        return $this->get_message($this->plural_resource_name());
    }

    function singular_name() {
        return $this->get_message($this->singular_resource_name());
    }

    // check reference integrity
    function check_links() {
        $table_link_counters = array();
        foreach ($this->get_links() as $dep_table_name => $dep_table_field) {
            $query = new SelectQuery(array(
                "select" => "id",
                "from"   => get_table_name($dep_table_name),
                "where"  => "{$dep_table_field} = {$this->id}",
            ));

            $n = $this->sql->get_query_num_rows($query);
            if ($n > 0) {
                if (array_key_exists($dep_table_name, $table_link_counters)) {
                    $table_link_counters[$dep_table_name] += $n;
                } else {
                    $table_link_counters[$dep_table_name] = $n;
                }
            }
        }

        $messages = array();
        if (count($table_link_counters) != 0) {
            $dep_objs_data = array();
            foreach ($table_link_counters as $dep_table_name => $n) {
                $dep_obj = $this->app->create_object($dep_table_name);
                $dep_objs_data[] = $dep_obj->quantity_str($n);
            }
            $messages[] = new OA_ErrorStatusMsg(
                "cannot_delete_record_because_it_has_links",
                array(
                    "main_obj_name" => $this->singular_name(),
                    "dep_objs_data" => join(", ", $dep_objs_data),
                )    
            );
        }

        return $messages;
    }

    function write_image($image_id = null, $var_name = "_image") {
        $image_template_text = "";
        $filename = "{$this->class_name}/{$var_name}.html";
        if ($this->page->is_template_exist($filename)) {
            $image = $this->get_image($image_id);
            if (!is_null($image)) {
                $this->page->assign($image->write());
                $image_template_text = $this->page->parse_file($filename);
            }
        }
        return array("{$this->class_name}{$var_name}" => $image_template_text);
    }

    function get_image($image_id = null) {
        if (is_null($image_id)) {
            $image_id = $this->image_id;
        }
        if ($image_id != 0) {
            $image = new Image();
            $image->fetch("image.id = {$image_id}");
            return $image;
        } else {
            return null;
        }
    }

    function del_image($image_id = null) {
        if (is_null($image_id)) {
            $image_id = $this->image_id;    
        }
        if ($image_id != 0) {
            $image = new Image();
            $image->del_where("id = {$image_id}");
        }
    }

    function process_image_upload(
        $image_id_name = "image_id", $input_name = "image_file"
    ) {
        if (Image::was_uploaded($input_name)) {
            $image_id = $this->{$image_id_name};
            $image = $this->get_image($image_id);
            if (is_null($image)) {
                $image = new Image();
            }
            $image->read_uploaded_content($input_name);
            
            if ($image->is_definite()) {
                $image->update();
            } else {
                $image->store();
                $this->{$image_id_name} = $image->id;
            }
        }
    }

    function verify_image_upload($input_name = "image_file") {
        if (!Image::was_uploaded($input_name)) {
            return "";
        }

        switch ($_FILES[$input_name]["error"]) {
            case UPLOAD_ERR_OK:
                $err = "";
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $err = $this->status_message("too_big_filesize");
                break;
            case UPLOAD_ERR_PARTIAL:
                $err = $this->status_message("file_was_not_uploaded_completely");
                break;
            default:
                $err = "";
                break;
        }

        return $err;
    }
}

?>
