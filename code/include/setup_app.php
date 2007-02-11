<?php

class SetupApp extends CustomApp {

    function SetupApp() {
        parent::CustomApp("SetupApp", "setup");

        $this->set_current_lang($this->dlang);

        $a = array("roles" => array("admin"));

        $this->actions = array(
            "pg_static" => $a,
            
            "pg_index" => $a,

            "create_update_tables" => $a,
            "delete_tables" => $a,
            "pg_tables_dump" => $a,
            "pg_tables_dump_url" => $a,
            "pg_tables_dump_view" => $a,
            "download_tables_dump" => $a,
            
            "insert_initial_data" => $a,
            "insert_test_data" => $a,
        );
    }
//
    function get_page_templates_dir() {
        return "templates/__setup";
    }
//
    function get_user_role($user = null) {
        return $this->get_http_auth_user_role();
    }

    function run_access_denied_action() {
        $this->create_http_auth_html_document_response($this->get_lang_str("setup_auth_realm"));
    }
//
    function action_pg_index() {
        $this->print_static_page("index", "body");
    }
//
    function action_insert_initial_data() {
        $this->insert_initial_users();
        $this->add_session_status_message(new OkStatusMsg("initial_data_inserted"));
        $this->create_self_redirect_response();
    }

    function insert_initial_users() {
        $user = $this->create_db_object("User");

        $user->login = "admin";
        $user->password = "";
        $user->first_name = "Admin";
        $user->last_name = "";
        $user->email = $this->get_config_value("admin_email_to");
        $user->role = "admin";
        $user->is_confirmed = 1;
        $user->is_active = 1;
        $user->store();
    }
//
    function action_insert_test_data() {
        $this->insert_test_users();
        $this->insert_test_news_articles();
        $this->add_session_status_message(new OkStatusMsg("test_data_inserted"));
        $this->create_self_redirect_response();
    }

