<?php

class _ImageProcessor extends AppObject {

    // Result Image object
    var $_image;

    // Info about actions to be performed with the image
    var $_actions;

    // Parameters of running action
    var $_action_params;
//
    function _init($params) {
        parent::_init($params);

        $this->_actions = get_param_value($params, "actions", array());
    }
//
    function process(&$image) {
        $this->_image =& $image;

        foreach ($this->_actions as $action_params) {
            $this->_action_params = $action_params;
            if (!$this->process_action($action_params["name"])) {
                $this->cleanup();
                return false;
            }
        }
        return true;
    }

    function process_action($action_name) {
        $action_func_name = "action_{$action_name}";
        if (method_exists($this, $action_func_name)) {
            if (!$this->{$action_func_name}()) {
                return false;
            }
        }
        return true;
    }

    function cleanup() {
    }

}

class ImageMagickWrapper extends _ImageProcessor {

    var $_image_magick_path;
    var $_output_file_full_filename;
    var $_output_file_created;
    var $_output_image_type;
    var $_output_image_jpg_quality;

    function _init($params) {
        parent::_init($params);

        $this->set_image_magick_path($this->get_config_value("image_magick_path"));

        $this->_output_file_created = false;
        $this->_output_file_full_filename = get_param_value($params, "output_filename", null);
        if (is_null($this->_output_file_full_filename)) {
            $this->_output_file_created = true;
            $this->_output_file_full_filename = tempnam(null, "im_");
        }
        $this->_output_image_type = get_param_value(
            $params,
            "output_image_type",
            $this->get_default_output_image_type()
        );
        $this->_output_image_jpg_quality = get_param_value(
            $params,
            "output_image_jpg_quality",
            $this->get_default_output_image_jpg_quality()
        );
    }

    function set_image_magick_path($image_magick_path) {
        $this->_image_magick_path = $image_magick_path;
        if ($this->_image_magick_path != "") {
            $this->_image_magick_path .= "/";
        }
    }
    
    function get_default_output_image_type() {
        return $this->get_config_value("image_magick_output_image_type");
    }

    function get_default_output_image_jpg_quality() {
        return $this->get_config_value("image_magick_output_image_jpg_quality");
    }

    function cleanup() {
        if ($this->_output_file_created) {
            @unlink($this->_output_file_full_filename);
        }
    }
//
    function process_action($action_name) {
        $this->_input_file_full_filename = $this->_image->get_full_filename();

        parent::process_action($action_name);

        list($returned_lines, $returned_value) = $this->exec_cmdline();

        if ($returned_value != 0) {
            return false;
        } else {
            $this->_image->set_full_filename($this->_output_file_full_filename);
            return true;
        }
    }

    function exec_cmdline() {
        $this->write_log(
            "Starting image processing:\n" .
            "Commandline: {$this->_cmdline}",
            DL_INFO
        );

        exec($this->_cmdline, $returned_lines, $returned_value);
        
        if ($returned_value != 0) {
            $this->write_log(
                "Image processing failed!\n" .
                "Commandline: {$this->_cmdline}\n" .
                "Error #{$returned_value}. Output:\n" .
                join("\n", $returned_lines),
                DL_ERROR
            );
        }

        return array($returned_lines, $returned_value);
    }

    function fetch_actual_image_properties() {
        $this->_cmdline = $this->create_identify_cmdline();

        list($returned_lines, $returned_value) = $this->exec_cmdline();

        if ($returned_value != 0) {
            return false;
        } else {
            // list($width, $height, $type) = explode(":", trim($returned_lines[0]));
            return explode(":", trim($returned_lines[0]));
        }
    }
//
    // ImageMagick commandlines creation functions
    function create_identify_cmdline() {
        return
            "{$this->_image_magick_path}identify " .
            "-format %w:%h:%m " .
            "\"{$this->_input_file_full_filename}\"";
    }

    function create_convert_cmdline($subcmdlines) {
        return
            "{$this->_image_magick_path}convert " .
            "+profile \"*\" -quality {$this->_output_image_jpg_quality} " .
            "\"{$this->_input_file_full_filename}\" " .
            join(" ", $subcmdlines) .
            " \"{$this->_output_image_type}:{$this->_output_file_full_filename}\"";
    }

