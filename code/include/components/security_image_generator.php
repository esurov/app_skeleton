<?php

class SecurityImageGenerator extends AppComponent {

    var $image_type; // jpg or png
    var $font_file; // ttf font full filename
    var $num_chars; // number of chars in security code
    var $width; // image width in pixels
    var $height; // image height in pixels

    function _init($params) {
        parent::_init($params);

        $this->image_type = $this->get_config_value("security_image_image_type");
        $this->font_file = $this->get_config_value("security_image_font_filename");
        $this->num_chars = $this->get_config_value("security_image_num_chars");
        $this->charset = $this->get_config_value("security_image_charset");
        $this->width = $this->get_config_value("security_image_width");
        $this->height = $this->get_config_value("security_image_height");
    }
    
    function get_security_code() {
        return Session::get_param("security_image_code");
    }

    function set_security_code() {
        Session::set_param("security_image_code", $this->generate_security_code());
    }

    function generate_security_code() {
        $charset_length = strlen($this->charset);
        $str = "";
        for ($i = 0; $i < $this->num_chars; $i++) {
            $str .= $this->charset[rand(0, $charset_length - 1)];
        }
        return strtolower($str);
    }

    function &get_current_image() {
        $stored_security_code = $this->get_security_code();
        return $this->create_object(
            "InMemoryImage", 
            array(
                "width" => $this->width,
                "height" => $this->height,
                "type" => $this->image_type,
                "content" => $this->generate_image_content($stored_security_code),
            )
        );
    }

    function generate_image_content($security_code, $noise = true) {
        $image = ImageCreateTrueColor($this->width, $this->height);
        $back = ImageColorAllocate($image, 233, 238, 247);
        ImageFilledRectangle($image, 0, 0, $this->width, $this->height, $back);
        if ($noise) { // rand characters in background with random position, angle, color
            for ($i = 0; $i < 25; $i++) {
                $size = intval(rand(6, 14));
                $angle = intval(rand(0, 360));
                $x = intval(rand(10, $this->width - 10));
                $y = intval(rand(0, $this->height - 5));
                $color = ImageColorAllocate(
                    $image,
                    intval(rand(160, 224)),
                    intval(rand(160, 224)),
                    intval(rand(160, 224))
                );
                $text = chr(intval(rand(45, 250)));
                ImageTTFText($image, $size, $angle, $x, $y, $color, $this->font_file, $text);
            }
        } else { // random grid color
            for ($i = 0; $i < $this->width; $i += 10) {
                $color = ImageColorAllocate(
                    $image,
                    intval(rand(160, 224)),
                    intval(rand(160, 224)),
                    intval(rand(160, 224))
                );
                ImageLine($image, $i, 0, $i, $this->height, $color);
            }
            for ($i = 0; $i < $this->height; $i += 10) {
                $color = ImageColorAllocate(
                    $image,
                    intval(rand(160, 224)),
                    intval(rand(160, 224)),
                    intval(rand(160, 224))
                );
                ImageLine($image, 0, $i, $this->width, $i, $color);
            }
        }
        // print security code
        for ($i = 0, $x = 5; $i < $this->num_chars; $i++) {
            $r = intval(rand(0, 128));
            $g = intval(rand(0, 128));
            $b = intval(rand(0, 128));
            $color = ImageColorAllocate($image, $r, $g, $b);
            $shadow= ImageColorAllocate($image, $r + 128, $g + 128, $b + 128);
            $size = intval(rand(12, 17));
            $angle = intval(rand(-30, 30));
            $text = strtoupper(substr($security_code, $i, 1));
            ImageTTFText($image, $size, $angle, $x + 2, 26, $shadow, $this->font_file, $text);
            ImageTTFText($image, $size, $angle, $x, 24, $color, $this->font_file, $text);
            $x += $size + 2;
        }

        ob_start();
        if ($this->image_type == "jpg") {
            imagejpeg($image, "", 85);
        } else {
            imagepng($image);
        }
        $image_content = ob_get_contents();
        ob_end_clean();
        
        ImageDestroy($image);

        return $image_content;
    }

}
  
?>