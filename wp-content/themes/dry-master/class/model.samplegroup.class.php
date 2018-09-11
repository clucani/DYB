<?php

class sampleGroup extends emuDbEntity
{
    function config()
    {
        global $emuTheme;

        $this->dbPrefix = $emuTheme->dbPrefix;
        $this->dbTable = 'sample_groups';
    }

    public function getBagsLink()
    {
    	return get_bloginfo('url').'/bags/?group='.$this->getID();
    }

    public function delete()
    {
        $bags = $this->getBags();

        foreach( $bags as $bag )
            $bag->delete();
        
        parent::delete();
    }

    public function setOwner($owner)
    {
        $this->ownerID = $owner->getID();
    }

    public function getOwner()
    {
        global $emuTheme;
        return $emuTheme->userAdminManager->getOwner($this->ownerID);
    }

    public function getBags()
    {
        global $wpdb, $emuTheme;

        $sql = "select dbID from {$emuTheme->dbPrefix}bags where sampleGroupID = ".$this->getID()." order by displayID ASC";

        $bag_ids = $wpdb->get_col($sql);

        $bags = array();

        foreach( $bag_ids as $bag_id )
            $bags[] = $emuTheme->getModel('bag', $bag_id);
        
        return $bags;

    }
}
?>