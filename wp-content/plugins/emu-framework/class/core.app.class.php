<?php

if( !isset( $_SESSION ) ) session_start();

class emuApp
{
    public $managers = array();
    public $helpers = array();
    public $constants = array();
    public $views = array();

    public $pluginPath;
    public $pluginName;
    public $pluginURL;

    public $emuAppID;
    public $sThemeURL;

    public $classPath;
    public $classes = array();
    public $objects = array();

    public $stylesheets = array();
    public $scripts = array();

    public $poolObjects = false;
    public $countObjectsCreated = 0;
    public $countObjectsPooled = 0;

    public $messages;

    public $dbPrefix            = 'emu_app_';
    public $dbPrefixShared      = 'emu_';

    public $verbose = false;

    public $installed = false;

    // Core settings
    public $sendEmails;
    public $logEmails;
    public $menuName;
    public $menuPosition;
    public $templatingEnabled = false;
    public $emailTemplatesEnabled = false;
    public $useSourceTemplateFiles; // badly named
    public $mailFunction;
    public $hasFeaturedImages = false;

    public $wpAdminEmail;
    public $wpSiteTitle;
    public $wpTagline;

    public $forceInstall = false;

    public $adminPages;
    public $sessionData = array();
    public $shortCodes = array();
    public $dataMessages = array();

    public static $staticTemplateTags = array(  'emu component' => 'emuAppID',
                                                'WP admin email' => 'wpAdminEmail',
                                                'WP site title' => 'wpSiteTitle',
                                                'WP tagline' => 'wpTagline' );

    public $templateTags;

    function __construct( $file_path )
    {
        global $wpdb;

        $this->setAppConfig(); // depracated, use config()
        $this->config();

        $this->pluginPath       = dirname( $file_path );
        $this->pluginName       = basename( $file_path, '.php' );
        $this->pluginURL        = get_bloginfo('url').'/wp-content/plugins/'.$this->pluginName;//todo this should use plugins_url() dug
        $this->pluginURLPath    = '/wp-content/plugins/'.$this->pluginName;
        $this->classPath        = $this->pluginPath.'/class';
        $this->dbPrefix         = $wpdb->prefix.$this->dbPrefix;
        $this->dbPrefixShared   = $wpdb->prefix.$this->dbPrefixShared;
        $this->sThemeURL        = get_bloginfo('stylesheet_directory');

        $this->wpAdminEmail     = get_bloginfo( 'admin_email' );
        $this->wpSiteTitle      = get_bloginfo( 'name' );
        $this->wpTagline        = get_bloginfo( 'description' );
        $this->templateTags     = self::$staticTemplateTags;

        if( $this->hasFeaturedImages )
            add_theme_support( 'post-thumbnails' );

        // Load/register core classes / styles / scripts
        $this->loadCoreClasses();

        add_action( 'init', array( $this, 'loadAppCoreStyles'), 1 );
        add_action( 'init', array( $this, 'loadAppCoreScripts'), 1 );

        add_action( 'init', array( $this, 'loadCoreStyles'), 2 );
        add_action( 'init', array( $this, 'loadCoreScripts'), 2 );

        // Register deactivation
        register_deactivation_hook( $file_path, array( $this, 'uninstall' ) );

        // Register widgets
        add_action( 'widgets_init', array( $this, 'registerPluginWidgets' ) );

        // add_action( 'init', array( $this, 'startStats' ) );
        // add_action( 'wp_footer', array( $this, 'endStats' ) );

        $this->settings = new emuM_Settings( $load_settings = true, $this->emuAppID );

        $this->saveSettings(); // save admin page settings changes

        // Register settings
        $this->registerCoreSettings();

        // Load settings
        $this->loadCoreSettings();

        // Load core admin pages (must be loaded *after* settings have been loaded)
        $this->loadCoreAdminPages();

        // Load any session data
        $this->loadSessionData();

        // Register core custom post types()
        add_action( 'init', array( $this, 'registerCorePostTypes' ), 1 );

        if( $this->templatingEnabled )
            $this->loadManager( 'template', 'emuM_Templates', 'manage.templates.class.php', EMU_FRAMEWORK_PATH.'/class' );

        if( $this->emailTemplatesEnabled )
            $this->loadManager( 'email-template', 'emuM_EmailTemplates', 'manage.emailtemplates.class.php', EMU_FRAMEWORK_PATH.'/class' );

        add_action( 'admin_menu', array( $this, 'addSettingsPage' ) );
        add_action( 'plugins_loaded', array( $this, 'pluginInstalledandLoaded' ) );

        $this->init();
        $this->checkInstall();
        $this->setupWPMenu();
    }

    public function setAppConfig() {} // depracated - use config()
    public function config() {} // to replace setAppConfig()
    public function init() {}

    public function pluginInstalledandLoaded()
    {
        if( !$this->installed &! $this->forceInstall )
        {
            do_action( $this->emuAppID.'_installed_loaded' );
        }
    }

    public function runStats()
    {
        add_action( 'init', array( $this, 'startStats' ) );
        add_action( 'wp_footer', array( $this, 'endStats' ) );
    }

    public function checkInstall()
    {
        if( get_option( $this->emuAppID.'_installed' ) && !$this->forceInstall )
        {
            $this->installed = true;
            return;
        }

        $this->install();

        update_option( $this->emuAppID.'_installed', apply_date_format( 'db' ) );
    }

    public function startStats()
    {
        start_exec_timer();
    }

    public function endStats()
    {
        $total_objects = $this->countObjectsCreated + $this->countObjectsPooled;

        $percentage_pooled = ( $this->countObjectsPooled / $total_objects ) * 100;

        echo $this->menuName.' Stats: Objects ('.$total_objects.'), Pooled ('.$this->countObjectsPooled.', '.apply_number_format( 'percentage', $percentage_pooled )."%)\n";

        stop_exec_timer();

    }

    public function loadCoreAdminPages()
    {
        // none (yet) but should really be handled by the managers
    }

    public function loadAppCoreStyles()
    {
        $this->loadStyle( 'emu-admin', EMU_FRAMEWORK_URL.'/css/emu-admin.css', null, 'is_admin()' );
        $this->registerStyle( 'emu-jquery-ui', EMU_FRAMEWORK_URL.'/css/emu-jquery-ui/emu-jquery-ui.css' );
        $this->registerStyle( 'emu-codemirror-theme', EMU_FRAMEWORK_URL.'/js/codemirror/default.css' );
        $this->registerStyle( 'emu-codemirror', EMU_FRAMEWORK_URL.'/js/codemirror/codemirror.css', array( 'emu-codemirror-theme' ) );
    }

