<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->


## app_skeleton-2-5

* Added RSS export for News Articles.
* Added feature to specify current language using Apache's mod_rewrite (saving old session-based method for compatibility).
* Added sendmail_path config parameter to specify sendmail (fake sendmail on windows) location.
* CSV document response added.
* GD2 ImageProcessor added.
* Conditional comments for IE6 added ('minmax.js' script).
* Added Notify status message (with sample).
* Context naming scheme for components is now action-based.
* Popup handling changed: size is set from code, position automatically calculated for new opened windows (App::init_popup()). For popups with static content popup=1 URL parameter is required.
* Filters insertion moved to insert_filters() method of appropriate DbObject-derived classes.
* Using DbObject::$_template_var_prefix in DbObject::print_values().
* Page titles and menu item captions may have HTML tags (App::print_raw_values() is used by default, take care of HTML escaping in lang/global files).
* edit_form.html and view_info.html no more used.
* Added feature to fill select/radio group lists using DbObject's method.
* Added DbObject::has_nonset_filters().
* Added get_full_name() and get_full_name_reversed().
* Response: open_inline renamed to is_attachment.
* Menu: Added methods for removing/hiding menu items, added processing of optional parameter 'item_names_to_hide' for App::print_menu().
* Added automatic values setting for fields created/updated if they exist in DbObject.
* Added more validate conditions.
* Added feature to select what initial data to insert in SetupApp with truncating corresponding table.

## app_skeleton-2-4

* Dynamic loading classes and components added.
* Image processing is reimplemented in ImageProcessor component.
* Added initial application with users, news, categories, products as example of classes usage for application development.
* Added 'Remember me' feature to user login form.
* Added 'like_many' filter type.
* Reading typed CGI values code moved from DbObject to App.
* DbObject::read() log output is much more verbose now.
* DbObject::read() and DbObject::update() optimized, DbObject remembers its original/initial values.
* Added contexts to DbObject's read(), save(), store(), update().
* Logger debug_level is constant-based now, available values: DL_FATAL_ERROR, DL_ERROR, DL_WARNING, DL_INFO, DL_DEBUG, DL_EXTRA_DEBUG.
* SelectQuery log output is greatly enhanced.
* Added App::fetch_rows_list() for speed optimization.
* Action naming convention changed: pg_view_news_articles -> pg_news_articles, pg_edit_news_article -> pg_news_article_edit.
* Action functions naming convention changed: now using prefix 'action_'.
* Added inheritance support to DbObject.
* DbObject-derived classes have 'Table' suffix.
* Added OrderedDbObject for tables with strict ordering.
* Added ImageTable is derived from FileTable now.
* Converted language resources from txt to php.
* Added support for per-template language resources.
* Language resources have prefix 'lang:' in templates.
* Global template variables have prefix 'global:'.
* Changed delimiter in config, language resources and templates from '_' to '.'.
* Made possible to use custom prefixes in template variables.
* Now param() returns null if array was passed to CGI, param_array() should be used to get that array.
* Added 'Create/Update SQL script' preview before execution in SetupApp.
* Changed way of forms posting: single action processes different 'command' instead of different actions for each command (view, edit, update, delete, delete_image).
* POST parameters are accessed first before GET parameters now by param().
* Added App::create_self_action_redirect_response().
* Template parser speed improved.
* Templates directory structure reorganization.
* New templates layout (XHTML/CSS).
* Now runs on php5 with php4 compatibility mode turned on.
* Added initial web_install script configs (release, test).
* Library improvements/bugfixes/refactoring.

## app_skeleton-2-3

* Added n-level menu xml class.
* Enhancements.
* Bugfixes.

## app_skeleton-2-2

* Values printing moved from DbObject to App.
* Client-side validations added.
* MySQL tables dump download added to Setup area.
* Library improvements/bugfixes/refactoring.

## app_skeleton-2-1

* Library improvements/redesign.
* Default encoding for application and database is UTF-8 now.

## app_skeleton-2-0

* Library improvements/redesign.

## app_skeleton-1-1

* Improvements.

## app_skeleton-1-0

* Initial release of app_skeleton with imported library PHPLib-1.

