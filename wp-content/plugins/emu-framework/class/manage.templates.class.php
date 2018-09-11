<?php

class emuM_Templates extends emuManager
{
    public $useSourceTemplateFiles = false;

    public $templateDirectory;

    public $templateType;

    public function __construct( $emuApp = null )
    {
        parent::__construct( $emuApp );

        $this->templateType = 'content';
        $this->templateDirectory    =   $emuApp->pluginPath.'/template';
        $this->useSourceTemplateFiles = $emuApp->useSourceTemplateFiles;

        // Register Ajax functions
        $this->registerAjaxAction( 'get_template', array( $this, 'AJAX_getTemplate' ) );
        $this->registerAjaxAction( 'save_template', array( $this, 'AJAX_saveTemplate' ) );
    }

    public function AJAX_getTemplate()
    {
        extract($_POST);

        header('Content-type: application/json');

        $template = $this->getTemplates( array( 'ID' => $template_id ) );

        $ok = false; $template_content = '';

        if( count( $template ) > 0 )
        {
            $template_content = stripslashes( $template[0]->template );
            $ok = true;
        }

        echo json_encode( array( "ok" => $ok, "templateContent" => $template_content ) );
    }

    public function AJAX_saveTemplate()
    {
        extract($_POST);

        header('Content-type: application/json');

        if( $this->updateTemplate( stripslashes( $template_content ), array( 'ID' => $template_id ) ) )
            $ok = true;
        else
            $ok = false;

        echo json_encode( array( 'ok' => $ok ) );
    }

    public function registerAdminPages()
    {
        $this->emuApp->registerAdminPage( array( 'name' => 'Templates', 'directory' => EMU_FRAMEWORK_PATH.'/admin/', 'filename' => 'templates.php', 'styles' => array( 'emu-templates' ),  'scripts' => array( 'emu-templates' ), 'position' => 20 ) );
    }

    public function loadStyles()
    {
        $this->emuApp->registerStyle( 'emu-templates', EMU_FRAMEWORK_URL.'/css/templates.css', array( 'emu-codemirror' ) );
    }

    public function loadScripts()
    {
        $this->emuApp->registerScript( 'emu-templates', EMU_FRAMEWORK_URL.'/js/templates.js', array( 'emu-codemirror' ) );
    }

    function getTemplateFiles()
    {
        $files = $this->emuApp->getFiles( $this->templateDirectory );

        $files = apply_filters_ref_array( 'emu_shop_template_files', array( $files, $this->templateType ) );

        return $files;
    }

    public function updateTemplate( $template, $terms = array() )
    {
        global $wpdb;

        return $wpdb->update( $this->emuApp->dbPrefixShared.'templates', array( 'template' => $template ), $terms, array( '%s' ) );
    }

    public function getTemplate( $filename )
    {
        global $wpdb;

        if( $this->useSourceTemplateFiles )
        {
            $file_path = $this->templateDirectory.'/';

            $file_path .= $filename;

            if( file_exists( $file_path ) )
            {
                $template_content = file_get_contents( $file_path );
            }
            else
            {
                trigger_error( "$filename does not exist", E_USER_WARNING );
                $template_content = '';
            }
        }
        else
        {
            // retrieve the templates from the DB

            if( $template = $this->getTemplateRecord( $filename ) )
            {
                $template_content = stripslashes( $template->template );
            }
            else
            {
                trigger_error( "Couldn't find template '$filename'", E_USER_WARNING );
                $template_content = '';
            }
        }

        $template_content = apply_filters_ref_array( $this->emuApp->emuAppID.'_template_manager_get_template', array( $template_content, $filename, $this->templateType ) );

        return $template_content;

    }

    public function replaceTag( $field, $value, &$template )
    {
        $template = str_replace( "[$field]", $value, $template );
    }

    public function fillTemplate( $template, $tags, $contentObject = null, $meta_post_id = null )
    {
        global $wpdb;

        if( !$tags ) return $template;

        // hunt through the template and find all tags
        if( preg_match_all( '/\[([^\]]+)\]/', $template, $tag_matches ) )
        {
            for( $n = 0; $n < count( $tag_matches[1] ); $n++ )
            {
                $tag = $tag_matches[1][$n];

                // only replace tags if they are found in the objects tag list
                if( in_array( $tag, array_keys( $tags ) ) )
                {
                    $tag_value = $this->getTagValue( $contentObject, $tags[ $tag ] );
                    $this->replaceTag( $tag, $tag_value, $template );
                }

                if( !isset( $this->emuApp->templateTags ) ) continue;

                // Also see if any tags from the app match
                if( in_array( $tag, array_keys( $this->emuApp->templateTags ) ) )
                {
                    $tag_value = $this->getTagValue( $this->emuApp, $this->emuApp->templateTags[ $tag ] );
                    $this->replaceTag( $tag, $tag_value, $template );
                }
            }
        }

        $template = $this->fillTemplateSpecialTags( $template, $meta_post_id );

        return $template;
    }

