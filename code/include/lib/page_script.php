<?php

class PageScript {
    // Script that generates pages -- base class.
    // Also provides user validation and action selection.

    var $page;
    var $messages;

    var $script_name;

    var $actions;

    var $action;
    var $self_action;

    // From App
    var $app;
    var $config;
    var $log;
    var $sql;

    function PageScript($script_name, $templates_dir = 'templates') {
        // Constructor.

        global $app;

        $this->script_name = $script_name;

        if (!isset($app)) {
            die('App object is not found!');
        }

        $this->app = &$app;

        $this->config = $app->config;
        $this->log    = $app->log;
        $this->sql    = $app->sql;  // ?

        // Create template for HTML pages:
        $t_dir = $templates_dir;
        $template_type = $this->config->value('template_type');
        $print_template_name = $this->config->value('print_template_name');
        if ($template_type != '') {
            $t_dir .= "/$template_type";
        }
        $t_dir .= "/{$this->script_name}";
        $this->page = new Template($t_dir, $print_template_name);

        // Assign predefined variables to page template.
        $this->page->assign(array(
            'self' => $_SERVER['PHP_SELF'],
        ));

        // Read messages:
        $this->messages = new Config();
        $this->messages->read("{$templates_dir}/messages.txt");
        $custom_messages_file = "$t_dir/debug.cfg";
        if (file_exists($custom_messages_file)) {
            $this->messages->read($custom_messages_file);
        }

        // Read pager defaults from config:
        $default_rows = $this->config->value("{$this->script_name}_default_rows");
        $max_rows     = $this->config->value("{$this->script_name}_max_rows");
        if (isset($default_rows) && isset($max_rows)) {
            $this->pager = new Pager($this, $default_rows, $max_rows);
        }

        // One action defined, but nobody can access it:
        $actions = array(
            'pg_index' => array(
                'valid_users' => array(),
            ),
        );
    }

    function run() {
        // Read from CGI and run appropriate action.

        // Decide what to do:
        $action = param('action');
        $this->log->write('PageScript', "action = '$action'", 3);

        // Ensure that action is valid:
        if (isset($this->actions[$action])) {
            $this->action = $action;

        } else {
            if ($action != '') {
                $this->log->write('PageScript', "Warning! Invalid action.", 1);
            }
            $this->action = 'pg_index';
        }

        $this->create_current_user();
        // Ensure that current user is allowed to run this action:
        // Check user permission level:
        $user_level   = $this->get_user_access_level();
        $valid_levels = $this->actions[$this->action]['valid_users'];
        if (in_array($user_level, $valid_levels)) {
            $this->run_action();
        } else {
            $this->log->write('WebApp', "Warning! Access denied for '$user_level'", 1);
            $this->pg_access_denied();
        }

        // Print page:
        $this->print_page();
    }

    function create_current_user() {
    }

    function get_user_access_level() {
        // Return user access level (string) for selecting allowed actions.
        return "everyone";
    }

    function run_action() {
        // Access is granted.
        $this->{$this->action}();  // NB! Variable function.
    }

    function print_page() {
        // Print resulting page using template.
        echo $this->page->parse_file('page.html');
    }

    function get_self_action() {
        // Generage URL to be used as self-action (resulting the same page).
        return "$_SERVER[PHP_SELF]?action={$this->action}";
    }

    function get_message($name) {
        // Return text of predefined message.
        return $this->messages->value($name);
    }

    function print_message($msg_name, $msg_type = NULL) {
        // Parse given message and store text to given template variable.

        $msg_text = $this->get_message($msg_name);

        $this->page->assign(array(
            'text_of_message' => '',
        ));
        $this->page->parse_text($msg_text, 'text_of_message');
        $this->page->parse_file('message.html', $msg_type);
    }


    // Common actions:

    function pg_index() {
        // Print index page.

        $this->page->assign(array(
            'title' => '',
        ));
    }


    function pg_access_denied() {
        // Print "access denied" page.

        $this->page->assign(array(
            'title' => 'Access denied!',
        ));
    }


    // View object functions:

    function create_view_object_page_title($obj) {
        $singular_name = $obj->singular_name();
        $this->page->assign(array(
            'title' => "View {$singular_name} info",
        ));
    }

