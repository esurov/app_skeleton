<?php

class Article extends CustomDbObject {

    function Article() {
        
        parent::CustomDbObject("article");

        $this->insert_field(array(
            "column" => "id",
            "type"   => "integer",
            "attr"   => "auto_increment",
        ));

        $this->insert_field(array(
            "column" => "created",
            "type"   => "date",
            "value"  => sql_now_date(),
        ));

        $this->insert_field(array(
            "column" => "title",
            "type"   => "varchar",
            "value"  => "",
            "multilingual" => 1,
        ));

        $this->insert_field(array(
            "column" => "body",
            "type"   => "text",
            "value"  => "",
            "multilingual" => 1,
            "input"  => "textarea",
        ));
//
        $this->insert_field(array(
            "name"   => "full_text",
            "type"   => "text",
            "select" =>
                "concat(article.title_{$this->lang}, article.body_{$this->lang})",
        ));
//
        $this->insert_where_condition(array(
            "name"     => "full_text",
            "relation" => "like",
        ));
        
        $this->table_indexes =
            "primary key(id), " .
            "index(created)";
    }

    function singular_name() {
        return $this->get_message("article");
    }

    function plural_name() {
        return $this->get_message("articles");
    }

//    function verify()
//    {
//        $err = "";
//
//        if( !preg_match("/\w+/", $this->title) ) {
//            $err .= "<p>Please input article title. It should be correct string.</p>\n";
//        }
//
//        if( !preg_match("/\w+/", $this->body) ) {
//            $err .= "<p>Please input article text.</p>\n";
//        }
//
//        if ( preg_match( "/\d{2,4}-\d{1,2}-\d{1,2}/", $this->created ) ) {
//            list( $year, $month, $day ) = explode( "-", $this->created );
//
//            if( checkdate( $month, $day, $year ) ) {
//                $now = date("Y-m-d");
//                if( $this->created > $now ) {
//                    $err .= "<p>Article date cannot be in future.</p>";
//                } else if( $year <= 1900 ) {
//                    $err .= "<p>Article is too old.</p>";
//                }
//            } else {
//                $err .= "<p>Please input correct date.</p>";
//            }
//
//        } else {
//            $err .= "<p>Article date should be in format mm/dd/yyyy.</p>";
//        }
//
//        return $err;
//    }

    function write($fields = null) {
        $h = parent::write($fields);
        
        $body_short_len = $this->config->value("article_body_short_length");
        $body_shortened = strip_tags($this->body);
        if (strlen($body_shortened) > $body_short_len) {
            $body_shortened = substr($body_shortened, 0, $body_short_len) . "...";
        }
        $h["article_body_short"] = $body_shortened;
        return $h;
    }
}

?>
