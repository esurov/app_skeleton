<?php

class App {
    // Application -- base class.

    var $app_name;
    var $config;
    var $log;


    function App($app_name, $config_dir = 'config', $log_dir = 'log') {
        // Constructor.

        $this->app_name = $app_name;

        // Read configuration file:
        $this->config = new Config();
        $this->config->read("{$config_dir}/app.cfg");
    /*
        // Read special (debug) configuration file:
        $special_config_file = "{$config_dir}/debug.cfg";
        if(file_exists( $special_config_file) ) {
            $this->config->read($special_config_file);
        }
    */
        // Initialise logging:
        $debug_level = $this->config->value('debug_level');
        $this->log = new Logger("$log_dir/app.log", $debug_level);

        $this->log->write("App", "$this->app_name started.", 3);
    }

}  // class App


?>