    function create_resize_subcmdline($resize_width, $resize_height) {
        return "-resize \"{$resize_width}x{$resize_height}\"";
    }

    function create_crop_subcmdline($crop_width, $crop_height, $crop_offset_x, $crop_offset_y) {
        if ($crop_offset_x >= 0) {
            $crop_offset_x = "+{$crop_offset_x}";
        }
        if ($crop_offset_y >= 0) {
            $crop_offset_y = "+{$crop_offset_y}";
        }
        return "-crop \"{$crop_width}x{$crop_height}{$crop_offset_x}{$crop_offset_y}\"";
    }

    function create_grayscale_subcmdline() {
        return "-colorspace gray";
    }
//
    // Actions
    function action_resize() {
        $output_width = $this->_action_params["width"];
        $output_height = $this->_action_params["height"];
        $this->_cmdline = $this->create_convert_cmdline(array(
            get_param_value($this->_action_params, "begin_subcmdline", ""),
            $this->create_resize_subcmdline($output_width, $output_height),
            get_param_value($this->_action_params, "end_subcmdline", ""),
        ));
        return true;
    }

    function action_crop_and_resize() {
        $input_width = $this->_image->get_width();
        $input_height = $this->_image->get_height();
        $output_width = $this->_action_params["width"];
        $output_height = $this->_action_params["height"];

        $input_width_height_ratio = $input_width / $input_height;
        $output_width_height_ratio = $output_width / $output_height;

        if ($input_width_height_ratio > $output_width_height_ratio) {
            $crop_width = (int) ($input_height * $output_width_height_ratio);
            $crop_height = $input_height;
            $crop_offset_x = (int) (($input_width - $crop_width) / 2);
            $crop_offset_y = 0;
        } else {
            $crop_width = $input_width;
            $crop_height = (int) ($input_width / $output_width_height_ratio);
            $crop_offset_x = 0;
            $crop_offset_y = (int) (($input_height - $crop_height) / 2);
        }

        $this->_cmdline = $this->create_convert_cmdline(array(
            get_param_value($this->_action_params, "begin_subcmdline", ""),
            $this->create_crop_subcmdline(
                $crop_width,
                $crop_height,
                $crop_offset_x,
                $crop_offset_y
            ),
            $this->create_resize_subcmdline($output_width, $output_height),
            get_param_value($this->_action_params, "end_subcmdline", ""),
        ));
        return true;
    }

    function action_convert_to_grayscale() {
        $this->_cmdline = $this->create_convert_cmdline(array(
            $this->create_grayscale_subcmdline(),
        ));
        return true;
    }

}

class GD2 extends _ImageProcessor {

    var $_output_file_full_filename;
    var $_output_file_created;
    var $_output_image_type;
    var $_output_image_jpg_quality;

    function _init($params) {
        parent::_init($params);

        $this->_output_file_created = false;
        $this->_output_file_full_filename = get_param_value($params, "output_filename", null);
        if (is_null($this->_output_file_full_filename)) {
            $this->_output_file_created = true;
            $this->_output_file_full_filename = tempnam(null, "gd2_");
        }
        $this->_output_image_type = get_param_value(
            $params,
            "output_image_type",
            $this->get_default_output_image_type()
        );
        $this->_output_image_jpg_quality = get_param_value(
            $params,
            "output_image_jpg_quality",
            $this->get_default_output_image_jpg_quality()
        );
    }

    function get_default_output_image_type() {
        return $this->get_config_value("gd2_output_image_type");
    }

    function get_default_output_image_jpg_quality() {
        return $this->get_config_value("gd2_output_image_jpg_quality");
    }

    function cleanup() {
        if ($this->_output_file_created) {
            @unlink($this->_output_file_full_filename);
        }
    }
//
    function process_action($action_name) {
        $this->_input_file_full_filename = $this->_image->get_full_filename();

        if (parent::process_action($action_name)) {
            $this->_image->set_full_filename($this->_output_file_full_filename);
            return true;
        } else {
            return false;
        }
    }
//
    function _get_allowed_image_types() {
        return array("jpg", "gif", "png");
    }

    function _is_image_type_allowed($image_type) {
        return in_array($image_type, $this->_get_allowed_image_types());
    }

