<?php

class ImageMagick {

    var $width = 0;
    var $height = 0;
    var $type = "";

    var $image_magick_path;
    var $dst_file_path;
    var $dst_quality = "85";

    var $verbose = false;

    function ImageMagick($image_magick_path = "", $app_name = "") {
        $this->app_name = $app_name;
        $this->image_magick_path = $image_magick_path;
        $this->create_dst_file();
        $this->set_dst_file_ext($this->get_default_dst_file_ext());
    }

    function create_dst_file() {
        $this->dst_file_path = tempnam("", "imagemagick_{$this->app_name}");
    }

    function set_dst_file_ext($ext) {
        $new_dst_file_path = "{$this->dst_file_path}.{$ext}";
        if (@rename($this->dst_file_path, $new_dst_file_path)) {
            $this->dst_file_path = $new_dst_file_path;
        }
    }

    function cleanup() {
        @unlink($this->dst_file_path);
    }

    function get_default_dst_file_ext() {
        return "jpg";
    }
//
    function get_width() {
        return $this->width;
    }

    function get_height() {
        return $this->height;
    }

    function get_type() {
        return strtolower($this->type);
    }

    function get_mime_type() {
        return "image/" . $this->get_type();
    }

    function get_filesize() {
        clearstatcache();
        return filesize($this->dst_file_path);
    }

    function get_content() {
        return file_get_contents($this->dst_file_path);
    }
//
    function fetch_properties($file_path = null) {
        if (is_null($file_path)) {
            $file_path = $this->dst_file_path;
        }
        $cmdline = "{$this->image_magick_path}identify -format '%w:%h:%m' '{$file_path}'";
        exec($cmdline, $returned_lines, $returned_value);
        if ($returned_value != 0) {
            if ($this->verbose) {
                var_dump("Identify failed! Cmdline: {$cmdline}");
            }
        } else {
            if ($this->verbose) {
                var_dump($returned_lines[0]);
            }
            list(
                $this->width, 
                $this->height, 
                $this->type, 
            ) = explode(":", trim($returned_lines[0]));
        }
    }

    function resize($src_file_path, $dst_image_params, $extra_cmdline = "") {
        $cmdline = $this->create_convert_cmdline(
            $src_file_path,
            array(
                $this->create_resize_cmdline(
                    $dst_image_params["width"], $dst_image_params["height"]
                ),
                $extra_cmdline,
            )
        );

        if ($this->verbose) {
            var_dump($cmdline);
        }

        exec($cmdline, $returned_lines, $returned_value);
        if ($returned_value != 0) {
            if ($this->verbose) {
                var_dump("Resize failed! Cmdline: {$cmdline}");
            }
        } else {
            $this->fetch_properties();
        }
    }

    function resize_and_grayscale($src_file_path, $dst_image_params) {
        $this->resize(
            $src_file_path,
            $dst_image_params,
            $this->create_grayscale_cmdline()
        );
    }
    
    function crop_and_resize($src_file_path, $dst_image_params) {
        $this->fetch_properties($src_file_path);
        $src_width = $this->width;
        $src_height = $this->height;
        $dst_width = $dst_image_params["width"];
        $dst_height = $dst_image_params["height"];

        $src_ratio = $src_width / $src_height;
        $dst_ratio = $dst_width / $dst_height;

        if ($src_ratio > $dst_ratio) {
            $resized_width = (int) ($src_height * $dst_ratio);
            $resized_height = $src_height;
            $crop_offset_x = (int) (($src_width - $resized_width) / 2);
            $crop_offset_y = 0;
        } else {
            $resized_width = $src_width;
            $resized_height = (int) ($src_width / $dst_ratio);
            $crop_offset_x = 0;
            $crop_offset_y = (int) (($src_height - $resized_height) / 2);
        }

        $cmdline = $this->create_convert_cmdline(
            $src_file_path,
            array(
                $this->create_crop_cmdline(
                    $resized_width, $resized_height, $crop_offset_x, $crop_offset_y
                ),
                $this->create_resize_cmdline(
                    $dst_width, $dst_height
                ),
            )
        );

        if ($this->verbose) {
            var_dump($cmdline);
        }

        exec($cmdline, $returned_lines, $returned_value);
        if ($returned_value != 0) {
            if ($this->verbose) {
                var_dump("Crop and resize failed! Cmdline: {$cmdline}");
            }
        } else {
            $this->fetch_properties();
        }
    }

    function create_convert_cmdline($src_file_path, $lines) {
        return
            "{$this->image_magick_path}convert +profile '*' " .
            "-quality {$this->dst_quality} '{$src_file_path}' " .
            join(" ", $lines) .
            " '{$this->dst_file_path}'";
    }

    function create_resize_cmdline($resized_width, $resized_height) {
        return "-resize '{$resized_width}x{$resized_height}'";
    }

    function create_grayscale_cmdline() {
        return "-colorspace gray";
    }

    function create_crop_cmdline(
        $cropped_width,
        $cropped_height,
        $crop_offset_x,
        $crop_offset_y
    ) {
        if ($crop_offset_x >= 0) {
            $crop_offset_x = "+{$crop_offset_x}";
        }
        if ($crop_offset_y >= 0) {
            $crop_offset_y = "+{$crop_offset_y}";
        }
        return "-crop '{$cropped_width}x{$cropped_height}{$crop_offset_x}{$crop_offset_y}'";
    }
}

?>