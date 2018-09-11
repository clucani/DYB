<?php

class emuM_UserAdmin extends emuManager
{
    public function init()
    {
        // Processors
        $this->registerProcessorClass( 'login' );
        
        $this->emuApp->registerModel( 'owner', 'model.owner.class.php', 'emuDbEntity' );
    }

    public function isUserLoggedIn()
    {
    	return $this->emuApp->getSessionData('owner');
    }

    public function setUserLoggedIn($owner_id)
    {
    	$this->emuApp->setSessionData('owner', $owner_id);
    }

    public function setUserLoggedOut()
    {
    	$this->emuApp->deleteSessionData('owner');
    }

    public function checkAuth()
    {
		if($owner_id = $this->isUserLoggedIn()) return $owner_id;
		
		$this->emuApp->setSessionData('login_return_url', $_SERVER['REQUEST_URI']);
		header('Location: /login');
		exit();
    }

    public function getOwner($owner_id)
    {
        return $this->emuApp->getModel('owner', $owner_id);
    }

    public function install()
    {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE {$this->emuApp->dbPrefix}owners (
        dbID int(10) NOT NULL AUTO_INCREMENT,
        firstName varchar(300) default NULL,
        lastName varchar(300) default NULL,
        email varchar(300) default NULL,
        contactNumber varchar(300) default NULL,
        passwordHash varchar(300) default NULL,
        passwordResetCode varchar(300) default NULL,
        signUpForm text default NULL,
        signUpDate datetime default NULL,
        UNIQUE KEY id (dbID)
        );";

        dbDelta($sql);

    }    

}

?>