    public function fillTemplateSpecialTags( $template, $post_id = null )
    {
        if( preg_match_all( '/\[template name=("|\')([^"]+)("|\')\]/', $template, $fields ) )
        {
            for( $n = 0; $n < count( $fields[2] ); $n++ )
            {
                $template_name = $fields[2][$n];

                $included_template = $this->getTemplate( $template_name );

                $template = preg_replace( '/\[template name='.$fields[1][$n].$fields[2][$n].$fields[3][$n].'\]/', $included_template, $template );
            }
        }

        if( preg_match_all( '/\[page name=("|\')([^"]+)("|\') field=("|\')([^"]+)("|\')\]/', $template, $fields ) )
        {
            for( $n = 0; $n < count( $fields[2] ); $n++ )
            {
                $page_name = $fields[2][$n];
                $page_field = $fields[5][$n];

                if( !$page_value = $this->getPageValue( $page_name, $page_field ) ) $page_value = '';

                $template = preg_replace( '/\[page name='.$fields[1][$n].$page_name.$fields[3][$n].' field='.$fields[4][$n].$page_field.$fields[6][$n].'\]/', $page_value, $template );
            }
        }

        if( !$post_id ) return $template;

        if( preg_match_all( '/\[custom field=("|\')([^"]+)("|\')\]/', $template, $fields ) )
        {
            for( $n = 0; $n < count( $fields[2] ); $n++ )
            {
                $field_name = $fields[2][$n];

                // get meta data for that field
                $meta_value = get_post_meta( $post_id, $field_name, true );

//if($field_name == 'incidencePriceNYLA'){
//	d($meta_value);
//	d(preg_quote($meta_value));
//}
if($this->startsWith($meta_value, '\$') 	)
	$meta_value = $meta_value;
elseif($this->startsWith($meta_value, '$'))
	//$val = preg_quote($meta_value);
	$meta_value = str_replace('$','\$',$meta_value);
else{
	//$val = str_replace('$','\$',$meta_value);
	$newval = str_replace('\$','$',$meta_value);
	if($newval != $meta_value)
		$meta_value = $meta_value;//it already has slashed $, just leave it
	else
		$meta_value = str_replace('$','\$',$newval);// $ not slashed, slash them
	//$val = $meta_value;
}

                $template = preg_replace( '/\[custom field="'.$field_name.'"\]/', $meta_value, $template );
            }
        }

        return $template;
    }
function startsWith($haystack, $needle){
    return !strncmp($haystack, $needle, strlen($needle));
}


    public function fillTemplateSections( $template, $tags )
    {
        $sections = $this->findTemplateSections( $template );

        foreach( $sections as $tag => $tagInfo )
        {
            if( !in_array( $tag, array_keys( $tags ) ) ) continue;

            $template = str_replace( $tagInfo->replaceContent, $tags[ $tag ], $template );
        }

        return $template;

    }

    public function fillTemplateRepeats( $template, $tags )
    {
        $sections = $this->findRepeatSections( $template );

        foreach( $sections as $tag => $tagInfo )
        {
            if( !in_array( $tag, array_keys( $tags ) ) ) continue;

            $template = str_replace( $tagInfo->replaceContent, $tags[ $tag ], $template );
        }

        return $template;

    }

    public function getTemplateSection( $section_name, $template, $fetch_file = false )
    {
        if( $fetch_file )
            $template = $this->getTemplate( $template );

        $sections = $this->findTemplateSections( $template );

        if( isset( $sections[ $section_name ] ) ) return $sections[ $section_name ]->content;
    }

    public function getTemplateRepeat( $repeat_name, $template, $fetch_file = false )
    {
        if( $fetch_file )
            $template = $this->getTemplate( $template );

        $sections = $this->findRepeatSections( $template );

        if( isset( $sections[ $repeat_name ] ) ) return $sections[ $repeat_name ]->content;
    }

