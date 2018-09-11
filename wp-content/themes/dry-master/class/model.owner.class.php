<?php

class owner extends emuDbEntity
{
    function config()
    {
        global $emuTheme;

        $this->dbPrefix = $emuTheme->dbPrefix;
        $this->dbTable = 'owners';
        $this->stripslashes = true;
    }

    public function getProperties()
    {
    	global $emuTheme, $wpdb;

    	$sql = "select dbID from {$emuTheme->dbPrefix}properties where ownerID = %s";

    	$property_ids = $wpdb->get_col($wpdb->prepare($sql, $this->getID()));

    	$properties = array();

    	foreach( $property_ids as $id )
    	{
    		$properties[] = $emuTheme->advertsManager->getProperty($id);
    	}

    	return $properties;
    }

    public function getSampleGroups()
    {
        global $wpdb, $emuTheme;

        $sql = "select dbID from {$emuTheme->dbPrefix}sample_groups where ownerID = ".$this->getID()." order by groupName ASC";

        $db_ids = $wpdb->get_col($sql);

        $sample_groups = array();

        foreach( $db_ids as $id )
        {
            $sample_groups[] = $emuTheme->getModel('sampleGroup', $id);
        }
        
        return $sample_groups;
    }


}