    public function loadAppCoreScripts()
    {
        global $wp_version;

        if( version_compare( $wp_version, '3.5', '<' ) )//was 3
            $this->registerScript( 'emu-jquery-accordion', EMU_FRAMEWORK_URL.'/js/jquery/jquery.accordion.js', array('jquery') );
        // else
            // $this->registerScript( 'emu-jquery-accordion', EMU_FRAMEWORK_URL.'/js/jquery/jquery.accordion.1.9.2.js', array('jquery') );

        $this->registerScript( 'emu-json', EMU_FRAMEWORK_URL.'/js/json2.js' );
        $this->registerScript( 'emu-codemirror', EMU_FRAMEWORK_URL.'/js/codemirror/codemirror.js' );
    }

    public function loadCoreStyles() {}
    public function loadCoreScripts() {}

    public function loadCoreClasses()
    {
        $this->loadClass( 'emuDB', 'core.db.class.php', null, EMU_FRAMEWORK_PATH.'/class' );

        $this->registerClass( 'emuDbEntity', 'core.dbentity.class.php', 'emuDB', EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuDbPostEntity', 'core.dbpostentity.class.php', 'emuDbEntity', EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuManager', 'core.manager.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuHelper', 'core.helper.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuConstants', 'core.constants.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuConstantsGroup', 'core.constantsgroup.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuUI', 'core.ui.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuView', 'core.view.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuProcessor', 'core.processor.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->registerClass( 'emuPost', 'core.post.class.php', null, EMU_FRAMEWORK_PATH.'/class' );

        $this->loadClass( 'emuM_Settings', 'manage.settings.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->loadClass( 'emuM_Templates', 'manage.templates.class.php', array('emuManager'), EMU_FRAMEWORK_PATH.'/class' );
        $this->loadClass( 'emuSettingsGroup', 'core.settingsgroup.class.php', null, EMU_FRAMEWORK_PATH.'/class' );
        $this->loadClass( 'emuSetting', 'core.setting.class.php', null, EMU_FRAMEWORK_PATH.'/class' );

        $this->loadClass( 'emuEmail', 'core.email.class.php', 'emuDbPostEntity', EMU_FRAMEWORK_PATH.'/class' );

    }

    public function registerCoreSettings()
    {
        $coreSettings = $this->settings->createSettingsGroup( $this->emuAppID, $this->pluginName.' Settings' );

        $coreSettings->addSetting( 'menuName', $this->pluginName, 'Menu Name', 1, 'text' );
        $coreSettings->addSetting( 'menuPosition', null, 'Menu Position', 2, 'number' );
        $coreSettings->addSetting( 'sendEmails', true, 'Send Emails', 14, 'boolean' );
        $coreSettings->addSetting( 'logEmails', true, 'Log Emails', 7, 'boolean' );
        $coreSettings->addSetting( 'mailFunction', 'mail', 'Send Mail Function', 4, array( 'mail' => 'Normal', 'wp' => 'WP Mail' ) );

        // $coreSettings->addSetting( 'templatingEnabled', false, 'Templating Enabled', 8, 'boolean' );
        $coreSettings->addSetting( 'useSourceTemplateFiles', false, 'Cache Template Files Disabled', 18, 'boolean' );

        $this->settings->addSettingsGroup( $coreSettings );
        $this->settings->saveSettings();
    }


    public function loadCoreSettings()
    {
        $settings = $this->settings->getSettingsGroup( $this->emuAppID )->getSettings();

        foreach( $settings as $settingName => $setting )
        {
            $this->$settingName = $setting->value;
        }
    }

    public function registerCorePostTypes()
    {
        // Has the post type already been registered by another emu app?
        if( post_type_exists( 'emu-email' ) ) return;

        /// Emails
        //////////////////////////////////////////////////////////////
        $labels = array(
            'name' => 'Emails',
            'singular_name' => 'Email',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Email',
            'edit_item' => 'Edit Email',
            'new_item' => 'New Email',
            'view_item' => 'View Email',
            'search_items' => 'Search Emails',
            'not_found' => 'No emails found',
            'not_found_in_trash' => 'No emails found in Trash',
            'parent_item_colon' => ''
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search ' => true,
            'show_ui' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'email'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array( 'title', 'excerpt', 'editor', 'author', 'custom-fields', 'revisions' ),
        );

        register_post_type( 'emu-email', $args );

        $labels = array(
          'name' => 'Email Categories',
          'singular_name' => 'Email Category',
          'search_items' =>  'Search Email Categories',
          'all_items' => 'All Email Categories',
          'parent_item' => 'Parent Category',
          'parent_item_colon' => 'Parent Category:',
          'edit_item' => 'Edit Email Category',
          'update_item' => 'Update Email Category',
          'add_new_item' => 'Add New Email Category',
          'new_item_name' => 'New Email Category Name',
          'menu_name' => 'Email Categories',
        );

        $args = array(
          'hierarchical' => true,
          'labels' => $labels,
          'show_ui' => true,
          'query_var' => true,
          'rewrite' => array( 'slug' => 'email-category' ),
        );

        register_taxonomy( 'email-category', array('emu-email'), $args );

    }

    public function loadScript( $name, $path = null, $dependants = null, $condition = null, $post_type = null, $args = array()  )
    {
        $this->registerScript( $name, $path, $dependants, $condition, $post_type, true );
    }

    public function registerScript( $name, $path = null, $dependants = null, $condition = null, $post_type = null, $load = false, $args = array() )
    {
        extract( wp_parse_args( $args, array(
                'ver' => false //String specifying the script version number, if it has one. Defaults to false.
                ,'in_footer' => false //If true, script goes in wp_footer(). Note that you have to enqueue your script before wp_head is run, even if it will be placed in the footer.
            ) ) );

        if( is_array( $name ) )
        {
            $arr_scripts = $name;

            foreach( $arr_scripts as $script )
                call_user_func_array( array( $this, 'registerScript' ), $script );

            return;
        }

        if( $condition )
        {
            $return = eval( "return $condition;" );

            if( !$return ) return;
        }

        wp_register_script( $name, $path, $dependants, $ver, $in_footer );

        if( $post_type )
        {
            if( $this->isPostAdminPage( $post_type ) ) wp_enqueue_script( $name );
        }
        elseif ( $load ) wp_enqueue_script( $name );
    }

    function saveSettings()
    {
        $action = request_val( 'e-action' );
        $plugin = request_val( 'e-plugin' );

        if( $plugin !== $this->emuAppID ) return; // wrong emu component

        switch( $action )
        {
            case 'saveSettings':

                $setting_name = post_val( 'setting_name' );
                $setting_group = post_val( 'setting_group' );

                for( $n = 0; $n < count( $setting_name ); $n++ )
                {
                    $settingsGroup = $this->settings->getSettingsGroup( $setting_group[ $n ] );

                    $setting = $settingsGroup->getSetting( $setting_name[ $n ] );

                    $setting_value = post_val( str_replace(' ', '_', $setting_name[ $n ]) );

                    switch( $setting->type )
                    {
                        case 'boolean':

                            $setting_value = $setting_value ? true : false;
                            break;

                        case 'password':

                            if( empty( $setting_value ) ) $setting_value = $setting->value; // keep the value the same
                            break;
                    }

                    $setting->value = $setting_value;
                }

                $this->settings->saveSettings();

                break;

        }
    }

    public function loadStyle( $name, $path = null, $dependants = null, $condition = null, $post_type = null )
    {
        if( !$this->registerStyle( $name, $path, $dependants, $condition, $post_type ) ) return false;

        $style = (object) $this->stylesheets[ $name ];

        if( $style->condition )
        {
            $return = eval( "return {$style->condition};" );

            if( !$return ) return false;
        }

        if( $style->post_type )
        {
            if( !$this->isPostAdminPage( $style->post_type ) ) return false;
        }

        wp_enqueue_style( $name );

        return true;
    }

    public function registerStyle( $name, $path = null, $dependants = null, $condition = null, $post_type = null )
    {
        if( is_array( $name ) )
        {
            $arr_styles = $name;

            foreach( $arr_styles as $style )
            {
                call_user_func_array( array( $this, 'registerStyle' ), $script );
            }

            return;
        }

        if( isset( $this->stylesheets[ $name ] ) ) return true;

        $this->stylesheets[ $name ] = array(    'name' => $name,
                                                'path' => $path,
                                                'dependants' => $dependants,
                                                'condition' => $condition,
                                                'post_type' => $post_type );

        wp_register_style( $name, $path, $dependants );

        return true;
    }

    public function isPostAdminPage( $post_type = null )
    {
        if( !$post_type ) return false;

        global $pagenow, $typenow, $post;

        if ( empty( $typenow ) && !empty( $_GET['post'] ) )
        {
            $post = get_post( $_GET['post'] );
            $typenow = @$post->post_type;
        }
        elseif ( empty( $typenow ) && ( $pagenow == 'post-new.php' ) )
        {
            $typenow = @$_GET['post_type'];
        }

        if( $typenow == $post_type )
        {
            if ( @$pagenow == 'post-new.php' OR @$pagenow == 'post.php' ) return true;
        }

        return false;
    }


    function registerPluginWidgets()
    {
        $widgets = $this->getWidgets();

        foreach( $widgets as $widget )
        {
            $widget = (object) $widget;

            include_once( $widget->path );

            if( class_exists( $widget->className ) )
                register_widget( $widget->className );
        }
    }

    function setupWPMenu()
    {
        add_action( 'admin_menu', array( $this, 'createWPMenu' ) );
    }

    function registerAdminPage( $terms = array() )
    {
        $defaults = array( 'name' => '', 'filename' => '', 'directory' => $this->pluginPath.'/admin/', 'styles' => '', 'scripts' => '', 'group' => $this->emuAppID.'Group', 'capability' => 'administrator', 'position' => 1, 'view' => false );

        $terms = wp_parse_args( $terms, $defaults );

        $this->adminPages[] = $terms;
    }

    function registerAdminPageForView( $page_name, $position = 1)
    {
        // $filename = "view.".$this->filenameify($page_name).".class.php";
        $this->registerView( $page_name,  $class = '', $filename = '', $path = $this->pluginPath.'/admin' );

        // registerView( $name, $class = '', $filename = '', $path = null )
        $this->registerAdminPage( array( 'name' => $page_name, 'filename' => false, 'position' => $position, 'view' => $page_name ) );
    }

    public function addSettingsPage()
    {
        $page = (object) array( 'directory' => EMU_FRAMEWORK_PATH.'/admin/', 'filename' => 'settings.php'  );

        add_options_page(   $this->menuName,
                            $this->menuName,
                            'manage_options',
                            $this->emuAppID.'_settings',
                            $this->genPageLoader( $page ) );
    }

    function createWPMenu()
    {
        // re-order according to the position
        $tmp_pages = array();

        for( $n = 0; $n < count( $this->adminPages ); $n++ )
        {
            $tmp_pages[ $n ] = $this->adminPages[$n]['position'];
        }

        asort( $tmp_pages );

        $group_default = true;

        foreach( $tmp_pages as $index => $position )
        {
            $this->addAdminPage( $this->adminPages[ (int) $index], $group_default );
            $group_default = false;
        }
    }

    function genPageLoader( $page, $is_group_default = false )
    {
        $class_name = get_class($this);

        global $$class_name;

        if( !is_object( $$class_name ) ) $$class_name = $this;

        $page_loader =  'if (!current_user_can("manage_options")) wp_die( __("You do not have sufficient permissions to access this page.") ); '.
                        '$emuAppID = "'.$this->emuAppID.'"; '.
                        '$emuAppObject = "'.get_class( $this ).'"; ';

        if( isset($page->view) && $page->view )
        {
            $page_loader .= 'global $$emuAppObject; $$emuAppObject->getView("'.$page->name.'", array(), $echo = '.($is_group_default ? 'false' : 'true').');';
        }
        else
        {
            $page_loader .= 'include_once( "'.str_replace('\\', '/', $page->directory).$page->filename.'" );';
        }

        return create_function( '', $page_loader );
    }

    function addAdminPage( $page, $is_group_default = false )
    {
        if( ! ( is_array( $page ) || is_object( $page ) ) ) return false;

        if( is_array( $page ) ) $page = (object) $page;

        if( $is_group_default )
        {
            // then this item creates the menu group
            $emu_page = add_menu_page( $page->group, $this->menuName, $page->capability, $page->group, $this->genPageLoader( $page, $is_group_default ), EMU_FRAMEWORK_URL.'/image/emu_icon.png', $this->menuPosition );
        }

        $menu_slug = preg_replace( '/[^a-zA-Z0-9]/', '', $page->name );
        $menu_slug = $this->emuAppID.'-'.strtolower( $menu_slug );

        $emu_page = add_submenu_page( $page->group, $page->name, $page->name, $page->capability, $is_group_default ? $page->group : $menu_slug, $this->genPageLoader( $page ) );

        if( $page->styles )
        {
            $styles = 'wp_enqueue_style("'.implode( $page->styles, '"); wp_enqueue_style("' ).'");';
            add_action( 'admin_print_styles-'.$emu_page, create_function( '', $styles ) );
        }

        if( $page->scripts )
        {
            $scripts = 'wp_enqueue_script("'.implode( $page->scripts, '"); wp_enqueue_script("' ).'");';
            add_action( 'admin_print_scripts-'.$emu_page, create_function( '', $scripts ) );
        }

    }

    function getWidgets()
    {
        $widget_files = $this->getFiles( $this->pluginPath.'/widget' );

        $widget_files = apply_filters($this->emuAppID.'_widget_files', $widget_files);

        $emu_widgets = array();

        if ( is_array( $widget_files ) ) {

            foreach ( $widget_files as $widget_file ) {

                $basename = str_replace( $this->pluginPath.'/widget/', '', $widget_file );

                // don't allow template files in subdirectories
                if ( false !== strpos($basename, '/') )
                    continue;

                if( file_exists( $widget_file ) )
                {
                    $file_data = implode( '', file( $widget_file ));

                    $name = '';
                    $class = '';
                    $description = '';

                    if ( preg_match( '|Emu Widget:(.*)$|mi', $file_data, $name ) )
                        $name = _cleanup_header_comment($name[1]);

                    if ( preg_match( '|Emu Widget Class:(.*)$|mi', $file_data, $className ) )
                        $className = _cleanup_header_comment($className[1]);

                    if ( preg_match( '|Emu Widget Description:(.*)$|mi', $file_data, $description ) )
                        $description = _cleanup_header_comment($description[1]);

                    if ( !empty( $name ) && !empty( $className ) ) {
                        $emu_widgets[$className] = array( 'name' => $name, 'className' => $className, 'description' => $description, 'path' => $widget_file, 'basename' => $basename );
                    }
                }
            }
        }

        return $emu_widgets;

    }

    function getFiles( $directory, $ext_pattern = 'htm|php|txt|css|js' )
    {
        $dir_files = array();

        $dir = @ dir("$directory");

        if ( $dir )
        {
            while ( ($file = $dir->read()) !== false )
            {
                if ( preg_match('|^\.+$|', $file) )
                    continue;
                if ( preg_match('/\.('.$ext_pattern.')$/', $file) )
                {
                    $dir_files[] = "$directory/$file";
                }
                elseif ( is_dir("$directory/$file") )
                {
                    $subdir = @ dir("$directory/$file");
                    if ( !$subdir )
                        continue;
                    while ( ($subfile = $subdir->read()) !== false )
                    {
                        if ( preg_match('|^\.+$|', $subfile) )
                            continue;
                        if ( preg_match('/\.('.$ext_pattern.')$/', $subfile) )
                            $dir_files[] = "$directory/$file/$subfile";
                    }
                    @ $subdir->close();
                }
            }
            @ $dir->close();
        }

        $dir_files = array_unique($dir_files);

        return $dir_files;

    }


    /* strBytes function courtesy of paolo dot mosna at gmail dot com - see http://php.net/manual/en/function.strlen.php */
    function strBytes($str)
    {
      // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT

      // Number of characters in string
      $strlen_var = strlen($str);

      // string bytes counter
      $d = 0;

     /*
      * Iterate over every character in the string,
      * escaping with a slash or encoding to UTF-8 where necessary
      */
      for ($c = 0; $c < $strlen_var; ++$c) {

          $ord_var_c = ord($str{$d});

          switch (true) {
              case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                  // characters U-00000000 - U-0000007F (same as ASCII)
                  $d++;
                  break;

              case (($ord_var_c & 0xE0) == 0xC0):
                  // characters U-00000080 - U-000007FF, mask 110XXXXX
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  $d+=2;
                  break;

              case (($ord_var_c & 0xF0) == 0xE0):
                  // characters U-00000800 - U-0000FFFF, mask 1110XXXX
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  $d+=3;
                  break;

              case (($ord_var_c & 0xF8) == 0xF0):
                  // characters U-00010000 - U-001FFFFF, mask 11110XXX
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  $d+=4;
                  break;

              case (($ord_var_c & 0xFC) == 0xF8):
                  // characters U-00200000 - U-03FFFFFF, mask 111110XX
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  $d+=5;
                  break;

              case (($ord_var_c & 0xFE) == 0xFC):
                  // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  $d+=6;
                  break;
              default:
                $d++;
          }
      }

      return $d;
    }

    public function __get($member)
    {
        if( substr($member, -7) == 'Manager' )
        {
            // See if we can find the manager
            $manager = str_replace('Manager', '', $member);
            return $this->findManager( $manager );
        }

        if( substr($member, -6) == 'Helper' )
        {
            // See if we can find the helper
            $helper = str_replace( 'Helper', '', $member);
            return $this->findHelper( $helper );
        }

        if( substr($member, -4) == 'View' )
        {
            // See if we can find the view
            $view = str_replace( 'View', '', $member);
            return $this->findView( $view );
        }

        $trace = debug_backtrace();
        trigger_error('Undefined property via __get(): ' . $member .' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
    }

    public function findManager( $name )
    {
        $name = $this->simpleName($name);

        foreach( $this->managers as $manager_name => $manager )
        {
            if( $this->simpleName( $manager_name ) == $name ) return $this->getManager($manager_name);
        }
    }

    public function findHelper( $name )
    {
        $name = $this->simpleName($name);

        foreach( $this->helpers as $helper_name => $helper )
        {
            if( $this->simpleName( $helper_name ) == $name ) return $this->getHelper($helper_name);
        }
    }

    public function findView( $name )
    {
        $name = $this->simpleName($name);

        foreach( $this->views as $view_name => $view )
        {
            if( $this->simpleName( $view_name ) == $name ) return $this->getView($view_name);
        }
    }

    private function simpleName($name)
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]/', '', $name);
        return $name;
    }

    public function getManagers()
    {
        return $this->managers;
    }

    public function getManager( $name )
    {
        if( !isset( $this->managers[ $name ] ) ) return false;

        if( !is_object( $this->managers[$name] ) )
        {
            if( !$this->loadManager( $name ) ) return false;
        }

        if( is_object( $this->managers[$name] ) )
            return apply_filters( 'emu_manager', $this->managers[ $name ], $name );

        return false;
    }

    public function registerManager( $name, $class = null, $filename = null, $path = null )
    {
        if( !$class )
            $class = "emuM_".$this->classify($name);

        if( !$filename )
            $filename = "manage.".$this->filenameify($name).".class.php";

        $this->registerClass( $class, $filename, 'emuManager', $path );
        $this->managers[ $name ] = $class; // just the name of the class
    }

    public function loadManager( $name, $class = null, $filename = null, $path = null )
    {
        if( !isset( $this->managers[ $name ] ) )
        {
            $this->registerManager( $name, $class, $filename, $path );

            // trigger_error( "Can not load $name manager: no class or filename registered (use registerManager)" );
            // return false;
        }

        if( !$this->loadClass( $this->managers[ $name ] ) )
            return false;

        $this->managers[ $name ] = $this->getInstance( $this->managers[ $name ], array( $this ) );

        return true;
    }

    ////////////////////////////////////////
    public function getViews()
    {
        return $this->views;
    }

    public function doView( $name, $vars = array() )
    {
        $this->getView( $name, $vars, $echo = true );
    }

    public function getView( $name, $vars = array(), $echo = false )
    {
        if( !isset( $this->views[$name] ) ) return false; // view hasn't even been registered

        if( !is_object( $this->views[$name] ) )
        {
            if( !$this->loadView( $name ) ) return false;
        }

        if( is_object( $this->views[$name] ) )
        {
            $view = $this->views[$name];

            $view = apply_filters( 'emu_view', $view, $name );

            if( !is_array( $vars ) )
                $vars = array( $vars );

            $view->setVars($vars);

            if( !$echo ) ob_start();

            $view->build();

            if( !$echo )
            {
                $content = ob_get_contents();
                ob_end_clean();
            }

            // if( $echo ) echo $content;

            if( !$echo ) return $content;
            return '';
        }
    }

    public function registerView( $name, $class = '', $filename = '', $path = null )
    {
        if( !$class )
            $class = "emuV_".$this->classify($name);

        if( !$filename )
            $filename = "view.".$this->filenameify($name).".class.php";

        $this->registerClass( $class, $filename, 'emuView', $path );
        $this->views[ $name ] = $class; // just the name of the class
    }

    public function filenameify($name)
    {
        $name = preg_replace('/[^\w]/', '', $name);
        return strtolower($name);
    }

    public function classify($name)
    {
        $name = str_replace('-', '_', $name);
        return Inflector::camelize($name);
    }

    public function loadView( $name, $class = null, $filename = null, $path = null, $short_code = null )
    {
        if( $class )
        {
            // register first
            $this->registerView( $name, $class, $filename, $path, $short_code );
        }

        if( !isset( $this->views[ $name ] ) )
        {
            trigger_error( "Can not load $name view: no class or filename registered (use registerView)" );
            return false;
        }

        if( !$this->loadClass( $this->views[$name] ))
            return false;

        $this->views[ $name ] = $this->getInstance( $this->views[ $name ], array( $this ) );

        return true;
    }

    public function getHelper( $name )
    {
        if( !isset( $this->helpers[ $name ] ) ) return false;

        if( !is_object( $this->helpers[$name] ) )
        {
            if( !$this->loadHelper( $name ) ) return false;
        }

        if( is_object( $this->helpers[$name] ) )
            return apply_filters( 'emu_helper', $this->helpers[ $name ], $name );

        return false;
    }

    public function getHelpers()
    {
        return $this->helpers;
    }

    public function registerHelper( $name, $class = '', $filename = '', $path = null, $reqs = array() )
    {
        if( !$class )
            $class = "emuH_".$this->classify($name);

        if( !$filename )
            $filename = "helper.".$this->filenameify($name).".class.php";

        if( !is_array($reqs) ) $reqs = array($reqs);

        $reqs[] = 'emuHelper';

        $this->registerClass( $class, $filename, $reqs, $path );
        $this->helpers[ $name ] = $class;
    }

    public function loadHelper( $name, $class = null, $filename = null, $path = null )
    {
        if( $class )
        {
            // register first
            $this->registerHelper( $name, $class, $filename, $path );
        }

        if( !isset( $this->helpers[ $name ] ) )
        {
            trigger_error( "Can not load $name helper: no class or filename registered (use registerHelper)" );
            return false;
        }

        if( !$this->loadClass( $this->helpers[ $name ] ) )
            return false;

        $this->helpers[ $name ] = $this->getInstance( $this->helpers[ $name ], array( $this ) );

        return true;
    }

    public function getConstants()
    {
        return $this->constants;
    }

    private function registerConstants( $name, $class, $filename, $path = null )
    {
        $this->registerClass( $class, $filename, 'emuConstants', $path );
        $this->constants[ $name ] = $class; // just the name of the class
    }

    public function loadConstants( $name, $class = null, $filename = null, $path = null )
    {
        if( $class )
        {
            // register first
            $this->registerConstants( $name, $class, $filename, $path );
        }

        if( !isset( $this->constants[ $name ] ) )
        {
            trigger_error( "Can not load $name constants: no class or filename registered (use registerConstants)" );
            return false;
        }

        if( !$this->loadClass( $this->constants[ $name ] ) )
            return false;

        $constants_object = $this->getInstance( $this->constants[ $name ], array( $this ) );

        $this->constants[ $name ] = $constants_object;

        // And load the constants
        $groups = $constants_object->getGroups();

        foreach( $groups as $group )
        {
            $constants = $group->getConstants();

            foreach( $constants as $name => $details )
                define( $group->name.$name, $details->value );
        }

        return true;
    }

    function addMessage( $key, $message, $type = 'notice' )
    {
        if( is_array( $message ) )
        {
            for( $n = 0; $n < count( $message ); $n++ ) $this->addMessage( $key, $message[$n], $type );
            return;
        }

        $this->loadMessages();

        $this->messages[$key][$type][] = $message;

        $this->saveMessages();
    }

    function saveMessages()
    {
        $_SESSION[ $this->emuAppID.'_MESSAGES' ] = serialize( $this->messages );
    }

    function getMessage( $key, $wrapper_start = '<p>', $wrapper_end = '</p>', $type = 'notice' )
    {
        $this->loadMessages();

        if( ! isset( $this->messages[ $key ][ $type ] ) ) return '';

        $message_output = '';

        foreach( $this->messages[ $key ][ $type ] as $message )
        {
            switch( $type )
            {
                case 'error':
                    $message_output .= '<div class="emu-error">'; break;
                case 'notice':
                    $message_output .= '<div class="emu-notice">'; break;
                default:
                    $message_output .= '<div>'; break;
            }
            $message_output .= $wrapper_start.$message.$wrapper_end.'</div>';
        }

        unset( $this->messages[ $key ][ $type ] );

        $this->saveMessages();

        return $message_output;

    }

    function loadMessages()
    {
        if( !$this->messages )
        {
            if( isset( $_SESSION[ $this->emuAppID.'_MESSAGES' ] ) )
                $this->messages = unserialize( $_SESSION[ $this->emuAppID.'_MESSAGES' ] );
            else
                $this->messages = array();
        }
    }

    function getMessagesArray($key, $clear = false){
        $this->loadMessages();
        $r = $this->messages[ $key ];

        if($clear){
            unset( $this->messages[ $key ]);
            $this->saveMessages();
        }

        return $r;
    }

    function getMessages( $key = null, $wrapper_start = '<p>', $wrapper_end = '</p>' )
    {
        $this->loadMessages();

        $keys = array();

        if( $key )
        {
            if( ! isset( $this->messages[ $key ] ) ) return '';
            $keys[] = $key;
        }
        else
        {
            foreach( $this->messages as $key => $value ) $keys[] = $key;
        }

        $message_output = '';

        foreach( $keys as $key )
        {
            foreach( $this->messages[ $key ] as $type => $messages )
                $message_output .= $this->getMessage( $key, $wrapper_start, $wrapper_end, $type );

            unset( $this->messages[ $key ] );
        }

        $this->saveMessages();

        return $message_output;
    }

    public function addSessionData( $field, $value = null, $update_session = true)
    {
        $this->setSessionData( $field, $value, $update_session );
    }

    public function setSessionData( $field, $value = null, $update_session = true )
    {
        if( $this->verbose ) echo "Setting session data '$field' value '$value'\n";

        if( is_array( $field ) )
        {
            $arr_fields = $field;

            foreach( $arr_fields as $field => $value )
                $this->setSessionData( $field, $value, false );

            if( $update_session ) $this->saveSessionData();

            return;
        }

        $this->loadSessionData();

        if( is_null( $value ) )
            return $this->deleteSessionData( $field );
        else
            $this->sessionData[$field] = $value;

        if( $update_session ) $this->saveSessionData();
    }

    public function deleteSessionData( $field )
    {
        if( $this->verbose ) echo "Deleting session data '$field'\n";

        if( isset( $this->sessionData[ $field ] ) )
            unset( $this->sessionData[ $field ] );

        $this->saveSessionData();
    }

    public function getSessionData( $field, $remove_after_read = false )
    {
        if( $this->verbose ) echo "Getting session data '$field'\n";

        $this->loadSessionData();

        if( ! isset( $this->sessionData[ $field ] ) )
            return '';

        $session_data = $this->sessionData[$field];

        if( $remove_after_read ) $this->deleteSessionData( $field );

        if( $this->verbose ) echo "Return session data '$session_data'\n";

        return $session_data;
    }

    public function saveSessionData()
    {
        if( $this->verbose ) echo "Saving session data\n";
        $_SESSION[ $this->emuAppID.'_DATA' ] = serialize( $this->sessionData );
    }

    public function loadSessionData()
    {
        if( $this->verbose ) echo "Loading session data\n";
        if( !$this->sessionData )
        {
            if( isset( $_SESSION[ $this->emuAppID.'_DATA' ] ) )
            {
                $this->sessionData = unserialize( $_SESSION[ $this->emuAppID.'_DATA' ] );
                if( $this->verbose ) echo "Loading from session\n";
            }
            else
                $this->sessionData = array();
        }
    }

    public function addShortCode( $name, $function = null, $view = null )
    {
        if( !in_array( $name, $this->shortCodes ) ) $this->shortCodes[] = $name;

        if( !$function && $view )
            add_shortcode( $name, array( $this, 'getShortCodeView' ) );
        else
            add_shortcode( $name, $function );
    }

    public function getShortCodeView($atts, $content = null, $tag)
    {
        $atts['content'] = $content;
        return $this->getView($tag, $atts);
    }

    public function addShortCodeForView( $name )
    {
        $this->registerView( $name );
        $this->addShortCode( $name, null, $name );
    }

    public function getShortCodes( $sorted = true )
    {
        $arr_shortcodes = $this->shortCodes;

        if( $sorted )
            asort( $arr_shortcodes );

        return $arr_shortcodes;
    }

    function loadModel( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        return $this->loadClass( $class_label, $class_file, $reqs, $path, $class_name );
    }

    function loadClass( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        if( is_array( $class_label ) )
        {
            $classes = $class_label;

            if( !$this->classPath )
            {
                trigger_error( "When loading an array of classes using loadClass() the class path must first be defined using setClassPath()" );
                return false;
            }

            foreach( $classes as $key => $value )
            {
                if( is_int( $key ) ) // e.g. array( 'emuClass1', 'emuClass2' ...
                {
                    $this->loadClass( $class = $value );
                }
                else // e.g. array( 'emuClass1' => 'class2.class.php', 'emuClass2' => 'class2.class.php' ...
                {
                    if( is_array( $value ) ) // e.g. array( 'emuClass1' => array( 'class1.class.php', array( 'emuClass2', 'emuClass3' ) )
                    {
                        $reqs = $value[1];
                        $class_file = $value[0];
                    }
                    else
                    {
                        $reqs = null;
                        $class_file = $value;
                    }
                    $this->loadClass( $class_label = $key, $class_file, $reqs );
                }
            }
            return true;
        }


        if( $class_file )
        {
            if( $this->verbose ) echo "Loading (and registering) $class_label | $class_name | $class_file | $path \n";
            if( !$class_name ) $class_name = $class_label;
            if( !$this->registerClass( $class_label, $class_file, $reqs, $path, $class_name ) ) return false;
        }
        else
        {
            if( $this->verbose ) echo "Loading $class_label \n";
        }

        if( !isset( $this->classes[ $class_label ] ) )
        {
            trigger_error( "Class '$class_label' can not be found" );
            return false;
        }

        // load reqs
        if( $reqs = $this->classes[ $class_label ]['reqs'] )
        {
            if( $this->verbose ) echo "Loading Class Reqs. \n";

            foreach( $reqs as $class_req_label )
            {
                if( !$this->loadClass( $class_req_label ) )
                {
                    trigger_error( "Cannot find '$class_req' requirement for '$class_label'" );
                    return false;
                }
            }
        }

        $class_path = $this->classes[ $class_label ]['path'];
        $class_name = $this->classes[ $class_label ]['class'];

        if( ! ( class_exists( $class_name ) || interface_exists( $class_name ) ) )
        {
            if( ! file_exists( $class_path ) )
            {
                trigger_error( "Class '$class_path' does not exist - is the path correct?" );
                return false;
            }
            include_once( $class_path );
        }

        if( $this->verbose ) echo "$class_label (class $class_name) loaded \n";

        return true;
    }


    function getDbEntity( $table_name, $db_id = null, $db_prefix = null )
    {
        if( !$db_prefix ) $db_prefix = $this->dbPrefix;
        return $this->getClass('emuDbEntity', array( $db_id, null, $db_prefix, $table_name ) );
    }

    function getModel( $class_label, $args = null, $refresh = false )
    {
        return $this->getInstance( $class_label, $args, $refresh );
    }
    function getClass( $class_label, $args = null, $refresh = false )
    {
        return $this->getInstance( $class_label, $args, $refresh );
    }
    function getInstance( $class_label, $args = null, $refresh = false )
    {
        if( $this->verbose ) echo "Getting $class_label \n";

        if( !$this->loadClass( $class_label ) )// this is just a check to see if label exists, i guess.
        {
            return (object) array();
        }

        $class_details = $this->classes[$class_label];

        $class = __NAMESPACE__ . '\\' . $class_details['class'];

        if( !$args )
        {
            $args = array();
        }
        else
        {
            if( !is_array( $args ) ) $args = array( $args );
        }

        if( $this->poolObjects &! $refresh )
        {
            if( $object = $this->findObject( $class_details['class'], $args ) )
                return clone $object; // just return a copy of the object
        }

        if( count( $args ) == 0 )
        {
            $object = new $class;
        }
        else
        {
            $object = new \ReflectionClass( $class );
            $object = $object->newInstanceArgs( $args );
        }

        $this->objects[] = array( 'class' => $class_details['class'], 'args' => $args, 'args_count' => count( $args ), 'object' => $object );

        return $object;
    }

    function findObject( $class_name, $args = null )
    {
        if( !$args ) return false; // only find objects that have arguments

        foreach( $this->objects as $object )
        {
            if( $object['class'] == $class_name )
            {
                if( $object['args_count'] == count( $args ) )
                {
                    $same_args = true;

                    for( $n = 0; $n < $object['args_count']; $n++ )
                    {
                        if( $object['args'][$n] !== $args[$n] )
                            $same_args = false;
                    }

                    if( $same_args )
                    {
                        if( $this->verbose ) echo "$class_name found in pool\n";

                        $this->countObjectsPooled++;

                        return $object['object'];
                    }
                }
            }

        }

        $this->countObjectsCreated++;

        if( $this->verbose ) echo "new $class_name \n";

        return false;
    }

    function renameModelLabel( $old_class_label, $new_class_label )
    {
        return $this->renameClassLabel( $old_class_label, $new_class_label);
    }

    function renameClassLabel( $old_class_label, $new_class_label )
    {
        if( !isset( $this->classes[$old_class_label] ) )
        {
            trigger_error( "Can't rename class label: class '$old_class_label' doesn't exist (maybe hasn't been registered yet?)" );
            return false;
        }
        $this->classes[$new_class_label] = $this->classes[$old_class_label];
        unset($this->classes[$old_class_label]);
    }

    function updateModel( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        return $this->updateClass( $class_label, $class_file, $reqs, $path, $class_name );
    }

    // Uses the existing class for defaults
    function updateClass( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        if( !isset( $this->classes[$class_label] ) )
        {
            trigger_error( "Can't override class: class '$class_label' doesn't exist (maybe hasn't been registered yet?)" );
            return false;
        }

        $existing_class = $this->classes[$class_label];

        if( is_null($class_file) )
            $class_file = $existing_class['filename'];

        if( is_null($reqs) )
            $reqs = $existing_class['reqs'];

        if( is_null($path) )
            $path = $existing_class['dir'];

        if( is_null($class_name) )
            $class_name = $existing_class['class'];

        // Overwrite
        $this->registerClass( $class_label, $class_file, $reqs, $path, $class_name );
    }

    function replaceModel( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        return $this->replaceClass( $class_label, $class_file, $reqs, $path, $class_name );
    }

    function replaceClass( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        if( !isset( $this->classes[$class_label] ) )
        {
            trigger_error( "Can't replace class: class '$class_label' doesn't exist (maybe hasn't been registered yet?)" );
            return false;
        }
        $this->registerClass( $class_label, $class_file, $reqs, $path, $class_name );
    }

    public function registerModel( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        return $this->registerClass( $class_label, $class_file, $reqs, $path, $class_name);
    }

    function registerClass( $class_label, $class_file = null, $reqs = null, $path = null, $class_name = null )
    {
        if( is_array( $class_label ) )
        {
            $classes = $class_label;

            if( !$this->classPath )
            {
                trigger_error( "When defining an array of classes using method registerClass() the class path must first be defined using setClassPath()" );
                return false;
            }

            foreach( $classes as $class_label => $class )
            {
                if( is_array( $class ) )
                {
                    $reqs = $class[1];
                    $class_file = $class[0];
                }
                else
                {
                    $class_file = $class;
                    $reqs = null;
                }
                $this->registerClass( $class_label, $class_file, $reqs, $this->classPath, $class_name );
            }

            return true;
        }

        if( !$path )
        {
            if( !isset( $this->classPath ) )
            {
                trigger_error( "Cannot load '.$class_label.': class path must be defined using setClassPath()" );
                return false;
            }
            $path = $this->classPath;
        }

        if( $reqs )
        {
            if(!is_array( $reqs ) )
            {
                // see if it is a delimeted list of reqs
                $reqs = str_replace( ' ', '', $reqs );
                $reqs = explode( ',', $reqs );
            }
        }
        else
            $reqs = array();

        if( !$class_name )
            $class_name = $class_label;

        if( $this->verbose ) echo "Registering $class_label | $class_name | $class_file | $path \n";

        $this->classes[$class_label] = array( "path" => "$path/$class_file", "reqs" => $reqs, "class" => $class_name, "dir" => $path, "filename" => $class_file );

        return true;
    }


    function setContentTypeHTML( $content_type )
    {
        return 'text/html';
    }

    function mail( $to, $from = '', $subject=''  , $message=''  , $type = null, $cc = null, $bcc = null, $attachments = null, $important = false )
    {
        // shouldnt need this global $wpdb;
        if(is_array($to)){//must be an args array
            extract( wp_parse_args( $to, array(
                 'to'       => ''
                ,'from'     => '"'.home_url().'" <admin@'.home_url().'>'
                ,'subject'  => ''
                ,'message'  => ''
                ,'type'     => null
                ,'cc'       => null
                ,'bcc'      => null
                ,'attachments' => null
                ,'important' => false
            ) ) );
        }

        $emuEmail = $this->getClass('emuEmail', $this, true );

        $emuEmail->emailTo = $to;
        $emuEmail->emailFrom = $from;
        $emuEmail->emailCc = $cc;
        $emuEmail->emailBcc = $bcc;
        $emuEmail->emailType = $type;
        $emuEmail->subject = $subject;
        $emuEmail->message = $message;

        if( $important )
            $emuEmail->important = true;

        if( $attachments )
            $emuEmail->addAttachment( $attachments );

        $emuEmail->send();

        return $emuEmail;
    }

    // core
    function isValidEmail( $email )
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    function setClassPath( $path )
    {
        $this->classPath = $path;
    }

    function getData( $data, $path = null, $ext = '.txt' )
    {
        if( !$path ) $path = $this->pluginPath;

        $data = file_get_contents( $path . '/data/'.$data.$ext );

        return $data;
    }


    function getMeta( $key )
    {
        global $wpdb;

        $sql = "select metaValue from {$this->dbPrefixShared}meta where metaKey = '$key' and emuAppID = '{$this->emuAppID}'";

        $meta_value = $wpdb->get_col( $sql );

        if( count( $meta_value ) > 0 )
            $return = $meta_value[0];
        else
            $return = '';

        if( is_serialized( $return ) ) $return = unserialize($return);

        return $return;

    }

    function deleteMeta($key)
    {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix.'emu_meta', array( 'metaKey' => $key,'emuAppID' => $this->emuAppID ) );
    }

