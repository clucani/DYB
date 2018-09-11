<?php

/*
Module: emu-common
Version: 1.7.2
*/

function post_val($name, $default = null)
{
    return isset($_POST[$name]) ? $_POST[$name] : (is_null($default) ? '' : $default);
}

function get_val($name, $default = null)
{
    return isset($_GET[$name]) ? $_GET[$name] : (is_null($default) ? '' : $default);
}

function request_val($name, $default = null)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : (is_null($default) ? '' : $default);
}

function back_to_number( $formatted_number )
{
    if( strlen( $formatted_number ) == 0 ) return '';

    return floatval( str_replace( ',', '', $formatted_number ) );
}

function fill_template ( $field, $value, &$template )
{
    $template = str_replace( "[$field]", $value, $template );
    //$template = preg_replace( '/\['.$field.'\]/', $value, $template );
}

if (!function_exists('pre'))
{
    function pre($string, $echo = true)
    {
        $pre = "<pre>".print_r($string, true)."</pre>";
        if($echo) echo $pre;
        return $pre;
    }

    function prex($string)
    {
        pre($string);
        exit();
    }

    function dump_post($exit = false)
    {
        pre(print_r($_POST, true));
        if($exit) exit();
    }

    function dump_get($exit = false)
    {
        pre(print_r($_GET, true));
        if($exit) exit();
    }

}

if( !function_exists('vd') )
{
    function vd($var)
    {
        var_dump($var);
    }

    function vde($var)
    {
        var_dump($var);
        exit();
    }
}

