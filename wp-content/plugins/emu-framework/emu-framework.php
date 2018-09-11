<?php
/*
Plugin Name: Emu Application Framework
Plugin URI: 
Description: Application Framework
Version: 0.2.4
Author: Chris Lucani (chrislucani@gmail.com)
Author URI: 
Another change
*/

if( !class_exists( 'emuApp' ) )
{
	define( 'EMU_FRAMEWORK_PATH', dirname( __FILE__ ) );
	define( 'EMU_FRAMEWORK_URL', get_bloginfo('url').'/wp-content/plugins/'.basename( __FILE__, '.php' ) );

  if( !function_exists( 'get_val' ) ) include_once( 'function/common.php' );
	if( !class_exists( 'Inflector' ) ) include_once( 'lib/Inflector.php' );

	include_once( 'class/core.app.class.php' );

	add_action( 'plugins_loaded', create_function( '', 'do_action( "emu_framework_loaded" );' ), 1 );
}

?>
