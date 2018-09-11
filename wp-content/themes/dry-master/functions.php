<?php

if( class_exists('emuApp') )
{
    include_once( 'class/_main.class.php' );

    global $emuTheme; $emuTheme = new emuTheme(__FILE__);
}
else
{
    $emuTheme = (object) array( 'sThemeURL' => get_bloginfo('stylesheet_directory' ) );
}


?>