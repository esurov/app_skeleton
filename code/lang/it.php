<?php
return array(
    // Languages
    "en" => "Inglese",
    "en_native" => "English",
    "it" => "Italiano",
    "it_native" => "Italiano",
    "de" => "Tedesco",
    "de_native" => "Deutsch",

    // Static head and page titles
    "page_title.access_denied" => "Accesso non autorizzato!",

    "head_page_title.pg_index" => "Home",

    "page_title.pg_static.policy_terms_of_use" => "Condizioni di utilizzo",
    "page_title.pg_static.policy_terms_and_conditions" => "Termini e condizioni",
    "page_title.pg_static.policy_privacy" => "Politica sulla privacy",
    "page_title.pg_static.policy_disclaimer" => "Responsabilità",

    "page_title.pg_static.sample_page1" => "SampleStaticPage1",
    "page_title.pg_static.sample_page2" => "SampleStaticPage2",
    "page_title.pg_static.sample_page2_1" => "SampleStaticPage2_1",
    "page_title.pg_static.sample_page2_2" => "SampleStaticPage2_2",

    // Menu items
    "guest_menu_item.index" => "Home",
    "guest_menu_item.sample_static_pages" => "SampleStaticPages",
    "guest_sub_menu_item.sample_static_page1" => "SampleStaticPage1",
    "guest_sub_menu_item.sample_static_page2" => "SampleStaticPage2",
    "guest_sub_menu_item.sample_popup" => "SamplePopup",
    "guest_sub_menu_item.sample_new_window" => "SampleNewWindow",
    "guest_sub_menu_item.sample_static_pages2" => "SampleStaticPages2",
    "guest_sub_sub_menu_item.sample_static_page2_1" => "SampleStaticPage2_1",
    "guest_sub_sub_menu_item.sample_static_page2_2" => "SampleStaticPage2_2",

    "user_menu_item.index" => "Home",

    "admin_menu_item.index" => "Home",

    // Parts of controls
    "any" => "Qualsiasi",
    "all" => "Tutti",
    "choose_one" => "Selezionare:",
    "yes" => "Sì",
    "no" => "No",
    "none" => "None",
    "other" => "Altro",
    "not_specified" => "Non specificato",

    // Status messages and parts of page
    "pager.pages_title" => "Pagine:",
    "pager.prev_page" => "&lt;&lt;&nbsp;!!Precedente",
    "pager.next_page" => "!!Prossimo&nbsp;&gt;&gt;",

    "cannot_delete_record_because_it_has_restrict_relations" => "Impossibile eliminare {%main_obj_name%} perché in relazione con {%dep_objs_info_str%}",

    "status_message.first_name_empty" => "Inserire il nome",
    "status_message.last_name_empty" => "Inserire il cognome",
    "status_message.email_empty" => "Inserire l'email",
    "status_message.email_bad" => "Inserire un indirizzo email valido",
    "status_message.zip_empty" => "Inserire il CAP",
    "status_message.zip_bad" => "Il CAP dovrebbe essere un numero di 5 cifre",
    "status_message.country_empty" => "Inserire la nazione",
    "status_message.city_empty" => "Inserire la città",
    "status_message.phone_empty" => "Inserire il nr. di telefono",
    "status_message.phone_bad" => "Nr. di telefono non valido",
    "status_message.message_text_empty" => "Inserire il messaggio",

    // Login/Logout
    "page_title.pg_login" => "Log In",

    "user.login_or_password_unknown" => "Login \"{%login%}\" o password non corretti",
    "user.not_confirmed_yet" => "L'utente \"{%login%}\" non è ancora confermato. Utilizza il link di conferma che trovi all'interno della email di avvenuta iscrizione.",
    "user.disabled_by_admin" => "L'utente \"{%login%}\" è attualmente disabilitato dall'amministratore di sistema.",
    "status_message.resource_access_denied" => "La pagina richiesta non può essere visualizzata. Prego effettuare l'accesso mediante login/password.",
    "status_message.logged_out" => "Logout effettuato con successo",

    // Signup
    "guest_menu_item.signup" => "Iscriviti",

    "page_title.pg_signup" => "Iscriviti",
    "page_title.pg_signup_almost_completed" => "Il nuovo account stato creato",

    "user.should_accept_agreement" => "Devi selezionare \"Accetta\" per continuare",

    "email_signup_form_processed_subject" => "Benvenuto al sito !!AppSkeleton",

    // Confirm signup
    "page_title.confirm_signup" => "Account è stato confermato",

    // Recover password
    "page_title.pg_recover_password" => "Ritrovamento password",

    "recover_password.login_or_email_empty" => "Login o indirizzo email non specificati",
    "recover_password.bad_email" => "Indirizzo email non corretto",
    "recover_password.no_account_with_login" => "Impossibile trovare l'account avente login \"{%login%}\"",
    "recover_password.no_account_with_email" => "Impossibile trovare l'account avente indirizzo email \"{%email%}\"",

    "email_recover_password_form_processed_subject" => "Recupera la password per il sito !!AppSkeleton",

    // Recover password sent
    "page_title.pg_recover_password_sent" => "Password è stata inviata",

    // News articles
    "news_article" => "notizia",
    "news_articles" => "notizie",

    "guest_menu_item.news" => "Notizie",
    "admin_menu_item.news" => "Notizie",

    "page_title.pg_news_articles" => "Notizie",
    "page_title.pg_news_article_view" => "Visualizza notizia",
    "page_title.pg_news_article_edit_new" => "Aggiungi notizia",
    "page_title.pg_news_article_edit" => "Modifica notizia",

    "news_article.added" => "Aggiunta nuova notizia",
    "news_article.updated" => "Notizia è stata modificata",
    "news_article.deleted" => "Notizia è stata eliminata",
    "news_article.image_deleted" => "L'immagine relativa all'articolo è stata cancellata",
    "news_article.file_deleted" => "Il file relativo all'articolo è stata cancellato",

    "news_article.title_empty" => "Inserire titolo",
    "news_article.image_empty" => "Prego caricare immagine",
    "news_article.image_bad" => "Prego caricare immagine valida",
    "news_article.file_empty" => "Prego caricare file",
    "news_article.file_bad" => "Prego caricare file valido",
    "news_article.file_max_size_reached" => "La dimensione del file caricato supera i 2 Mb",
    "news_article.files_max_total_size_reached" => "La dimensione totale dei file caricati è maggiore di 100 Mb",

    // Contact form
    "guest_menu_item.contact_form" => "Contatti",

    "page_title.pg_contact_form" => "Contatti",
    "contact_form.processed" => "I suoi dati sono stati registrati. Grazie!",

    // Users
    "user" => "utente",
    "users" => "utenti",

    "user_menu_item.user" => "Il mio account",
    "admin_menu_item.users" => "Utenti",

    "page_title.pg_users" => "Utenti",
    "page_title.pg_user_view" => "Visualizza utente",
    "page_title.pg_user_view_my_account" => "Il mio account",
    "page_title.pg_user_edit_new" => "Aggiungi utente",
    "page_title.pg_user_edit" => "Modifica utente",
    "page_title.pg_user_edit_my_account" => "Modifica account",

    "user.added" => "Utente aggiunto",
    "user.updated" => "Utente aggiornato",
    "user.deleted" => "Utente cancellato",
    "user.login_empty" => "Inserire login",
    "user.login_exists" => "Il login inserito esiste già, specificarne un altro",
    "user.password_empty" => "Inserire password",
    "user.password_at_least_6_chars" => "La password deve essere almeno 6 caratteri",
    "user.passwords_do_not_match" => "Le password inserite non corrispondono tra loro",
    "user.email_empty" => "Inserire email",
    "user.email_bad" => "L'indirizzo email specificato non è corretto",
    "user.email_exists" => "L'indirizzo email è attualmente in uso con ul altro account",
    "user.first_name_empty" => "Inserire nome",
    "user.last_name_empty" => "Inserire cognome",
    "user.role_empty" => "Specificare ruolo utente",
    "user.cannot_delete_main_admin" => "Impossibile cancellare l'account principale di amministrazione",

    "user_role.user" => "Utente",
    "user_role.admin" => "Amministratore",

    // Categories
    "category1" => "categoria1",
    "category1s" => "categorie1",
    "category2" => "categoria2",
    "category2s" => "categorie2",
    "category3" => "categoria3",
    "category3s" => "categorie3",

    "admin_menu_item.categories" => "Categorie",

    "page_title.pg_categories" => "Categorie",
    "page_title.pg_categories_category1_edit_new" => "Aggiungi categoria1",
    "page_title.pg_categories_category1_edit" => "Modifica categoria1",
    "page_title.pg_categories_category2_edit_new" => "Aggiungi categoria2",
    "page_title.pg_categories_category2_edit" => "Modifica categoria2",
    "page_title.pg_categories_category3_edit_new" => "Aggiungi categoria3",
    "page_title.pg_categories_category3_edit" => "Modifica categoria3",

    "category1.added" => "Categoria1 aggiunta",
    "category1.updated" => "Categoria1 aggiornata",
    "category1.deleted" => "Categoria1 cancellata",
    "category1.name_empty" => "Inserire il nome della categoria1",
    "category2.added" => "Categoria2 aggiunta",
    "category2.updated" => "Categoria2 aggiornata",
    "category2.deleted" => "Categoria2 cancellata",
    "category2.name_empty" => "Inserire il nome della categoria2",
    "category3.added" => "Categoria3 aggiunta",
    "category3.updated" => "Categoria3 aggiornata",
    "category3.deleted" => "Categoria3 cancellata",
    "category3.name_empty" => "Inserire il nome della categoria3",

    // Products
    "product" => "prodotto",
    "products" => "prodotti",

    "admin_menu_item.products" => "Prodotti",

    "page_title.pg_products" => "Prodotti",
    "page_title.pg_product_edit_new" => "!!Add product",
    "page_title.pg_product_edit" => "!!Edit product",

    "product.added" => "!!Product added",
    "product.updated" => "!!Product updated",
    "product.deleted" => "!!Product deleted",

    "product.category1_empty" => "!!Category1 empty",
    "product.category2_empty" => "!!Category2 empty",
    "product.category3_empty" => "!!Category3 empty",
    "product.name_empty" => "!!Specify product name",
);
?>