    function create_edit_object_page_title($obj) {
        $singular_name = $obj->singular_name();
        if ($obj->is_definite()) {
            $operation = "Edit";
            $info = " info";
        } else {
            $operation = "Add";
            $info = "";
        }
        $this->page->assign(array(
            'title' => "{$operation} {$singular_name}{$info}",
        ));
    }

    function create_view_several_objects_page_title($obj) {
        $plural_name = $obj->plural_name();
        $this->page->assign(array(
            'title' => "View {$plural_name}",
        ));
    }

    function pg_view_object(
        $name, $aspect, $where_str,
        $default_order_by = NULL, $show_search_form = false
    ) {
        // Print page to view info about object(s).

        $obj = $this->app->read_id_fetch_object($name, $aspect, $where_str);

        if ($obj->is_definite()) {
            // Print one object:
            $this->print_view_object_page($obj, $aspect);
        } else {
            // Print table with all objects:
            $this->print_view_several_objects_page(
                $name, $aspect, $where_str, $default_order_by, $show_search_form);
        }
    }

    function print_view_object_page($obj, $aspect) {
        // Print page for viewing object info.

        $name   = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        // print info table:
        $this->page->assign($obj->write());
        $this->page->parse_file("{$prefix}info.html", "text_{$name}_info");

        // print page:
        $this->create_view_object_page_title($obj);
        $this->page->parse_file("{$prefix}view.html",  'body');
    }


    function pg_edit_object($name, $aspect, $where_str) {
        // Print page for editing (modifying) object.

        // Read existing object:
        $obj = $this->app->read_id_fetch_object($name, $aspect, $where_str);

        $this->print_edit_object_page($obj, $aspect);
    }


    function update_object(
        $name, $aspect, $where_str,
        $default_order_by = NULL, $show_search_form = false,
        $next_action = null, $fields_to_update = NULL
    ) {
        // Check entered data and add/update object info in database if ok.

        $old_obj = $this->app->read_id_fetch_object($name, $aspect, $where_str);

        $this->update_existing_object(
            $old_obj, $aspect, $where_str,
            $default_order_by, $show_search_form,
            $next_action, $fields_to_update
        );
    }


    function update_existing_object(
        &$obj, $aspect, $where_str,
        $default_order_by = NULL, $show_search_form = false,
        $next_action = null, $fields_to_update = NULL
    ) {
        // Check entered data and update given object in database if ok.

        $name   = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        // Read and verify new object:
        $old_obj = $obj;
        $obj->read($fields_to_update);

        $messages = $obj->verify($old_obj);

        if (count($messages) != 0) { // error detected
            // write to log
            $this->log->write('PageScript', "update '$name' - verify error", 3);

            $this->print_status_messages($messages);

            $this->print_edit_object_page($obj, $aspect);

        } else {  // no errors
            if ($old_obj->is_definite()) {
                // update existing object:
                $obj->update($fields_to_update);
                $obj->fetch();
                $this->finish_update_object(
                    $obj, $aspect, $where_str,
                    $default_order_by, $show_search_form, $next_action
                );

            } else {
                // add new object:
                $obj->store();
                $obj->fetch();
                $this->finish_add_object(
                    $obj, $aspect, $where_str,
                    $default_order_by, $show_search_form, $next_action
                );
            }
        }
    }


    function finish_add_object(
        $obj, $aspect, $where_str, $default_order_by, $show_search_form, $next_action
    ) {
        // Print page after successful object addition.

        $name   = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        $this->print_message("{$prefix}added", 'ok');

        if (is_null(param('continue_editing'))) {
            $this->print_view_object_page($obj, $aspect);

        } else {
            $this->print_edit_object_page($obj, $aspect);
        }
    }


    function finish_update_object(
        $obj, $aspect, $where_str, $default_order_by, $show_search_form, $next_action
    ) {
        // Print page after successful object update.

        $name   = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        $this->print_message("{$prefix}updated", 'ok');

        if (is_null(param('continue_editing'))) {
            $this->print_view_object_page($obj, $aspect);

        } else {
            $this->print_edit_object_page($obj, $aspect);
        }
    }


    function pg_delete_object($name, $aspect, $where_str) {
        // Print page for deleting object.

        // Read existing object:
        $obj = $this->app->read_id_fetch_object($name, $aspect, $where_str);

        $this->print_delete_object_page($obj, $aspect, $where_str);
    }