    // Alias
    function setMeta( $key, $value )
    {
        $this->updateMeta($key, $value);
    }

    function updateMeta( $key, $value )
    {
        global $wpdb;

        if( is_array( $value ) )
            $value = serialize($value);

        // see if the key already exists
        $meta_id = $wpdb->get_col( "select id from {$wpdb->prefix}emu_meta where metaKey = '$key' and emuAppID = '{$this->emuAppID}'" );

        if( count( $meta_id ) > 0 )
            $sql = "update {$this->dbPrefixShared}meta set metaValue = '$value' where id = {$meta_id[0]} and emuAppID = '{$this->emuAppID}'";
        else
            $sql = "insert into {$this->dbPrefixShared}meta (metaKey, metaValue, emuAppID) values ('$key', '$value', '{$this->emuAppID}')";

        return $wpdb->query( $sql );

    }

    public function buildActionRefs($e_action, $e_button, $e_plugin = null, $url_encode = true, $as_query_string = false )
    {
        if( !$e_plugin )
            $e_plugin = $this->emuAppID;

        if( is_object( $e_plugin ) )
            $e_plugin = $e_plugin->emuAppID;

        $action_refs = array();
        $action_refs['e-plugin'] = urlencode($e_plugin);
        $action_refs['e-action'] = urlencode($e_action);
        $action_refs['e-button'] = urlencode($e_button);

        if( $as_query_string)
        {
            $query_string = '';
            foreach( $action_refs as $action_ref_key => $action_ref_value )
                $query_string .= "{$action_ref_key}={$action_ref_value}&";

            $query_string = rtrim($query_string, '&');
            return $query_string;
        }

        return $action_refs;
    }