    function insert_test_users() {
        $user = $this->create_db_object("User");

        $user->login = "user";
        $user->password = "";
        $user->first_name = "Fn";
        $user->last_name = "Ln";
        $user->email = $this->get_config_value("admin_email_to");
        $user->role = "user";
        $user->is_confirmed = 1;
        $user->is_active = 1;
        $user->store();

        $user->login = "user_not_active";
        $user->password = "";
        $user->first_name = "FnNotActive";
        $user->last_name = "LnNotActive";
        $user->email = $this->get_config_value("admin_email_to");
        $user->role = "user";
        $user->is_confirmed = 1;
        $user->is_active = 0;
        $user->store();

        $user->login = "user_not_confirmed";
        $user->password = "";
        $user->first_name = "FnNotConfirmed";
        $user->last_name = "LnNotConfirmed";
        $user->email = $this->get_config_value("admin_email_to");
        $user->role = "user";
        $user->is_confirmed = 0;
        $user->is_active = 0;
        $user->store();
    }
    function insert_test_news_articles() {
        $news_article = $this->read_id_fetch_db_object("NewsArticle");
        $news_article->created = "2004-06-20";
        $news_article->title_it = "IT: Integer id ante dignissim lacus elementum dapibus.";
        $news_article->body_it =
            "Integer id ante dignissim lacus elementum dapibus. " .
            "Sed interdum porttitor diam. Class aptent taciti sociosqu " .
            "ad litora torquent per conubia nostra, per inceptos hymenaeos. " .
            "<a href=\"http://www.google.com/\">GOOGLE</a> " .
            "Fusce egestas, purus ut pulvinar ultrices, nulla sem " .
            "iaculis nulla, vitae bibendum mauris ligula eu elit. " .
            "Pellentesque eu augue. Maecenas sed metus eget urna " .
            "tristique posuere. Sed odio tortor, faucibus sit amet, " .
            "tincidunt quis, ornare rutrum, nulla. Nunc luctus, nisl " .
            "at facilisis mollis, ipsum magna ultrices sem, vel hendrerit " .
            "lacus felis at eros. Fusce eros. Curabitur fermentum, quam " .
            "vel gravida egestas, justo lorem mollis magna, nec euismod " .
            "nibh pede et libero. Sed semper, ante at pulvinar tempor, " .
            "nisl metus bibendum augue, sit amet adipiscing lectus quam eu " .
            "urna. Ut lectus erat, iaculis nec, adipiscing non, " .
            "elementum sit amet, erat.";
        $news_article->title_en = "EN: Integer id ante dignissim lacus elementum dapibus.";
        $news_article->body_en =
            "Vivamus ut arcu in nunc interdum mattis. Suspendisse in felis. " .
            "Morbi sed nulla. Proin ut sapien id turpis pharetra pellentesque. " .
            "<a href=\"http://www.google.com/\">GOOGLE</a> " .
            "Quisque sit amet augue. Pellentesque elit pede, hendrerit suscipit, " .
            "dignissim sed, imperdiet eget, justo. Aliquam at metus. " .
            "Donec imperdiet. In elementum venenatis lectus. Suspendisse " .
            "feugiat posuere ipsum. Etiam metus augue, tincidunt sit amet, " .
            "sodales at, suscipit et, arcu. Mauris consequat justo et erat. " .
            "Sed venenatis bibendum nisl. Class aptent taciti sociosqu ad litora " .
            "torquent per conubia nostra, per inceptos hymenaeos. Vestibulum " .
            "mauris felis, eleifend eget, lacinia eget, blandit nec, lacus.";
        $news_article->store();

        $news_article->created = "2004-06-23";
        $news_article->title_it = "IT: Phasellus nec neque. Morbi massa.";
        $news_article->body_it =
            "Phasellus nec neque. Morbi massa. Quisque sed odio. " .
            "Suspendisse blandit elementum dui. Pellentesque commodo. " .
            "Mauris massa nisl, placerat eu, eleifend at, dictum vel, urna. " .
            "Sed venenatis, dui id aliquet ornare, justo urna nonummy dui, " .
            "eu lacinia leo neque sed nisl. Aenean quis lectus et lectus " .
            "ornare auctor. Fusce vitae nunc. Curabitur molestie felis a " .
            "purus imperdiet ornare. Duis ut elit sed massa condimentum interdum. " .
            "Curabitur libero. Nam mauris. Nam tincidunt. Cum sociis " .
            "natoque penatibus et magnis dis parturient montes, " .
            "nascetur ridiculus mus. In ultricies pharetra tellus.";
        $news_article->title_en = "EN: Phasellus nec neque. Morbi massa.";
        $news_article->body_en =
            "Suspendisse pulvinar ultricies enim. Duis pharetra. " .
            "Cras venenatis nisl molestie urna. Proin vel nulla vitae " .
            "dolor porta iaculis. Pellentesque felis risus, sollicitudin " .
            "nec, ultrices non, fermentum eu, lacus. Nulla facilisi. " .
            "Maecenas placerat. Duis massa felis, ullamcorper sit amet, " .
            "gravida sed, facilisis in, dolor. Nunc turpis lorem, " .
            "eleifend eget, sodales non, fringilla consequat, sem. " .
            "Vestibulum nulla. Fusce nunc purus, auctor quis, varius ac, " .
            "commodo in, sem. Duis id tortor. Pellentesque habitant morbi " .
            "tristique senectus et netus et malesuada fames ac turpis egestas. " .
            "Nulla facilisi. Nunc laoreet, erat vitae ornare elementum, " .
            "nisl nisl egestas odio, non lobortis massa ipsum vitae dui.";
        $news_article->store();

        $news_article->created = "2004-06-24";
        $news_article->title_it = "IT: Nam molestie lectus vitae tellus.";
        $news_article->body_it =
            "Nam molestie lectus vitae tellus. Etiam molestie placerat " .
            "tellus. Etiam ac orci eget ipsum hendrerit rutrum. In eros. " .
            "Proin elit metus, accumsan quis, placerat eu, convallis at, " .
            "ligula. Proin cursus enim ut velit. Curabitur nulla nulla, " .
            "tristique eget, nonummy in, facilisis vitae, est. Quisque " .
            "molestie, enim venenatis commodo vestibulum, felis nisl cursus " .
            "odio, eu aliquam leo diam quis mauris. Aliquam eu nulla eget " .
            "enim feugiat posuere. Maecenas vehicula, turpis ac lobortis " .
            "dignissim, ante augue blandit augue, a vulputate dui leo " .
            "eu nunc. Aliquam id diam. Etiam odio. Mauris ac ante. In hac " .
            "habitasse platea dictumst. Etiam accumsan urna id dolor. " .
            "Mauris pharetra eleifend mi. Quisque quis purus quis " .
            "purus malesuada rhoncus.";
        $news_article->title_en = "EN: Nam molestie lectus vitae tellus.";
        $news_article->body_en =
            "Nulla magna nulla, dignissim at, dictum in, tincidunt eu, " .
            "mauris. Curabitur eu nisl. Curabitur a magna. Proin elit " .
            "mi, fringilla vel, pretium quis, mollis feugiat, enim. " .
            "Mauris eget lorem. Nullam lorem. Nam aliquet, dui porttitor " .
            "imperdiet nonummy, ligula urna sagittis ante, quis condimentum " .
            "enim odio vel ante. Nulla facilisi. Maecenas non dui. " .
            "In venenatis ullamcorper odio. Cum sociis natoque penatibus " .
            "et magnis dis parturient montes, nascetur ridiculus mus. " .
            "Morbi eros. Quisque luctus neque et justo. Nullam facilisis velit. " .
            "Curabitur at odio. Sed vel justo. Aenean suscipit.";
        $news_article->store();
    }

}

?>