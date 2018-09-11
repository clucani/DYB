<?php

class emuP_Login extends emuProcessor
{
    public $requiredFields = array('email', 'user_password');

    public function process()
    {
        $this->checkRequiredFields();

        if( !$this->hasRequiredFields )
        {
            $this->emuApp->addMessage( 'login', 'Please enter both your email address and password', 'error' );
            $this->error = true;
            return;
        }

        switch($this->button)
        {
            case 'Sign in':
                $this->doSignIn();
                break;
            default:
                return;
        }

        if( !$this->error )
        {
            $return_url = $this->emuApp->getSessionData('login_return_url');
            $location = get_bloginfo('url').'/sample-groups';
            header( "Location: $location" );
            exit();
        }
    }

    private function doSignIn()
    {
        global $wpdb;

        $sql = "SELECT dbID FROM {$this->emuApp->dbPrefix}owners WHERE email = %s AND passwordHash = %s";
        $prepared_sql = $wpdb->prepare($sql, post_val('email'), md5(post_val('user_password')));
        $owner_id = $wpdb->get_var($prepared_sql);

        if(is_null($owner_id))
        {
            $this->emuApp->addMessage( 'login', 'Hmmm... can\'t find any accounts that match that combination - are you sure your email address and passsword are correct?', 'error' );
            $this->error = true;
        }

        $this->emuApp->userAdminManager->setUserLoggedIn($owner_id);
    }
}

?>