    function delete_object(
        $name, $aspect, $where_str,
        $default_order_by = NULL,  $show_search_form = false,
        $next_action = null, $cascading = false
    ) {
        // Delete object

        // Read existing object
        $obj = $this->app->read_id_fetch_object($name, $aspect, $where_str);
        if (!$obj->is_definite()) {
            // FIXME: add "object not found" page here.
            self_redirect("?action=pg_view_{$name}");  // UGLY
        }

        $this->delete_existing_object(
            $obj, $aspect, $where_str, $default_order_by, $show_search_form, $next_action, $cascading
        );
    }


    function delete_existing_object(
        &$obj, $aspect, $where_str,
        $default_order_by = NULL, $show_search_form = false,
        $next_action = null, $cascading = false
    ) {
        // Delete object

        $name   = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        $pr_key_name  = $obj->primary_key_name();
        $pr_key_value = $obj->primary_key_value();

        if (param( 'sure') != '1' ) {  // if not sure -- do nothing
            self_redirect(
                "?action=pg_view_{$name}&{$name}_{$pr_key_name}={$pr_key_value}");
        }

        $messages = $obj->check_links();  // check reference integrity
        if (count($messages) != 0) {
            $this->error_delete_object_after_check_links($obj, $next_action, $messages);
        } else {
            $n = 1;
            if ($cascading) {
                $obj->del_cascading();
            } else {
                $n = $obj->del();
            }
            $this->page->assign(array(
                'n_items' => $obj->quantity_str($n),
            ));
            if ($n == 1) {
                // Object becomes indefinite.
                $obj->set_indefinite();
            }
            $this->finish_delete_object(
                $obj, $aspect, $where_str, $default_order_by, $show_search_form, $next_action
            );
        }
    }

    function error_delete_object_after_check_links($obj, $next_action, $messages) {
        self_redirect("?action={$next_action}");
    }

    function finish_delete_object(
        $obj, $aspect, $where_str, $default_order_by, $show_search_form, $next_action = null
    ) {
        // Print page after successful object update.

        $name   = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        $this->print_message("{$prefix}deleted", 'ok');
        $this->pg_view_object($name, $aspect, $where_str, $default_order_by, $show_search_form);
    }


    // Even more generalized object functions:

    function print_view_several_objects_page(
        $obj, $aspect, $where_str,
        $default_order_by = NULL, $show_search_form = false, $template_var = 'body'
    ) {
        // Print table to view several objects.

        //$obj = $this->app->create_object($name);

        // COMPATIBILITY:
        if (!is_object($obj)) {
            $obj = $this->app->create_object($obj);
        }

        $name   = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        $query = $obj->get_select_query();

        $query->expand(array(
            'where' => $where_str,
        ));

        // Read filtering (WHERE) and ordering (ORDER_BY) conditions:
        //list($where, $where_params) = $obj->read_where();
        $obj->read_where_cool();
        list ($where_str, $having_str) = $obj->get_where_condition();
        $where_params = $obj->get_where_params();

        if (is_null($default_order_by)) {
            $default_order_by = $obj->primary_key_name();
        }
        list ($order_by, $order_by_params) =
            $obj->read_order_by($default_order_by);
        // Apply filtering and ordering conditions to query:
        $query->expand(array(
            "where" => $where_str,
            "order_by" => $order_by,
            "having" => $having_str,
        ));

        // Print summary on group fields:
        $group_fields = $obj->get_group_fields($aspect);
        if (isset($group_fields)) {
            $group_query = $query;
            $group_query->select   = implode(', ', $group_fields);
            $group_query->order_by = '';

            $res = $this->sql->run_select_query($group_query);
            $row = $res->fetch();
            $obj->fetch_row($row);

            $this->page->assign($obj->write(array_keys($group_fields)));
            $this->page->parse_file("{$prefix}total.html", "text_{$name}_total");
        }

        // Make sub-URL with all necessary parameters stored -- for pager:
        $self_action = $this->get_self_action();
        $where_sub_url = make_sub_url($where_params);
        $order_by_sub_url = make_sub_url($order_by_params);
        $sub_url = "{$self_action}{$where_sub_url}{$order_by_sub_url}" .
            $this->get_pager_custom_url_params();

        $this->page->assign(array(
            'self_action' => "{$self_action}{$where_sub_url}",
            'pager_suburl' => "{$where_sub_url}{$order_by_sub_url}",
        ));

        if ($show_search_form) {
            $this->page->assign($where_params);
            $this->page->assign($obj->write_search_form());

            $this->page->parse_file(
                "{$prefix}search_form.html", "text_{$name}_search_form");
        }

        $n = $this->sql->get_query_num_rows($query);

        if ($n > 0) {
            $this->pager->set_total_rows($n);
            $this->pager->read();

            $query->expand(array(
                'limit' => $this->pager->get_limit_clause(),
            ));

            $res = $this->sql->run_select_query($query);

            // Fill the table with selected items:
            $i = 0;
            while ($row = $res->fetch()) {
                $obj->fetch_row($row);
                $this->page->assign($obj->write());
                $this->page->assign(array(
                    "row_parity" => $i % 2,
                    "row_style" => ($i % 2 == 0) ? "table-row-even" : "table-row-odd",
                ));
                $this->page->parse_file("{$prefix}item.html", "text_{$name}_items");
                $i++;
            }

            $this->page->assign(array(
                'nav_str'        => $this->pager->get_pages_navig($sub_url),
                'simple_nav_str' => $this->pager->get_simple_navig($sub_url),
            ));
        }

        // Print the page:
        $this->page->assign(array(
            'where_sub_url' => $where_sub_url,
            'total'         => $obj->quantity_str($this->pager->n_min),
        ));

        if ($template_var == 'body') {
            $this->create_view_several_objects_page_title($obj);
        }
        $this->page->parse_file("{$prefix}view_all.html", $template_var);
    }


