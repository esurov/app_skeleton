<?php

class SampleComponent extends TemplateComponent {

}

class SampleComponent2 extends SampleComponent {
    
    function _print_values() {
        parent::_print_values();

        $this->app->print_file("{$this->templates_dir}/body.html", $this->template_var);
    }
    
}

?>