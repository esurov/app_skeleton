<?php

class SampleComponent {

    var $templates_dir;
    var $template_var;

    function print_values() {
        $this->app->print_file("{$this->templates_dir}/body.html", $this->template_var);
    }
    
}

class SampleComponent2 extends SampleComponent {
    
}

?>