/* Pagination function modified from Sparklette Studio (http://design.sparklette.net/teaches/how-to-add-wordpress-pagination-without-a-plugin/) */
function emu_pagination( $before = '<div>', $after = '</div>', $pages = null, $range = 4, $echo = true )
{
    global $paged;

    $showitems = ( $range * 2 ) + 1;

    if( empty( $paged ) ) $paged = 1;

    if( !$pages )
    {
        global $wp_query;
        $pages = $wp_query->max_num_pages;
        if( !$pages ) $pages = 1;
    }

    $pagination = '';

    if( 1 != $pages )
    {
        $pagination .= '<span class="emu-page-summary">Page '.$paged.' of '.$pages.'</span>';

        if( $paged > 2 && $paged > $range + 1 && $showitems < $pages )
            $pagination .= '<a href="'.get_pagenum_link( 1 ).'" class="emu-page-first">&laquo; First</a>';

        if( $paged >= 1 && $showitems < $pages )
            $pagination .= '<a href="'.get_pagenum_link( $paged - 1 ).'" class="emu-page-previous">&lsaquo; Previous</a>';

        for( $i = 1; $i <= $pages; $i++ )
        {
            if( 1 != $pages && ( !( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) )
            {
                if( $paged == $i )
                    $pagination .= '<span class="emu-page emu-page-current">'.$i.'</span>';
                else
                    $pagination .= '<a href="'.get_pagenum_link($i).'" class="emu-page">'.$i.'</a>';
            }
        }

        if( $paged < $pages && $showitems < $pages )
            $pagination .= '<a href="'.get_pagenum_link( $paged + 1 ).'" class="emu-page-next">Next &rsaquo;</a>';

        if( $paged < $pages-1 && $paged + $range - 1 < $pages && $showitems < $pages )
            $pagination .= '<a href="'.get_pagenum_link($pages).'" class="emu-page-last">Last &raquo;</a>';

        $pagination = $before.$pagination.$after;

    }

    if( $echo ) echo $pagination;

    return $pagination;

}

function apply_date_format( $format, $date = null )
{
    if( !$date )
        $unix_time = time();
    else
        $unix_time = strtotime( $date );

    switch( $format )
    {
        case 'standard': $date = date( 'jS F Y', $unix_time ); break;
        case 'standard-with-time': $date = date( 'jS F Y h:i', $unix_time ); break;
        case 'standard-with-time-alternative': $date = date( 'F j Y - h:i', $unix_time ); break;
        case 'short-with-time': $date = date( 'd/M/Y h:i', $unix_time ); break;
        case 'short': $date = date( 'd/M/Y', $unix_time ); break;
        case 'time': $date = date( 'g:ia', $unix_time ); break;
        case 'full-time': $date = date( 'g:i:sa', $unix_time ); break;
        case 'time-24': $date = date( 'H:i', $unix_time ); break;
        case 'full-time-24': $date = date( 'H:i:s', $unix_time ); break;
        case 'review': $date = date( 'F Y', $unix_time ); break;
        case 'db': $date = date( 'Y-m-d H:i:s', $unix_time ); break;
        default:
            $date = date( $format, $unix_time );
    }

    return $date;
}

function apply_number_format( $format, $number )
{
    if( !is_numeric( $number ) ) return;
    if( $number === '' ) return;

    switch( $format )
    {
        case 'currency':

            $number = number_format( $number, 2, '.', ',' );
            $number = str_replace('.00', '', $number);

        break;
        case 'percentage': $number = number_format( $number, 1, '.', ',' ); break;
        case 'weight': $number = number_format( $number, 1, '.', ',' ); break;
    }

    return $number;

}

function simple_trace( $hidden = true )
{
    $trace_arr = debug_backtrace();

    if( $hidden ) echo '<!--';

    foreach( $trace_arr as $trace )
    {
        echo @$trace['file'].' '.@$trace['line']."<br />\n";
    }

    if( $hidden ) echo '-->';
}

function matchShortcodes($src, $returnbrackets = false)
{
    preg_match_all('/\[(.*?)\]/s', $src, $matches); //use the s at the end for making dot 'really' be all normally dot doesnt include newlines
    if($returnbrackets) return $matches[0];
    return $matches[1];
}

function matchOptionParams($name, $optiongroup)
{
    if( preg_match('/(.*?)'.$name.'=("|\')(?P<'.$name.'>.*?)("|\')(.*?)/s',$optiongroup,$matches) )
        $r = $matches[$name];

    $r = preg_replace('/\s+/', ' ',$r); //replace multi space with single
    $r = trim($r,', '); //trim commas and spaces

    return $r;
}

function drop_down( $id, $name, $class, $selected, $options, $default_null = null, $add_attributes = '' )
{
    if( !is_array( $options ) ) return '';

    if( array_values($options) === $options )
        $values = $text = array_values($options);
    else
    {
        $values = array_keys($options);
        $text = array_values($options);
    }

    if( empty( $id ) ) $id = $name;

    $drop_down = "<select name='$name' id='$id' class='$class' $add_attributes>";

    if( !is_null( $default_null ) ) $drop_down .= '<option value="">'.$default_null.'</option>';

    for( $n = 0; $n < count( $values ); $n++ )
    {
        $is_selected = strval($values[$n]) === strval($selected) ? ' selected="selected"' : '';
        $drop_down .= sprintf( '<option value="%s"%s>%s</option>', $values[$n], $is_selected, $text[$n] );
    }

    $drop_down .= '</select>';

    return $drop_down;
}


function get_emu_data( $path, $data, $ext = '.txt' )
{
    $data = file_get_contents( $path . '/data/'.$data.$ext );

    return $data;
}

function has_menu_group()
{
    global $menu;

    $menu_group_exists = false;

    foreach($menu as $menu_group)
    {
        if( in_array( 'emuMenuGroup', $menu_group ) ) $menu_group_exists = true;
    }

    return $menu_group_exists;
}

function start_exec_timer()
{
    list ($msec, $sec) = explode(' ', microtime());
    $microtime = (float)$msec + (float)$sec;
    $GLOBALS['exec_start'] = $microtime;
    $GLOBALS['memory_start'] = memory_get_usage();
}

function stop_exec_timer()
{
    if( !isset($GLOBALS['exec_start']) )
    {
        echo 'Timer has not been started';
        return;
    }

    list ($msec, $sec) = explode(' ', microtime());
    $microtime = (float)$msec + (float)$sec;

    echo 'Script Execution Time: ' . round($microtime - $GLOBALS['exec_start'], 3) . ' seconds'."\n";
    echo 'Memory: '. round( ( ( memory_get_usage() - $GLOBALS['memory_start'] ) / 1024 ), 3 ) ."k\n";
}

if ( !function_exists('sys_get_temp_dir'))
{
    function sys_get_temp_dir()
    {
        if( $temp=getenv('TMP') )
            return $temp;

        if( $temp=getenv('TEMP') )
            return $temp;

        if( $temp=getenv('TMPDIR') )
            return $temp;

        $temp = tempnam(__FILE__,'');

        if (file_exists($temp))
        {
            unlink($temp);
            return dirname($temp);
        }

        return null;
    }
}




?>