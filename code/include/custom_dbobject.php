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

        global $app;
        $this->messages =& $app->messages;
        $this->page =& $app->page;
        $this->lang =& $app->lang;
    }

    function mysql2app_time($time) {
        list($hour, $minute, $second) = explode(":", $time);
        return "{$hour}.{$minute}.{$second}";
    }

    function app2mysql_time($time) {
        $parts = explode(".", $time);
        $second = "00";
        if (count($parts) >= 2) {
            $hour = $parts[0];
            $minute = $parts[1];
        }
        if (count($parts) == 3) {
            $second = $parts[2];
        } else {
            return "00:00:00";
        }
        return "{$hour}:{$minute}:{$second}";
    }

    /** Multilingual attribute support added */
    function insert_field($field) {
        global $app;

        // adding non-language as virtual field
        if (isset($field["multilingual"]) && $field["multilingual"] == 1) {

            // adding language fields
            $child = $field;
            unset($child["multilingual"]);
            foreach ($app->avail_langs as $lang) {
                $child["column"] = $field["column"] . "_{$lang}";
                parent::insert_field($child);
            }

            $field["create"] = 0;
            $field["store"] = 0;
            $field["update"] = 0;
            //$field["read"] = 0;

            $table = isset($field["name"]) ? "" : $this->class_name;

            $current = "{$table}.{$field["column"]}_{$app->lang}";
            $default = "{$table}.{$field["column"]}_{$app->dlang}";

            $field["select"] = "if ({$current} != '', {$current}, {$default})";
        }

        if (isset($field["table"]) && ($field["table"] != $this->class_name)) {
            $alias = (isset($field["alias"])) ? $field["alias"] : $field["table"];

            $obj = new $app->tables[$field["table"]](true);
            $obj_field = $obj->fields[$field["column"]];
            if (
                isset($obj_field["multilingual"]) &&
                $obj_field["multilingual"]
            ) {

                $current = "{$alias}.{$field['column']}_{$app->lang}";
                $default = "{$alias}.{$field['column']}_{$app->dlang}";

                $field["select"] = "if ({$current} != '', {$current}, {$default})";
            }
        }

        parent::insert_field($field);
    }

    function status_message($msg_name, $vars = array()) {
        $msg_text = $this->get_message($msg_name);
        $this->page->assign(array(
            "text_of_message" => "",
        ));
        $this->page->assign($vars);

        $this->page->parse_text($msg_text, "text_of_message");
        return $this->page->parse_file("status_message.html");
    }

    function get_message($name) {
        return $this->messages->value($name);
    }

    function read($fields_to_read = NULL) {
        $field_names = isset($fields_to_read) ?
            $fields_to_read : array_keys($this->fields);

        $this->add_multilingual(&$field_names);

        parent::read($field_names);

        global $app;

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
                $this->$name = $this->{"{$name}_{$app->dlang}"};
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
        global $app;

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
            foreach ($app->avail_langs as $lang) {
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

        global $app;
        $default = $app->dlang;

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
        global $app;
        foreach ($field_names as $field) {
            if ($this->is_multilingual($field)) {
                foreach ($app->avail_langs as $lang) {
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
}

?>