    public function findRepeatSections( $template )
    {
        $sections = array();

        // hunt through and find all the template tags
        if( preg_match_all( '/\[repeat\s*tag=["\']([^"\']+)["\']\]([\s\S]+?)\[\/repeat\]/', $template, $fields ) )
        {
            $replace_content = 0;
            $tag = 1;
            $content = 2;

            for( $n = 0; $n < count( $fields[1] ); $n++ )
                $sections[ $fields[ $tag ][$n] ] = (object) array( 'content' => $fields[ $content ][$n], 'replaceContent' => $fields[ $replace_content][$n] );
        }
        return $sections;
    }

    public function findTemplateSections( $template )
    {
        $sections = array();

        // hunt through and find all the template tags
        if( preg_match_all( '/\[template\s*tag=["\']([^"\']+)["\']\]([\s\S]+?)\[\/template\]/', $template, $fields ) )
        {
            $replace_content = 0;
            $tag = 1;
            $content = 2;

            for( $n = 0; $n < count( $fields[1] ); $n++ )
                $sections[ $fields[ $tag ][$n] ] = (object) array( 'content' => $fields[ $content ][$n], 'replaceContent' => $fields[ $replace_content][$n] );
        }
        return $sections;
    }

    public function findTemplateConditionals( $template )
    {
        $sections = array();

        if( preg_match_all( '/\[conditional\s*tag=["\']([^"\']+)["\']\]([\s\S]+?)\[\/conditional\]/', $template, $fields ) )
        {
            $replace_content = 0;
            $tag = 1;
            $content = 2;

            for( $n = 0; $n < count( $fields[1] ); $n++ )
                $sections[] = (object) array( 'tag' => $fields[ $tag ][$n], 'content' => $fields[ $content ][$n], 'replaceContent' => $fields[ $replace_content][$n] );
        }
        return $sections;
    }


    public function setTemplateConditionals( $template, $conditionals )
    {
        $ctags = $this->findTemplateConditionals( $template );

        foreach( $ctags as $tagInfo )
        {
            $tag = $tagInfo->tag;

            if( !in_array( $tag, array_keys( $conditionals ) ) ) continue;

            $content = $conditionals[ $tag ] ? $tagInfo->content : '';

            $template = str_replace( $tagInfo->replaceContent, $content, $template );
        }

        return $template;
    }


    public function findTemplateCustomConditionals( $template )
    {
        $sections = array();

        if( preg_match_all( '/\[custom\s*conditional=["\']([^"\']+)["\']\s*condition=["\']([^"\']*)["\']\]([\s\S]+?)\[\/custom\]/', $template, $fields ) )
        {
            $replace_content = 0;
            $tag = 1;
            $condition = 2;
            $content = 3;

            for( $n = 0; $n < count( $fields[1] ); $n++ )
                $sections[] = (object) array( 'tag' => $fields[ $tag ][$n], 'condition' => $fields[ $condition ][$n], 'content' => $fields[ $content ][$n], 'replaceContent' => $fields[ $replace_content][$n] );
        }
        return $sections;
    }


    public function setTemplateCustomConditionals( $template, $post_id = null )
    {
        if( !$post_id ) return $template;

        $ctags = $this->findTemplateCustomConditionals( $template );

        foreach( $ctags as $tagInfo )
        {
            // get meta data for that field
            $meta_value = get_post_meta( $post_id, $tagInfo->tag, true );
            $meta_value = trim( $meta_value );

            $conditional_boolean = false;

            if( strcmp( $tagInfo->condition, $meta_value ) == 0 ) // no difference
                $conditional_boolean = true;

            $content = $conditional_boolean ? $tagInfo->content : '';

            $template = str_replace( $tagInfo->replaceContent, $content, $template );
        }

        return $template;
    }


    private function getPageValue( $page_name, $page_field )
    {
        if( !isset( $this->emuApp->pages ) ) return false;

        foreach( $this->emuApp->pages as $page )
        {
            if( strcasecmp( str_replace( ' ', '', $page->description ), str_replace( ' ', '', $page_name ) ) == 0 )
            {
                if( isset( $page->$page_field ) ) return $page->$page_field;
            }

        }
        return false;
    }

