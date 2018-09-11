<?php

class emuManager
{
    protected $emuApp;

    protected $processors = array();
    protected $AJAXMethods = array();

    public function __construct( $emuApp )
    {
        $this->emuApp = $emuApp;

        add_action( 'init', array( $this, 'registerClasses' ), 1 );
        add_action( 'init', array( $this, 'wpinit' ), 1 );
        add_action( 'init', array( $this, 'registerModels' ), 1 );
        add_action( 'init', array( $this, 'registerCustomPostTypes' ), 1 );
        add_action( 'init', array( $this, 'registerAdminPages' ), 2 );
        add_action( 'init', array( $this, 'registerViews' ), 2 );
        add_action( 'init', array( $this, 'addShortCodes' ), 2 );
        add_action( 'init', array( $this, 'loadStyles' ), 2 );
        add_action( 'init', array( $this, 'loadScripts' ), 2 );
        add_action( 'init', array( $this, 'registerSideBars' ) );
        add_action( 'init', array( $this, 'registerProcessorTags'));
        add_action( 'wp',   array( $this, 'afterPostSetup' ) );
        add_action( 'after_setup_theme', array( $this, 'registerMenus' ) );
        add_action( 'admin_menu', array( $this, 'registerMetaBoxes' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array($this,'admin_enqueue_scripts'));

        // Depracated - use the registerAJAXMethod and registerProcessorClass and registerProcessorMethod
        ////////////////////////////////////////////////////////////////////
        add_action( 'init', array( $this, 'processAjax' ), 3 );
        add_action( 'template_redirect', array( $this, 'processForm' ), 4 );
        ////////////////////////////////////////////////////////////////////

        add_action( 'init', array( $this, 'doAJAXMethods' ), 3 );

        if( is_admin() )
            add_action( 'admin_init', array( $this, 'doProcessors' ), 4 );
        else
            add_action( 'template_redirect', array( $this, 'doProcessors' ), 4 );

        $this->init();
    }

    public function registerProcessorTags()
    {
        add_rewrite_tag('%e-action%','([^&]+)');
        add_rewrite_tag('%e-plugin%','([^&]+)');
    }

    public function doAJAXMethods()
    {
        if( !$this->requestVars($plugin, $action) ) return;

        if( !isset( $this->AJAXMethods[$action] ) ) return;

        $ajax_action = $this->AJAXMethods[$action];

        call_user_func( $ajax_action );

        exit();
    }

    public function requestVars(&$plugin, &$action)
    {
        global $wp_query;

        $plugin = request_val( 'e-plugin' );

        if( empty($plugin) && isset( $wp_query->query_vars['e-plugin'] ) && !empty($wp_query->query_vars['e-plugin']) )
            $plugin = $wp_query->query_vars['e-plugin'];

        if( $plugin !== $this->emuApp->emuAppID ) return false;

        $action = request_val( 'e-action' );

        if( empty($action) && isset( $wp_query->query_vars['e-action'] ) && !empty($wp_query->query_vars['e-action']) )
            $action = $wp_query->query_vars['e-action'];

        return true;
    }

    public function doProcessors()
    {
        if( !$this->requestVars($plugin, $action) ) return;

        if( !isset( $this->processors[$action] ) ) return;

        $processor = $this->processors[$action];

        if( $processor['class'] )
        {
            // $processorObj = $this->emuApp->getClass( $processor['class'], $this->emuApp );
            $processorObj = $this->getProcessor($action);
            
            if( method_exists( $processorObj, 'process' ) )
                $processorObj->process();

        }
        else if ( $processor['method'] )
        {
            call_user_func( $processor['method'] );
        }
    }

    public function getProcessor($action)
    {
        if( !isset( $this->processors[$action] ) ) return false;
        $processor = $this->processors[$action];
        
        $processorObj = $this->emuApp->getClass( $processor['class'], $this->emuApp );
        return $processorObj;
    }

    // Depracated - use registerAJAXMethod
    ////////////////////////////////////////////////////////////////
    public function registerAjaxAction( $action, $function_to_call )
    {
        $this->registerAJAXMethod( $action, $function_to_call );
    }
    ////////////////////////////////////////////////////////////////

    public function registerAJAXMethod( $action, $method_to_call )
    {
        $this->AJAXMethods[ $action ] = $method_to_call;
    }

    public function registerProcessorClass( $action, $class = '', $filename = '', $reqs = array('emuProcessor'), $path = null )
    {
        if( !$class )
            $class = "emuP_".$this->emuApp->classify($action);

        if( !$filename )
            $filename = "process.".$this->emuApp->filenameify($action).".class.php";

        $this->emuApp->registerClass( $class, $filename, $reqs, $path );
        $this->processors[$action] = array( 'class' => $class, 'function' => null );
    }

    // Alias of registerProcessorClass
    public function registerProcessor( $action, $class = '', $filename = '', $reqs = array('emuProcessor'), $path = null )
    {
        $this->registerProcessorClass( $action, $class, $filename, $reqs, $path );
    }

    // Depracated - use registerProcessorMethod
    ////////////////////////////////////////////////////////////////
    public function registerProcessorFunction( $action, $function_to_call )
    {
        $this->registerProcessorMethod( $action, $function_to_call );
    }
    ////////////////////////////////////////////////////////////////

    public function registerProcessorMethod( $action, $method_to_call )
    {
        $this->processors[$action] = array( 'class' => null, 'method' => $method_to_call );
    }

    function nonce( $args = array() )
    {
        extract( wp_parse_args( $args, array(
            'action' => 'setopts'
            ,'name' => get_class($this).'_nonce'
            ,'referer' => false // the referrer thing is pretty much deprecated, dont bother.
            ,'echo' => true
            ) ) );
        wp_nonce_field($action, $name, $referer, $echo );     
    }

    function checknonce( $args = array() )
    {
        extract( wp_parse_args( $args, array(
            'action' => 'setopts'
            ,'name' => get_class($this).'_nonce'
            ) ) );
        return wp_verify_nonce($_POST[$name],$action); //yes, the order of these are reversed from wp_nonce_field()
    }

    public function init()
    {}

    public function wpinit()
    {}

    public function addShortCodes()
    {}

    public function registerAdminPages()
    {}

    public function registerViews()
    {}

    public function registerClasses()
    {}

    public function registerModels()
    {}

    public function processForm()
    {}

    public function loadStyles()
    {}

    public function loadScripts()
    {}

    public function registerCustomPostTypes()
    {}

    public function registerMetaBoxes()
    {}
	public function admin_menu(){}//same hook as registermetaboxes...(admin_menu) just more generically named i guess.
	public function admin_enqueue_scripts(){}

    public function registerSideBars()
    {}

    public function registerMenus()
    {}

    public function install()
    {}

    public function afterPostSetup()
    {}

    // Depracated - use registerAJAXMethod
    ////////////////////////////////////////////////////////////////
    public function processAjax()
    {}
    ////////////////////////////////////////////////////////////////
}

?>