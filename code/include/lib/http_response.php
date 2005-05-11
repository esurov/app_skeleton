<?php

class HttpHeader {

    var $name;
    var $value;

    function HttpHeader($name, $value = null) {
        $this->name = $name;
        $this->value = $value;
    }

    function get_string() {
        $value_str = (is_null($this->value)) ? "" : ": {$this->value}";
        return "{$this->name}{$value_str}";
    }

    function send() {
        header($this->get_string());
    }
}

class HttpResponse {
    var $headers;

    function HttpResponse() {
        $this->headers = array();
    }

    // Add header to the end of headers list
    function add_header($header) {
        $this->headers[] = $header;
    }

    function add_headers($headers) {
        $this->headers = array_merge($this->headers, $headers);
    }

    // Push header to the beginning of headers list
    function push_header($header) {
        $this->headers = array_unshift($this->headers, $header);
    }

    function push_headers($headers) {
        $this->headers = array_merge($headers, $this->headers);
    }

    function add_no_cache_headers() {
        $this->add_last_modified_header(get_gmt_str_from_timestamp(time()));
        $this->add_headers(array(
            new HttpHeader("Expires", "Mon, 01 Jan 2000 00:00:00 GMT"),
            new HttpHeader(
                "Cache-Control",
                "no-store, no-cache, must-revalidate, post-check=0, pre-check=0"
            ),
            new HttpHeader("Pragma", "no-cache"),
        ));
    }

    function add_redirect_header($url) {
        $this->add_header(new HttpHeader("Location", "{$url}"));
    }

    function add_content_type_header($mime_type, $charset = null) {
        $charset_str = (is_null($charset)) ? "" : "; charset={$charset}";
        $this->add_header(new HttpHeader("Content-Type", "{$mime_type}{$charset_str}"));
    }

    function add_content_disposition_header($filename, $disposition_type = "attachment") {
        $this->add_header(new HttpHeader(
            "Content-Disposition", "{$disposition_type}; filename=\"{$filename}\""
        ));
    }

    function add_last_modified_header($last_modified_gmt_str) {
        $this->add_header(new HttpHeader("Last-Modified", $last_modified_gmt_str));
    }
//
    function get_body() {
        return "";
    }

    function send() {
        $this->send_headers();
        $this->send_body();
    }

    function send_headers() {
        foreach ($this->headers as $header) {
            $header->send();
        }
    }

    function send_body() {
        echo $this->get_body();
    }
}

class RedirectResponse extends HttpResponse {
    
    function RedirectResponse($url) {
        parent::HttpResponse();

        $this->add_redirect_header($url);
    }
}

class HtmlPageResponse extends HttpResponse {
    
    var $page_content;

    function HtmlPageResponse($page_content, $charset) {
        parent::HttpResponse();

        $this->page_content = $page_content;

        $this->add_content_type_header("text/html", $charset);
        $this->add_no_cache_headers();
    }

    function get_body() {
        return $this->page_content;
    }
}

class XmlPageResponse extends HttpResponse {
    
    var $page_content;

    function XmlPageResponse($page_content) {
        parent::HttpResponse();

        $this->page_content = $page_content;

        $this->add_content_type_header("text/xml");
        $this->add_no_cache_headers();
    }

    function get_body() {
        return $this->page_content;
    }
}

class ImageResponse extends HttpResponse {
    
    var $image;
    var $should_send_body;

    function ImageResponse($image, $cached_gmt_str) {
        parent::HttpResponse();

        $updated_gmt_str = $image->get_updated_as_gmt_str();
        if ($updated_gmt_str == $cached_gmt_str) {
            $this->image = null;
            $this->add_header(new HttpHeader("HTTP/1.1 304 Not Modified"));
        } else {
            $this->image = $image;
            $this->add_content_type_header($this->image->type);
            $this->add_content_disposition_header($this->image->filename);
            $this->add_headers(array(
                new HttpHeader("Accept-Ranges", "bytes"),
                new HttpHeader("Content-Length", $this->image->filesize),
                new HttpHeader("Expires", "0"),
                new HttpHeader("Cache-Control", "must-revalidate, post-check=0, pre-check=0"),
            ));
            $this->add_last_modified_header($updated_gmt_str);
        }
    }

    function get_body() {
        if (is_null($this->image)) {
            return "";
        } else {
            return $this->image->content;
        }
    }
}

?>