    public function getTagValue( $contentObject, $tag_value )
    {
        global $wpdb;

        $original_tag_value = $tag_value; // this because array_shifting all array values doesn't end up with a null value (ends up as an empty array)

        if( ! is_array( $tag_value ) )
        {
            if( $contentObject )
                return $contentObject->$tag_value;
            else
                return $tag_value;
        }

        // Otherwise if the tag value is an array then it's a function or method call

        // first item in array is the object
        $object = array_shift( $tag_value );

        // second item is the function name (if no object is defined) or method (if the object is defined) or property (if $parameters is null)
        $function_or_property = array_shift( $tag_value );

        // the rest of the array are parameters to send to the function
        $parameters = $tag_value;

        $arguments = array();

        // some of the parameters may be function calls themselves (and these values as well and so on) so dig down and retrieve them
        foreach( $parameters as $parameter_value )
        {
            if( is_array( $parameter_value ) )
                $arguments[] = $this->getTagValue( $contentObject, $parameter_value );
            else
                $arguments[] = $parameter_value;
        }

        if( $object )
        {
            global $$object;

            $object = $object == 'this' ? $contentObject : $$object;

            // if arguments is null (i.e. array length is only 2) then we're retrieving an object property e.g. array( 'this', 'property' )
            if( count( $original_tag_value ) == 2 )
                $tag_value = $object->$function_or_property;
            else
                $tag_value = call_user_func_array( array( $object, $function_or_property ), $arguments );
        }
        else
            $tag_value = call_user_func( $function_or_property, $arguments );

        return $tag_value;
    }

    public function refreshTemplates()
    {
        global $wpdb;

        $arr_template_files = $this->getTemplateFiles();

        $templates = $this->getTemplates();

        // loop through the file list and see if any are missing from the template list
        for( $n = 0; $n < count( $arr_template_files ); $n++ )
        {
            $filename = basename( $arr_template_files[$n] );

            $template_found = false;

            foreach( $templates as $template )
            {
                if( $template->filename == $filename ) $template_found = true;
            }

            if( !$template_found )
            {
                $template_content = file_get_contents( $arr_template_files[$n] );
                $wpdb->insert( $this->emuApp->dbPrefixShared.'templates', array( 'filename' => $filename, 'template' => $template_content, 'templateType' => $this->templateType, 'emuAppID' => $this->emuApp->emuAppID ), array( '%s', '%s', '%s', '%s' ) );
            }
        }
    }

    public function populateTemplates()
    {

        global $wpdb;

        $this->clearTemplates();

        $templates = $this->getTemplateFiles();

        foreach( $templates as $template )
        {
            $template_content = file_get_contents( $template );

            $wpdb->insert( $this->emuApp->dbPrefixShared.'templates', array( 'filename' => basename( $template ), 'template' => $template_content, 'templateType' => $this->templateType, 'emuAppID' => $this->emuApp->emuAppID ), array( '%s', '%s', '%s', '%s' ) );
        }
    }


    public function clearTemplates()
    {
        global $wpdb;

        $sql = "delete from {$this->emuApp->dbPrefixShared}templates where emuAppID = '{$this->emuApp->emuAppID}' and templateType = '{$this->templateType}'";

        return $wpdb->query( $sql );
    }

    public function getTemplateRecord( $filename )
    {
        $template = $this->getTemplates( array( 'filename' => $filename ) );

        if( count( $template ) == 0 )
        {
            trigger_error( "Couldn't find template '$filename'", E_USER_WARNING );
            return false;
        }
        return $template[0];
    }

    public function getTemplates( $terms = array() )
    {
        global $wpdb;

        $sql_add = array();

        $sql = "select * from {$this->emuApp->dbPrefixShared}templates %s";

        if(isset($terms['filename'])) $sql_add[] = "filename = '{$terms['filename']}'";
        if(isset($terms['ID'])) $sql_add[] = "id = '{$terms['ID']}'";

        $sql_add[] = "templateType = '{$this->templateType}'";
        $sql_add[] = "emuAppID = '{$this->emuApp->emuAppID}'";

        $sql_add[] = 'active = '.( isset( $terms['active'] ) ? $terms['active'] : '1' );

        $sql_add = count($sql_add) > 0 ? 'where ('.implode(') and (', $sql_add).')' : '';

        $sql = sprintf( $sql, $sql_add );

        $templates_rs = $wpdb->get_results( $sql );

        return $templates_rs;
    }

    public function loadTemplateData()
    {
        // Populate the tables with the template data (if it's not there already)
        if( count( $this->getTemplates() ) == 0 ) $this->populateTemplates();
    }

    public function install( $emuApp = null )
    {
        if( !$emuApp ) return;

        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE {$emuApp->dbPrefixShared}templates (
        ID int(10) NOT NULL AUTO_INCREMENT,
        filename varchar(300) default NULL,
        template TEXT default NULL,
        templateType varchar(30) default NULL,
        emuAppID varchar(20) default NULL,
        active BOOLEAN NOT NULL DEFAULT 1,
        UNIQUE KEY ID (ID)
        );";

        dbDelta($sql);

        add_action( $emuApp->emuAppID.'_installed_loaded', array( $this, 'loadTemplateData' ) );
    }

}


?>