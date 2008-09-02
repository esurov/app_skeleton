<?php
return array(
    // Languages
    "en" => "English",
    "en_native" => "English",
    "it" => "Italian",
    "it_native" => "Italiano",
    "de" => "German",
    "de_native" => "Deutsch",

    // Status messages and parts of page
    "any" => "Any",
    "all" => "All",
    "choose_one" => "Choose one:",
    "yes" => "Yes",
    "no" => "No",
    "none" => "None",
    "other" => "Other",
    "not_specified" => "Not specified",

    "pager.pages_title" => "Pages:",
    "pager.prev_page" => "&lt;&lt;&nbsp;Previous",
    "pager.next_page" => "Next&nbsp;&gt;&gt;",

    "status_msg.cannot_delete_record_because_it_has_restrict_relations" => "Cannot delete {%main_obj_name%} because it has links to {%dep_objs_info_str%}",

    // Static head/page titles and their menu items
    "page_title.access_denied" => "Access denied!",

    "guest_menu_item.index" => "Home",
    "admin_menu_item.index" => "Home",
    "user_menu_item.index" => "Home",
    "head_page_title.index" => "Home",

    "guest_menu_item.sample_static_pages" => "SampleStaticPages",

    "guest_sub_menu_item.sample_static_page1" => "SampleStaticPage1",
    "page_title.static.sample_page1" => "SampleStaticPage1",

    "guest_sub_menu_item.sample_static_page2" => "SampleStaticPage2",
    "page_title.static.sample_page2" => "SampleStaticPage2",
    
    "guest_sub_sub_menu_item.sample_static_page2_1" => "SampleStaticPage2_1",
    "page_title.static.sample_page2_1" => "SampleStaticPage2_1",

    "guest_sub_sub_menu_item.sample_static_page2_2" => "SampleStaticPage2_2",
    "page_title.static.sample_page2_2" => "SampleStaticPage2_2",

    "guest_sub_menu_item.sample_popup" => "SamplePopup",
    "guest_sub_menu_item.sample_new_window" => "SampleNewWindow",

    "guest_sub_menu_item.sample_static_pages2" => "SampleStaticPages2",

    // Login/Logout
    "page_title.login" => "Log In",

    "status_msg.resource_access_denied" => "Requested page cannot be displayed. Please use your access info to login.",
    "status_msg.logged_out" => "Logout completed successfully",

    "status_msg.user.login_or_password_unknown" => "Bad login \"{%login%}\" or password",
    "status_msg.user.disabled_by_admin" => "User \"{%login%}\" is currently disabled by site administrator",
    "status_msg.user.not_confirmed_yet" => "User \"{%login%}\" is not confirmed yet. Use confirmation link from your signup confirmation email.",

    // Signup/Users
    "user" => "user",
    "users" => "users",

    "guest_menu_item.signup" => "Signup",
    "user_menu_item.my_account" => "My account",
    "admin_menu_item.my_account" => "My account",
    "admin_menu_item.users" => "Users",

    "page_title.signup" => "Signup",
    "page_title.signup_almost_completed" => "New account created",
    "page_title.confirm_signup" => "Account confirmed",
    "page_title.my_account" => "My account",
    "page_title.users_admin" => "Users",
    "page_title.user_edit_new_admin" => "Add user",
    "page_title.user_edit_admin" => "Edit user",

    "page_title.static.policy_terms_of_use" => "Terms of use",
    "page_title.static.policy_terms_and_conditions" => "Terms and conditions",
    "page_title.static.policy_privacy" => "Privacy policy",
    "page_title.static.policy_disclaimer" => "Disclaimer",

    "status_msg.user.added" => "User added",
    "status_msg.user.updated" => "User updated",
    "status_msg.user.deleted" => "User deleted",
    "status_msg.user.account_updated" => "Account updated",
    
    "status_msg.user.login_empty" => "Specify login",
    "status_msg.user.login_exists" => "Login \"{%login%}\" already exists, specify another one",
    "status_msg.user.password_empty" => "Specify password",
    "status_msg.user.password_at_least_6_chars" => "Password must be at least 6 characters",
    "status_msg.user.passwords_do_not_match" => "Entered passwords do not match",
    "status_msg.user.email_empty" => "Specify email",
    "status_msg.user.email_bad" => "Email is invalid",
    "status_msg.user.email_exists" => "Email is already used with another account",
    "status_msg.user.first_name_empty" => "Specify first name",
    "status_msg.user.last_name_empty" => "Specify last name",
    "status_msg.user.role_empty" => "Specify user role",
    "status_msg.user.should_accept_agreement" => "You should check \"Accept agreement\" to continue",
    "status_msg.user.cannot_delete_main_admin" => "Cannot delete main administrator account",

    "email_signup_form_processed_subject" => "Welcome to !!AppSkeleton website",

    "user_role.user" => "User",
    "user_role.admin" => "Administrator",

    // Recover password
    "page_title.recover_password" => "Recover password",
    "page_title.recover_password_sent" => "Password sent",

    "status_msg.recover_password.login_or_email_empty" => "Specify login or email",
    "status_msg.recover_password.bad_email" => "Bad email specified",
    "status_msg.recover_password.no_account_with_login" => "Cannot find account with login \"{%login%}\"",
    "status_msg.recover_password.no_account_with_email" => "Cannot find account with email \"{%email%}\"",

    "email_recover_password_form_processed_subject" => "Password recovery for !!AppSkeleton website",

    // News articles
    "news_article" => "news article",
    "news_articles" => "news articles",

    "guest_menu_item.news" => "News",
    "admin_menu_item.news" => "News",

    "page_title.news_articles" => "News",
    "page_title.news_articles_admin" => "News",
    "page_title.news_article_view" => "View news article",
    "page_title.news_article_edit_admin_new" => "Add news article",
    "page_title.news_article_edit_admin" => "Edit news article",

    "status_msg.news_article.added" => "News article added",
    "status_msg.news_article.updated" => "News article updated",
    "status_msg.news_article.deleted" => "News article deleted",
    "status_msg.news_article.image_deleted" => "News article image deleted",
    "status_msg.news_article.file_deleted" => "News article file deleted",

    "status_msg.news_article.title_empty" => "Specify title",
    "status_msg.news_article.image_empty" => "Please upload image",
    "status_msg.news_article.image_bad" => "Please upload valid image",
    "status_msg.news_article.file_empty" => "Please upload file",
    "status_msg.news_article.file_bad" => "Please upload valid file",
    "status_msg.news_article.file_max_size_reached" => "Uploaded file size is more than 2 Mb",
    "status_msg.news_article.files_max_total_size_reached" => "Total uploaded files size is more than 100 Mb",

    // Newsletters
    "newsletter" => "newsletter",
    "newsletters" => "newsletters",

    "admin_menu_item.newsletters" => "Newsletters",
    "admin_sub_menu_item.newsletters" => "Newsletters",
    "admin_sub_menu_item.newsletter_category" => "Newsletter categories",
    "admin_sub_menu_item.newsletter_edit" => "Create newsletter",

    "page_title.newsletters" => "Newsletters",
    "page_title.newsletter_view" => "View newsletter",
    "page_title.newsletter_edit_new" => "Create newsletter",
    "page_title.newsletter_edit" => "Edit newsletter",
    "page_title.newsletter_categories" => "Newsletter categories",
 
    "status_msg.newsletter.added" => "Newsletter sent",
    "status_msg.newsletter.updated" => "Newsletter updated",
    "status_msg.newsletter.deleted" => "Newsletter deleted",
    "status_msg.newsletter.image_deleted" => "Newsletter image deleted",
    "status_msg.newsletter.file_deleted" => "Newsletter file deleted",

    "status_msg.newsletter.title_empty" => "Specify title",
    "status_msg.newsletter.body_empty" => "Specify body",
    "status_msg.newsletter.category_empty" => "Category is empty",
    "status_msg.newsletter.image_empty" => "Please upload image",
    "status_msg.newsletter.image_bad" => "Please upload valid image",
    "status_msg.newsletter.file_empty" => "Please upload file",
    "status_msg.newsletter.file_bad" => "Please upload valid file",
    "status_msg.newsletter.file_max_size_reached" => "Uploaded file size is more than 2 Mb",
    "status_msg.newsletter.files_max_total_size_reached" => "Total uploaded files size is more than 100 Mb",
    "status_msg.newsletter.send_mail_failed" => "!!Function \"Send mail\" not realized",
    "status_msg.newsletter.send_emails_done" => "!!Emails were sent",

    // Newsletter categories
    "newsletter_category" => "newsletter category",
    "newsletter_categorys" => "newsletter categories",

    "admin_menu_item.newsletter_category" => "Newsletter categories",

    "page_title.newsletter_categories" => "Newsletter categories",
    "page_title.newsletter_category_edit_new" => "Add newsletter category",
    "page_title.newsletter_category_edit" => "Edit category",

    "status_msg.newsletter_category.added" => "Newsletter category added",
    "status_msg.newsletter_category.updated" => "Newsletter category updated",
    "status_msg.newsletter_category.deleted" => "Newsletter category deleted",

    "status_msg.newsletter_category.name_empty" => "Specify newsletter category name",

    // User subscription
    "user_subscription" => "newsletter subscription",
    "user_subscriptions" => "newsletter subscriptions",

    "user_menu_item.user_subscription" => "My newletter subscriptions",

    "page_title.user_subscription" => "My newsletter subscriptions",

    "status_msg.user_subscription.updated" => "Newsletter subscriptions updated",

    // Contact form
    "guest_menu_item.contact_form" => "Contacts",

    "page_title.contact_form" => "Contact form",

    "status_msg.contact_info.processed" => "Your contact info was processed. Thank you!",
    "status_msg.contact_info.first_name_empty" => "Please input your first name",
    "status_msg.contact_info.last_name_empty" => "Please input your last name",
    "status_msg.contact_info.email_empty" => "Please input your email",
    "status_msg.contact_info.email_bad" => "Please input correct email address",
    "status_msg.contact_info.message_text_empty" => "Input message",

    // Categories
    "category1" => "category1",
    "category1s" => "categories1",
    "category2" => "category2",
    "category2s" => "categories2",
    "category3" => "category3",
    "category3s" => "categories3",

    "admin_menu_item.categories" => "Categories",

    "page_title.categories" => "Categories",
    "page_title.categories_category1_edit_new" => "Add category1",
    "page_title.categories_category1_edit" => "Edit category1",
    "page_title.categories_category2_edit_new" => "Add category2",
    "page_title.categories_category2_edit" => "Edit category2",
    "page_title.categories_category3_edit_new" => "Add category3",
    "page_title.categories_category3_edit" => "Edit category3",

    "status_msg.category1.added" => "Category1 added",
    "status_msg.category1.updated" => "Category1 updated",
    "status_msg.category1.deleted" => "Category1 deleted",

    "status_msg.category1.name_empty" => "Specify category1 name",

    "status_msg.category2.added" => "Category2 added",
    "status_msg.category2.updated" => "Category2 updated",
    "status_msg.category2.deleted" => "Category2 deleted",

    "status_msg.category2.name_empty" => "Specify category2 name",

    "status_msg.category3.added" => "Category3 added",
    "status_msg.category3.updated" => "Category3 updated",
    "status_msg.category3.deleted" => "Category3 deleted",

    "status_msg.category3.name_empty" => "Specify category3 name",

    // Products
    "product" => "product",
    "products" => "products",

    "admin_menu_item.products" => "Products",

    "page_title.products" => "Products",
    "page_title.product_edit_new" => "Add product",
    "page_title.product_edit" => "Edit product",

    "status_msg.product.added" => "Product added",
    "status_msg.product.updated" => "Product updated",
    "status_msg.product.deleted" => "Product deleted",

    "status_msg.product.category1_empty" => "Category1 empty",
    "status_msg.product.category2_empty" => "Category2 empty",
    "status_msg.product.category3_empty" => "Category3 empty",
    "status_msg.product.name_empty" => "Specify product name",
);
?>