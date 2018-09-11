<?php

class emuP_LogEditor extends emuProcessor
{
    public $bag;
    public $logEntry;
    public $responseBody; 
    public $method;
    public $owner;

    public function process()
    {
        $this->error = false; // we'll be optimistic

        // Make sure they're logged in
        $owner_id = $this->emuApp->userAdminManager->checkAuth();

        $this->owner = $this->emuApp->userAdminManager->getOwner($owner_id);

        $this->logEntry = $this->emuApp->bagsManager->getLogEntry(post_val('entryID'));
        $this->bag = $this->emuApp->bagsManager->getBag(post_val('bagID')); 

        $this->method = post_val('method');

        switch($this->method)
        {
            case 'get-entry':
                $response_body = $this->getEntry();
                break;
            case 'get-group-bags':
                $response_body = $this->getGroupBags();
                break;
            case 'save-entry':
                $response_body = $this->saveEntry();
                break;
            case 'get-entry-table':
                $response_body = $this->getEntryTable();
                break;
            case 'delete-entry':
                $response_body = $this->deleteEntry();
                break;
            default:
                return;
        }
        
        $this->doResponse( ($this->error ? 'error' : 'OK'), $response_body );
    }

    public function doResponse($code, $body)
    {
        header('Content-type: application/json');
        echo json_encode( array(
            'responseCode' => $code,
            'responseBody' => $body,
        ), JSON_NUMERIC_CHECK);
        exit();
    }

    private function getEntry()
    {
        return $this->logEntry->data;
    }

    private function getGroupBags()
    {
        $sampleGroup = $this->emuApp->bagsManager->getSampleGroup(post_val('sampleGroupID'));

        $bag_list = array();

        foreach($sampleGroup->getBags() as $bag)
            $bag_list[] = array( 'id' => $bag->getID(), 'name' => $bag->displayID );

        return $bag_list;
    }

    private function getEntryTable()
    {
        return array( 'markup' => $this->emuApp->getView('drying-log', array('bag' => $this->bag)));
    }

    private function saveEntry()
    {
        $this->logEntry->entryType = post_val('entryType');
        $this->logEntry->logTime = post_val('logDate').' '.post_val('logTime');
        $this->logEntry->duration = post_val('duration');
        $this->logEntry->waterPotential = post_val('waterPotential');
        
        if($this->logEntry->save())
            return 1;
        else 
        {
            $this->error = true;
            return 0;
        }
    }

    private function deleteEntry()
    {
        $this->logEntry->delete();
        return 1;
    }
}

?>