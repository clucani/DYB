<?php

class emuEmail extends emuDbPostEntity
{
    public $messages = array();
    public $error = false;
    public $important = false;

    private $arrAttachments = array();

    private $emuApp;

    // Can either instatiate using the db ID or the Post (or Post ID)
    public function __construct( $emuApp, $dbID = null, $post = null )
    {
        $this->postType = 'emu-email';
        $this->taxonomy = 'email-category';

        $this->emuApp = $emuApp;

        parent::__construct( $dbID, $post, $emuApp->dbPrefixShared, 'email_logs', array( 'sent' => '%datefill', 'error' => '%b' ) );
    }

    public function send()
    {
        if( $this->emuApp->mailFunction == 'mail')
            $this->error = $this->sendUsingMailFunction();
        else
            $this->error = $this->sendUsingWPFunction();

        $this->sent = apply_date_format('db');
        $this->emuAppID = $this->emuApp->emuAppID;

        if( $this->emuApp->logEmails )
        {
            if( !$this->emuApp->sendEmails )
                $sSent = '(not sent - email turned off)';
            else
            {
                // $sSent = $this->error ? '(not sent, error)' : '';
                $sSent = '';
            }


            $this->postTitle = $this->subject.' '.$sSent;
            $this->postContent = $this->message;
            $this->postStatus = 'publish';
            $this->save();

            // add the header as post meta data
            add_post_meta( $this->postID, 'to', $this->emailTo );
            add_post_meta( $this->postID, 'from', $this->emailFrom );
            add_post_meta( $this->postID, 'bcc', $this->emailBcc );
            add_post_meta( $this->postID, 'cc', $this->emailCc );

        }
        return $this->error;
    }

    public function addAttachment( $filename )
    {
        if( is_array( $filename ) )
        {
            foreach( $filename as $filename_i )
                $this->addAttachment( $filename_i );
            return;
        }

        $this->arrAttachments[] = $filename;
    }

	private function addImportant(){
        $headers .= "X-Priority: 1 (Highest)\n";
        $headers .= "X-MSMail-Priority: High\n";
        $headers .= "Importance: High\n";
	return $headers;
	}

    private function sendUsingWPFunction()
    {
        $this->header = '';

        if( $this->emailType == 'html' )
            add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );

		if($this->important)
			$this->header .= $this->addImportant();

        $this->header .= "From: {$this->emailFrom}\r\n";

        if( $this->emailCc ) $this->header .= "Cc: {$this->emailCc}\r\n";
        if( $this->emailBcc ) $this->header .= "Bcc: {$this->emailBcc}\r\n";

        $mail_success = false;

        if( $this->emuApp->sendEmails )
            $mail_success = wp_mail( $this->emailTo, $this->subject, $this->message, $this->header, $this->arrAttachments );

        return $mail_success;
    }

    private function sendUsingMailFunction()
    {
        $this->header = '';

        if( $this->emailType == 'html' )
        {
            $this->header .= "MIME-Version: 1.0\r\n";
            $this->header .= "Content-type:text/html;charset=iso-8859-1\r\n";
        }

		if($this->important)
			$this->header .= $this->addImportant();

		$this->header .= "To: {$this->emailTo}\r\n";
        $this->header .= "From: {$this->emailFrom}\r\n";

        if( $this->emailCc ) $this->header .= "Cc: {$this->emailCc}\r\n";
        if( $this->emailBcc ) $this->header .= "Bcc: {$this->emailBcc}\r\n";

        $mail_success = false;

        if( $this->emuApp->sendEmails )
        {
            $mail_success = mail( $this->emailTo, $this->subject, $this->message, $this->header );
		}

        return $mail_success;
    }

    public function saveRecord()
    {
        $this->attachments = implode( ',', $this->arrAttachments );
        parent::saveRecord();
    }

    public function getData()
    {
        parent::getData();
        $this->arrAttachments = explode( ',', $this->attachments );
    }

}


?>