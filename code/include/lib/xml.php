<?php

class XML {

    var $parser;

    function XML() {
    }

    // Parse given XML data.
    // All initialisation code code goes here, not to constructor (!)
    function parse($xml_data_str) {

        // Create parser:
        $parser = xml_parser_create();
        xml_set_object($parser, &$this);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($parser, "tag_open", "tag_close");
        xml_set_character_data_handler($parser, "cdata");

        // Do parse:
        xml_parse($parser, $xml_data_str);

        // Destroy parser:
        xml_parser_free($parser);
    }

    function tag_open($parser, $tag, $attributes) {
        // Must be redefined in derived class
    }

    function cdata($parser, $cdata) {
        // Must be redefined in derived class
    }

    function tag_close($parser, $tag) {
        // Must be redefined in derived class
    }

} // class XML

?>