    function get_pager_custom_url_params() {
        return "";
    }


    function print_edit_object_page($obj, $aspect) {
        // Print page for adding/editing object.

        $name = $obj->class_name;
        $prefix = $this->get_template_prefix($name, $aspect);

        // print form:
        $this->page->assign($obj->write_form());
        $this->page->parse_file("{$prefix}form.html", "text_{$name}_form");

        // print page:
        $this->create_edit_object_page_title($obj);
        $this->page->parse_file("{$prefix}edit.html", 'body');
    }


    function print_delete_object_page($obj, $aspect) {
        // Print page for deleting object.

        $name = $obj->class_name;

        $prefix = $this->get_template_prefix($name, $aspect);

        // print form:
        $this->page->assign($obj->write());
        $this->page->parse_file(
            "{$prefix}info.html", "text_{$name}_info");

        // print page:
        $title = $obj->singular_name();
        $this->page->assign(array(
            'title'  => "Delete {$title}",
        ));
        $this->page->parse_file("{$prefix}delete.html", 'body');
    }


    // Auxilary object functions:

    function get_template_prefix($name, $aspect) {
        // Return template prefix (directory for templates) for given object.

        $prefix = ($aspect == '') ? "$name/" : "$name/$aspect/";

        return $prefix;
    }


    // Other functions:

    function authorize_admin() {
        $this->handleHttpAuth(
            $this->config->value("admin_login"),
            $this->config->value("admin_password")
        );
    }

    function handleHttpAuth(
        $login, $password, $realm = "Admin area"
    ) {
        if (
            (!isset($_SERVER["PHP_AUTH_USER"]) || !isset($_SERVER["PHP_AUTH_PW"])) ||
            $_SERVER["PHP_AUTH_USER"] != $login || $_SERVER["PHP_AUTH_PW"] != $password
        ) {
            $this->sendHttpAuthHeaders($realm);
            $this->sendHttpAuthErrorPage();
            exit;
        }
    }

    function sendHttpAuthErrorPage() {
        echo $this->page->parse_file("access_denied.html");
    }

    function sendHttpAuthHeaders($realm) {
        header("WWW-Authenticate: Basic realm=\"{$realm}\"");
        header("HTTP/1.0 401 Unauthorized");
    }

    function print_status_messages($messages) {
        foreach ($messages as $message) {
            $this->print_status_message($message);
        }    
    }

    function print_status_message($message) {
        $msg_text_raw = $this->get_message($message->resource);
        $this->page->assign($message->resource_params);
        $this->page->assign(array(
            "text" => "",
            "type" => $message->type,
        ));
        $this->page->parse_text($msg_text_raw, "text");
        return $this->page->parse_file("_status_message.html", "status_messages");
    }

}  // class PageScript

?>
