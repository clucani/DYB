<?php

class emuP_SampleGroups extends emuProcessor
{
    private $owner;

    public function process()
    {
        // Make sure they're logged in
        $owner_id = $this->emuApp->userAdminManager->checkAuth();

        $this->owner = $this->emuApp->userAdminManager->getOwner($owner_id);

        switch($this->button)
        {
            case 'Create':
                $this->createGroup();
                break;
            case 'View':
                $this->viewGroup();
                break;
            case 'Delete':
                $this->deleteGroup();
                break;
            default:
                return;
        }
        
        header('Location: /');
        exit();
    }

    public function viewGroup()
    {
        $this->requiredFields = array('sample_group');

        $this->checkRequiredFields();

        if( !$this->hasRequiredFields )
        {
            $this->emuApp->addMessage( 'sample-groups', 'Select a sample group.', 'error' );
            $this->error = true;
            return;
        }

        $group = $this->emuApp->bagsManager->getSampleGroup(post_val('sample_group'));

        if($group->getOwner()->getID() !== $this->owner->getID())
            return;

        header('Location: '.$group->getBagsLink());
        exit();
    }

    public function deleteGroup()
    {
        $this->requiredFields = array('sample_group');

        $this->checkRequiredFields();

        if( !$this->hasRequiredFields )
        {
            $this->emuApp->addMessage( 'sample-groups', 'Select a sample group.', 'error' );
            $this->error = true;
            return;
        }

        $group = $this->emuApp->bagsManager->getSampleGroup(post_val('sample_group'));

        if($group->getOwner()->getID() !== $this->owner->getID())
            return;
        
        $group->delete();

        $this->emuApp->addMessage( 'sample-groups', 'Group deleted.', 'notice' );
    }

    public function createGroup()
    {
        $this->requiredFields = array('group_name');

        $this->checkRequiredFields();

        if( !$this->hasRequiredFields )
        {
            $this->emuApp->addMessage( 'sample-groups', 'Group name required', 'error' );
            $this->error = true;
            return;
        }

        $group = $this->emuApp->getModel('sampleGroup');
        $group->groupName = post_val('group_name');
        $group->setOwner($this->owner);
        $group->save();

        $location = $group->getBagsLink();

        header('Location:'.$location);
        exit();        
    }

    public function processSampleGroup() 
    {

        if(post_val('delete-group'))
        {
            $group = $this->emuApp->getModel('sampleGroup', post_val('delete-group'));

            if($group->ownerID <> $owner->getID()) return; // not their group!
            
            $group->delete();
            $location = '/';
        }
        else
        {
            $group = $this->emuApp->getModel('sampleGroup');
            $group->groupName = post_val('group_name');
            $group->setOwner($owner);
            $group->save();
            $location = $group->getBagsLink();

        }
        header('Location:'.$location);
        exit();
    }

}

?>