    function _get_image_type_func_name($image_type) {
        switch ($image_type) {
        case "jpg":
            $image_type_func_name = "jpeg";
            break;
        
        case "gif":
            $image_type_func_name = "gif";
            break;
        
        case "png":
            $image_type_func_name = "png";
            break;
        
        default:
            $image_type_func_name = "";
        }
        return $image_type_func_name;
    }

    function _run_image_import_func($image_type) {
        $image_type_func_name = $this->_get_image_type_func_name($image_type);
        $image_import_func_name = "imagecreatefrom{$image_type_func_name}";
        return $image_import_func_name($this->_input_file_full_filename);
    }

    function _run_image_export_func($image_type, $dst_image_resource) {
        $image_type_func_name = $this->_get_image_type_func_name($image_type);
        $image_export_func_name = "image{$image_type_func_name}";
        switch ($image_type) {
        case "jpg":
            $result = $image_export_func_name(
                $dst_image_resource,
                $this->_output_file_full_filename,
                $this->_output_image_jpg_quality
            );
            break;
        
        case "gif":
        case "png":
            $result = $image_export_func_name(
                $dst_image_resource,
                $this->_output_file_full_filename
            );
            break;
        
        default:
            $result = false;
        }
        return $result;
    }
//
    // Actions
    function action_resize() {
        $image_type = $this->_image->get_type();
        if (!$this->_is_image_type_allowed($image_type)) {
            return false;
        }
        
        $input_width = $this->_image->get_width();
        $input_height = $this->_image->get_height();
        $output_width = $this->_action_params["width"];
        $output_height = $this->_action_params["height"];
            
        $src_image_resource = $this->_run_image_import_func($image_type);
        
        $dst_image_resource = imagecreatetruecolor($output_width, $output_height);

        imagecopyresampled(
            $dst_image_resource,
            $src_image_resource,
            0,
            0,
            0,
            0,
            $output_width,
            $output_height,
            $input_width,
            $input_height
        );

        return $this->_run_image_export_func($this->_output_image_type, $dst_image_resource);
    }

    function action_crop_and_resize() {
        $image_type = $this->_image->get_type();
        if (!$this->_is_image_type_allowed($image_type)) {
            return false;
        }
        $input_width = $this->_image->get_width();
        $input_height = $this->_image->get_height();
        $output_width = $this->_action_params["width"];
        $output_height = $this->_action_params["height"];

        $input_width_height_ratio = $input_width / $input_height;
        $output_width_height_ratio = $output_width / $output_height;

        if ($input_width_height_ratio > $output_width_height_ratio) {
            $crop_width = (int) ($input_height * $output_width_height_ratio);
            $crop_height = $input_height;
            $crop_offset_x = (int) (($input_width - $crop_width) / 2);
            $crop_offset_y = 0;
        } else {
            $crop_width = $input_width;
            $crop_height = (int) ($input_width / $output_width_height_ratio);
            $crop_offset_x = 0;
            $crop_offset_y = (int) (($input_height - $crop_height) / 2);
        }

        $src_image_resource = $this->_run_image_import_func($image_type);
        
        $dst_image_resource = imagecreatetruecolor($output_width, $output_height);

        imagecopyresampled(
            $dst_image_resource,
            $src_image_resource,
            0,
            0,
            $crop_offset_x,
            $crop_offset_y,
            $output_width,
            $output_height,
            $crop_width,
            $crop_height
        );

        return $this->_run_image_export_func($this->_output_image_type, $dst_image_resource);
    }

    function action_convert_to_grayscale() {
        $image_type = $this->_image->get_type();
        if (!$this->_is_image_type_allowed($image_type)) {
            return false;
        }
        $output_width = $this->_image->get_width();
        $output_height = $this->_image->get_height();

        $dst_image_resource = $this->_run_image_import_func($image_type);

        for ($x = 0; $x < $output_width; $x++) {
            for ($y = 0; $y < $output_height; $y++) {
                $rgb = imagecolorat($dst_image_resource, $x, $y);
                
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $gray = round(0.299 * $red + 0.587 * $green + 0.114 * $blue);

                imagesetpixel(
                    $dst_image_resource,
                    $x,
                    $y, 
                    imagecolorallocate($dst_image_resource, $gray, $gray, $gray)
                );
            }
        }

        return $this->_run_image_export_func($this->_output_image_type, $dst_image_resource);
    }

}

?>