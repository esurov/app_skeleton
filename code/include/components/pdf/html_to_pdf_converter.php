<?php

require_once(dirname(__FILE__) . "/dompdf/dompdf_config.inc.php");

class HtmlToPdfConverter extends AppComponent {
    
    // Html content to convert
    var $html;
    
    // Base path to take linked resources from
    var $base_path;

    // Paper size
    var $paper;
    
    // Paper orientation
    var $orientation;

    function _init($params) {
        parent::_init($params);

        $this->html = get_param_value($params, "html", null);
        if (is_null($this->html)) {
            $this->process_fatal_error_required_param_not_found("html");
        }
        $this->base_path = get_param_value($params, "base_path", ".");
        $this->paper = get_param_value($params, "paper", "a4");
        $this->orientation = get_param_value($params, "orientation", "portrait");

        // Global is used to make pdf object accessible from text/php script
        global $pdf;
        $pdf = new DOMPDF();

        $this->pdf =& $pdf;
        $this->pdf->load_html($this->html);
        $this->pdf->set_paper($this->paper, $this->orientation);
        $this->pdf->set_base_path($this->base_path);
    }

    function print_values() {
        $this->pdf->render();
        return $this->pdf->output();
    }

}

?>