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

    function add_content_type_header($mime_type, $charset = null) {
        $charset_str = (is_null($charset)) ? "" : "; charset={$charset}";
        $this->add_header(new HttpHeader("Content-Type", "{$mime_type}{$charset_str}"));
    }

    function add_content_length_header($length) {
        $this->add_header(new HttpHeader("Content-Length", $length));
    }

    function add_content_disposition_header($filename, $disposition_type = "attachment") {
        $filename_safe = rawurlencode($filename);
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

    function BinaryContentResponse(
        $content,
        $mime_type = null,
        $content_length = null,
        $no_cache = true
    ) {
        parent::HttpResponse();

        $this->content = $content;
        
        if (!is_null($mime_type)) {
            $this->add_content_type_header($mime_type);
        }

        if ($no_cache) {
            $this->add_no_cache_headers();
        }

        if (is_null($content_length)) {
            if (!is_null($content)) {
                $content_length = strlen($content);
            }
        }

        if (!is_null($content) && !is_null($content_length)) {
            $this->add_content_length_header($content_length);
        }
    }

    function get_body() {
        if (is_null($this->content)) {
            return "";
        } else {
            return $this->content;
        }
    }
}

class HtmlDocumentResponse extends BinaryContentResponse {
    
    function HtmlDocumentResponse($content, $charset) {
        parent::BinaryContentResponse($content);

        $this->add_content_type_header("text/html", $charset);
    }
}

class XmlDocumentResponse extends BinaryContentResponse {
    
    function XmlDocumentResponse($content) {
        parent::BinaryContentResponse($content, "text/xml");
    }
}

class PlainTextDocumentResponse extends BinaryContentResponse {
    
    function PlainTextDocumentResponse($content, $filename, $open_inline) {

        parent::BinaryContentResponse($content, "plain/text");

        if (!is_null($filename)) {
            if ($filename == "") {
                $filename = "text_document.txt";
            }
            $this->add_content_disposition_header(
                $filename,
                ($open_inline) ? "inline" : "attachment"
            );
        }
    }
}

class PdfDocumentResponse extends BinaryContentResponse {
    
    function PdfDocumentResponse($content, $filename, $open_inline) {
        
        parent::BinaryContentResponse($content, "application/pdf", null, false);

        if (!is_null($filename)) {
            if ($filename == "") {
                $filename = "pdf_document.pdf";
            }
            $this->add_content_disposition_header(
                $filename,
                ($open_inline) ? "inline" : "attachment"
            );
        }
    }
}

class ImageResponse extends BinaryContentResponse {
    
    function ImageResponse($image, $cached_gmt_str) {
        $updated_gmt_str = $image->get_updated_as_gmt_str();
        $content = ($updated_gmt_str == $cached_gmt_str) ?
            null : $image->content;
        
        parent::BinaryContentResponse($content, $image->type, $image->filesize, false);

        if (is_null($content)) {
            $this->push_header(new HttpHeader("HTTP/1.1 304 Not Modified"));
        } else {
            $this->add_content_disposition_header($image->filename);
            $this->add_headers(array(
                new HttpHeader("Expires", "0"),
                new HttpHeader("Cache-Control", "must-revalidate, post-check=0, pre-check=0"),
            ));
            $this->add_last_modified_header($updated_gmt_str);
        }
    }
}

class FileResponse extends BinaryContentResponse {
    
    function FileResponse($file, $open_inline) {
        parent::BinaryContentResponse($file->content, $file->type, $file->filesize, false);

        $this->add_content_disposition_header(
            $file->filename,
            $open_inline ? "inline" : "attachment"
        );
        $updated_gmt_str = $file->get_updated_as_gmt_str();
        $this->add_last_modified_header($updated_gmt_str);
    }
}

?>