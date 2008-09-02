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

class Cookie {
    var $name;
    var $value;
    var $expire;
    var $path;
    var $domain;
    var $secure;

    function Cookie(
        $name,
        $value,
        $expire = 0,
        $path = null,
        $domain = null,
        $secure = false
    ) {
        $this->name = $name;
        $this->value = $value;
        $this->expire = $expire;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
    }

    function send() {
        setcookie(
            $this->name,
            $this->value,
            $this->expire,
            $this->path,
            $this->domain,
            $this->secure
        );
    }    

}

class HttpResponse {
    var $headers;
    var $cookies;

    function HttpResponse() {
        $this->headers = array();
        $this->cookies = array();
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
        array_unshift($this->headers, $header);
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

    function add_content_type_header($content_type_str) {
        $this->add_header(new HttpHeader("Content-Type", $content_type_str));
    }

    function add_content_length_header($length) {
        $this->add_header(new HttpHeader("Content-Length", $length));
    }

    function add_content_disposition_header($filename, $is_attachment) {
        $filename_safe = rawurlencode($filename);
        $disposition_type = ($is_attachment) ? "attachment" : "inline";
        $this->add_header(new HttpHeader(
            "Content-Disposition", "{$disposition_type}; filename=\"{$filename_safe}\""
        ));
    }

    function add_last_modified_header($last_modified_gmt_str) {
        $this->add_header(new HttpHeader("Last-Modified", $last_modified_gmt_str));
    }

    function add_cookie($cookie) {
        $this->cookies[] = $cookie;
    }
//
    function get_body() {
        return "";
    }

    function send() {
        $this->send_headers();
        $this->send_cookies();
        $this->send_body();
    }

    function send_headers() {
        foreach ($this->headers as $header) {
            $header->send();
        }
    }

    function send_cookies() {
        foreach ($this->cookies as $cookie) {
            $cookie->send();
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

class BinaryContentResponse extends HttpResponse {

    var $content;

    function BinaryContentResponse($content_type, $content, $content_length = null) {
        parent::HttpResponse();

        $this->content = $content;

        if (is_null($content_length)) {
            $content_length = strlen($content);
        }

        $this->add_content_type_header($content_type);
        $this->add_content_length_header($content_length);
    }
    
    function get_body() {
        return $this->content;
    }

}

class BinaryStreamResponse extends HttpResponse {
    
    var $stream;
    var $stream_type;

    function BinaryStreamResponse($content_type, $stream, $stream_type) {
        parent::HttpResponse();

        $this->stream = $stream;
        $this->stream_type = $stream_type;

        $this->add_content_type_header($content_type);
    }

    function send_body() {
        fpassthru($this->stream);
        switch ($this->stream_type) {
        case "file":
        case "socket":
            fclose($this->stream);
            break;
        case "process":
            pclose($this->stream);
            break;
        }
    }

}

class PlainTextDocumentResponse extends BinaryContentResponse {
    
    function PlainTextDocumentResponse($content, $filename, $is_attachment) {
        parent::BinaryContentResponse("text/plain", $content);
        
        $this->add_no_cache_headers();

        if ($filename != "") {
            $this->add_content_disposition_header($filename, $is_attachment);
        }
    }

}

class HtmlDocumentResponse extends BinaryContentResponse {
    
    function HtmlDocumentResponse($content, $charset) {
        parent::BinaryContentResponse("text/html; charset={$charset}", $content);

        $this->add_no_cache_headers();
    }

}

class XmlDocumentResponse extends BinaryContentResponse {
    
    function XmlDocumentResponse($content) {
        parent::BinaryContentResponse("text/xml", $content);

        $this->add_no_cache_headers();
    }

}

class RssDocumentResponse extends BinaryContentResponse {
    
    function RssDocumentResponse($content) {
        parent::BinaryContentResponse("application/rss+xml", $content);
    }

}

class CsvDocumentResponse extends BinaryContentResponse {
    
    function CsvDocumentResponse($content, $filename, $is_attachment) {
        parent::BinaryContentResponse("text/csv", $content);

        if ($filename != "") {
            $this->add_content_disposition_header($filename, $is_attachment);
        }
    }

}

class PdfDocumentResponse extends BinaryContentResponse {
    
    function PdfDocumentResponse($content, $filename, $is_attachment) {
        parent::BinaryContentResponse("application/pdf", $content);

        if ($filename != "") {
            $this->add_content_disposition_header($filename, $is_attachment);
        }
    }

}

class FileResponse extends BinaryContentResponse {
    
    function FileResponse($file, $filename, $updated_gmt_str, $is_attachment) {
        parent::BinaryContentResponse(
            $file->get_mime_type(),
            $file->get_content(),
            $file->get_content_length()
        );

        if ($filename != "") {
            $this->add_content_disposition_header($filename, $is_attachment);
        }
        $this->add_headers(array(
            new HttpHeader("Expires", "0"),
            new HttpHeader("Cache-Control", "must-revalidate, post-check=0, pre-check=0"),
        ));
        $this->add_last_modified_header($updated_gmt_str);
    }

}

?>