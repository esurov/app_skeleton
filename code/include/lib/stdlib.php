<?php

// Standard library functions.

function header_no_cache()
{
    // This function sent raw HTTP header for no cache HTML page.
    // For more detail realization - see PHP manual, HTTP function.

    header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

    $server_protocol = $_SERVER['SERVER_PROTOCOL'];
    if($server_protocol == 'HTTP/1.1'){
        header ("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
    } else {
        header ("Pragma: no-cache");                          // HTTP/1.0
    }

    header("Cache-Control: post-check=0, pre-check=0", false);
}


function self_redirect($sub_url = '')
{
    // Perform HTTP redirect to the same script.

    global $app;

    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]$sub_url";

    $app->log->write('Redirect', $url, 3);

    header("Location: $url");

    exit;
}


function write_options($items, $select = NULL)
{
    // OBSOLETE!
    // Compatibility wrapper for make_options() function.

    return make_options($items, $select) ;
}


function make_options($items, $select = NULL)
{
    // Return a string with a <option> tags created from given array.

    $s = "\n";

    foreach($items as $i => $item) {
        if(is_array( $item) ) {
            $id   = $item['id'];
            $name = $item['name'];

        } else {
            // compatibility mode:
            $id   = $i;
            $name = $item;
        }
        if(is_array( $select) ) {
            $sel = in_array($id, $select) ? ' selected' : '';
        } else {
            $sel = (isset($select) && $id == $select) ? ' selected' : '';
        }
        $s .= "<option value=\"$id\"$sel>$name</option>\n";
    }

    return $s;
}


function param($name) {
    if (isset($_GET[$name])) {
        $res = $_GET[$name];
    } else if (isset($_POST[$name])) {
        $res = $_POST[$name];
    } else {
        return null;
    }
    
    if (get_magic_quotes_gpc()) {
        $res = stripslashes($res);
    }

    return $res;
}

function param_cookie($name) {
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
}

function params() {
    return array_merge($_POST, $_GET);
}

function make_sub_url($params)
{
    // Generate partial URL from given hash of parameters.

    $sub_url = '';

    foreach($params as $name => $value) {
        if(is_array( $value) ) {
            foreach($value as $val) {
                $sub_url .= "&amp;{$name}[]=" . urlencode($val);
            }
        } else {
            $sub_url .= "&amp;$name=" . urlencode($value);
        }
    }

    return $sub_url;
}


function if_null($variable, $value) {
    return is_null($variable) ? $value : $variable;
}


// Some less standard functions:

function get_years()
{
    // Return array of valid years.

    $y = array();

    $y[2000] = '2000';
    $y[2001] = '2001';
    $y[2002] = '2002';
    $y[2003] = '2003';
    $y[2004] = '2004';
    $y[2005] = '2005';

    return $y;
}


function get_months()
{
    // Return array of valid months.

    $m = array(
        '(whole year)',
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
   );
    return $m;
}

function mysql_to_unix_time($str)
{
    $date_regexp = '/^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)$/';
    $date_values = array();
    $result = 0;
    if(preg_match( $date_regexp, $str, $date_values) ) {
        $year   = $date_values[1];
        $month  = $date_values[2];
        $day    = $date_values[3];
        $hour   = $date_values[4];
        $minute = $date_values[5];
        $second = $date_values[6];
        $result = mktime($hour, $minute, $second, $month, $day, $year);
    }
    return $result;
}


function str_to_unix_time($str)
{
    $date_regexp = '/^(\d+)-(\d+)-(\d+)(\s+(\d+))?(:(\d+))?(:(\d+))?$/';
    $date_values = array();
    $result = 0;
    if(preg_match( $date_regexp, $str, $matches) ) {
        $matches = array_merge($matches, array( 0, 0, 0) );
        $year   = $matches[1];
        $month  = $matches[2];
        $day    = $matches[3];
        $hour   = $matches[4];
        $minute = $matches[5];
        $second = $matches[6];
        $result = mktime($hour, $minute, $second, $month, $day, $year);
    }
    return $result;
}


function unix_to_mysql_time($time)
{
    return date('Y-m-d H:i:s', $time);
}

function pipe_sendmail($msg, $queue = true)
{
    // Send email message using local sendmail program.

    // $sendmail_path = '/usr/lib/sendmail';
    // $sendmail_keys = '-oi -t' . ($queue ? ' -odq' : '');

    $sendmail_command = ini_get('sendmail_path');
    if($queue) {
        $sendmail_command .= ' -odq';
    }

    $from = $msg['from'];
    $from_name = isset($msg['from_name']) ? $msg['from_name'] : '';
    $to = $msg['to'];
    $to_name = isset($msg['to_name']) ? $msg['to_name'] : '';
    $subj = isset($msg['subj']) ? $msg['subj'] : '';
    $text = $msg['text'];

    $s =
        "From: $from_name <$from>\n" .
        "To: $to_name <$to>\n" .
        "Subject: $subj\n" .
        "\n" .
        "$text";

    $sm = popen("$sendmail_command", 'w');
    if(!$sm) {
        die("Can't fork for sendmail!<br>\n");
    }

    fputs($sm, $s);

    pclose($sm);
}

function is_empty($value) {
    return !preg_match('/\w+/', $value);
}

function is_email($value) {
    return preg_match('/.+@.+\..+/', $value);
}

function is_in_table($table, $where_str, $sql) {
    $query = new SelectQuery(array(
        "from"  => $table,
        "where" => $where_str,
    ));
    $n = $sql->get_query_num_rows($query);
    return ($n == 1);
}

function format_double_value(
    $double_value, $decimals = 2, $dec_point = ",", $thousands_sep = "."
) {
    return number_format($double_value, $decimals, $dec_point, $thousands_sep);
}

function escape_js($str) {
    return str_replace("'", "\'", $str);
}

function get_textarea_as_html($str) {
    $html = htmlspecialchars($str);
    $html = preg_replace('/\r/', ''    , $html);
    $html = preg_replace('/\n/', '<br>', $html);
    return $html;
}

function create_dependency_js(
    $formName, $name, $depSelectName, $dep_array
) {
    $dependenciesStr = create_dependency_str($dep_array);
    return
        "<script language=\"JavaScript\">

        depends[depends.length] = new Dependency(
            '{$formName}',
            '{$name}',
            '{$depSelectName}',
            new Array(
{$dependenciesStr}
            )
        );

        </script>";
}

function create_dependency_str($dep_array) {
    $elements = array();
    foreach ($dep_array as $dep_select_array) {
        $elements[] = '    new Array("' . implode('", "', $dep_select_array) . '")';
    }
    return implode(",\n", $elements) . "\n";
}

function get_table_field_values($table, $field_name, $query_ex) {
    global $app;
    $h = array();
    $obj = $app->create_object($table);  // !!!
    $res = $obj->get_expanded_result($query_ex);
    while($row = $res->fetch()) {
        $obj->fetch_row($row);
        $h[] = array(
            "id" => $obj->id,
            "name" => $obj->$field_name   // !!!
        );
    }
    return $h;
}

function get_ids_from_items($items) {
    $ids = array();
    foreach ($items as $item) {
        $ids[] = $item["id"];    
    }    
    return $ids;
}

function write_options_ex($items, $select = null, $items_begin = null, $items_end = null) {
    if (!is_null($items_begin)) {
        $items = array_merge(array($items_begin), $items);
    }
    if (!is_null($items_end)) {
        $items = array_merge($items, array($items_end));
    }
    return write_options($items, $select);
}

?>
