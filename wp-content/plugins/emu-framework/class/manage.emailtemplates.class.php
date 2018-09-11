<?php

class emuM_EmailTemplates extends emuM_Templates
{
    public function __construct( $emuApp = null )
    {
        parent::__construct( $emuApp );

        $this->templateType = 'email';
        $this->templateDirectory    = $emuApp->pluginPath.'/email';

        // Register Ajax functions
        $this->registerAjaxAction( 'get_email_template', array( $this, 'AJAX_getEmailTemplate' ) );
        $this->registerAjaxAction( 'save_email_template', array( $this, 'AJAX_saveEmailTemplate' ) );
    }

    public function AJAX_getEmailTemplate()
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

    public function AJAX_saveEmailTemplate()
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
        $this->emuApp->registerAdminPage( array( 'name' => 'Email Templates',  'directory' => EMU_FRAMEWORK_PATH.'/admin/', 'filename' => 'emails.php', 'styles' => array( 'emu-templates' ), 'scripts' => array( 'emu-templates' ), 'position' => 21 ) );
    }

    function extractEmailData( $template )
    {
        $file_data = $template;

        $email = '';
        $email_from = '';
        $email_to = '';
        $email_cc = '';
        $email_bcc = '';
        $email_subject = '';
        $email_type = '';

        if ( preg_match( '|Emu Email:(.*)$|mi', $file_data, $matches ) )
            $email = _cleanup_header_comment( $matches[1] );

        if ( preg_match( '|Email From:(.*)$|mi', $file_data, $matches ) )
            $email_from = _cleanup_header_comment( $matches[1] );

        if ( preg_match( '|Email To:(.*)$|mi', $file_data, $matches ) )
            $email_to = _cleanup_header_comment( $matches[1] );

        if ( preg_match( '|Email CC:(.*)$|mi', $file_data, $matches ) )
            $email_cc = _cleanup_header_comment( $matches[1] );

        if ( preg_match( '|Email BCC:(.*)$|mi', $file_data, $matches ) )
            $email_bcc = _cleanup_header_comment( $matches[1] );

        if ( preg_match( '|Email Subject:(.*)$|mi', $file_data, $matches ) )
            $email_subject = _cleanup_header_comment( $matches[1] );

        if ( preg_match( '|Email Type:(.*)$|mi', $file_data, $matches ) )
            $email_type = _cleanup_header_comment( $matches[1] );

        // strip out the header for the content
        $content = $template;

        $content = preg_replace( '/<\?php(?:.+)\?>/s', '', $content );

        $template = (object) array( 'name' => $email,
                                    'content' => $content,
                                    'from' => $email_from,
                                    'to' => $email_to,
                                    'cc' => $email_cc,
                                    'bcc' => $email_bcc,
                                    'subject' => $email_subject,
                                    'type' => $email_type
                                    );
        return $template;
    }

    /* Depracated
     * Retrieve the whole template then apply the fill methods and then extract
     * the email data using extractEmailData
     */
    //////////////////////////////////////////////////////
    function getEmailTemplate( $file )
    {
        $template = $this->getTemplate( $file, 'email' );

        return $this->extractEmailData( $template );
    }
    //////////////////////////////////////////////////////


}


?>