    public function bindProcessor($name, $echo = true)
    {
        $refs = '<input type="hidden" name="e-plugin" value="'.$this->emuAppID.'" />'."\n\t";
        $refs .= '<input type="hidden" name="e-action" value="'.$name.'" />'."\n";

        if( $echo ) echo $refs;
        return $refs;
    }

    public function getProcessorURL($name)
    {
        return get_bloginfo('url').'/?e-plugin='.$this->emuAppID.'&e-action='.$name;
    }

    public function addSideBar($name, $id = null, $options = array() )
    {
        if( is_array( $name ) )
        {
            foreach( $name as $name_array_item )
                $this->addSideBar( $name_array_item );

            return;
        }

        $defaults = array( 'description'  => '', 'before_widget' => '', 'after_widget' => '', 'before_title' => '', 'after_title' => '' );

        $options = wp_parse_args( $options, $defaults );

        if( !$id )
        {
            $id = strtolower($name);
            $id = str_replace(' ', '-', $id);
            $id = preg_replace('/[^a-z0-9\-]/', '', $id);
        }

        $options['name'] = $name;
        $options['id'] = $id;

        register_sidebar( $options);
    }


    /* is__writable function courtesy of legolas558 @ sourceforge dot net - see http://uk.php.net/is_writable */
    function isWritable($path) {
        if ($path{strlen($path)-1}=='/')
            return $this->isWritable($path.uniqid(mt_rand()).'.tmp');

        if (file_exists($path)) {
            if (!($f = @fopen($path, 'r+')))
                return false;
            fclose($f);
            return true;
        }

        if (!($f = @fopen($path, 'w')))
            return false;
        fclose($f);
        unlink($path);
        return true;
    }

    public function uninstall()
    {
        delete_option( $this->emuAppID.'_installed' );
    }

    public function install()
    {
        $managers = $this->getManagers();

        foreach( $managers as $manager ) $manager->install( $this );

        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // meta
        $sql = "CREATE TABLE {$this->dbPrefixShared}meta (
        ID int(10) NOT NULL AUTO_INCREMENT,
        emuAppID varchar(20) default NULL,
        metaKey VARCHAR(255) default NULL,
        metaValue LONGTEXT default NULL,
        UNIQUE KEY id (ID)
        );";

        dbDelta($sql);

        // email logs
        $sql = "CREATE TABLE {$this->dbPrefixShared}email_logs (
        dbID int(10) NOT NULL AUTO_INCREMENT,
        postID int(10) default NULL,
        userID int(10) default NULL,
        emuAppID varchar(20) default NULL,
        emailFrom varchar(300) default NULL,
        emailTo varchar(300) default NULL,
        emailCc varchar(300) default NULL,
        emailBcc varchar(300) default NULL,
        emailType varchar(30) default NULL,
        header varchar(400) default NULL,
        attachments text default NULL,
        subject varchar(400) default NULL,
        message text default NULL,
        sent DATETIME default NULL,
        error BOOLEAN NULL DEFAULT 0,
        UNIQUE KEY id (dbID)
        );";

        dbDelta($sql);

    }
}

?>