<?php

class emuM_Bags extends emuManager
{
    const STEP_NEW_BAG = 10;
    const STEP_DRYING_SETUP = 1;
    const STEP_DRYING = 2;
    const STEP_DRYING_COMPLETE = 3;
    const STEP_EQUILIBRATING = 4;
    const STEP_EQUILIBRATED = 5;
    const STEP_BAG_FINISHED = 11;

    const LOG_TYPE_DRYING = 1;
    const LOG_TYPE_EQUIL = 2;
    const LOG_TYPE_MEASUREMENT = 3;

    public $stepDescriptions = array();
    public $entryDescriptions = array();

    public function init()
    {
        // Views
        $this->emuApp->registerView('steps');
        $this->emuApp->registerView('drying-log');
        $this->emuApp->registerView('drying-trends');
        $this->emuApp->registerView('log-editor-modal');
        
        // Processors
        $this->registerProcessorClass('drier');
        $this->registerProcessorClass('log-editor');
        $this->registerProcessorClass('sample-groups');
        $this->registerProcessorFunction( 'analyze-phases', array($this, 'doPhaseAnalysisRedirect'));
        
        // Models
        $this->emuApp->registerModel( 'bag', 'model.bag.class.php', 'emuDbEntity' );
        $this->emuApp->registerModel( 'logEntry', 'model.logentry.class.php', 'emuDbEntity' );
        $this->emuApp->registerModel( 'sampleGroup', 'model.samplegroup.class.php', 'emuDbEntity' );

        $this->stepDescriptions[self::STEP_NEW_BAG] = 'New Bag';
        $this->stepDescriptions[self::STEP_DRYING_SETUP] = 'Setup Drying';
        $this->stepDescriptions[self::STEP_DRYING] = 'Drying';
        $this->stepDescriptions[self::STEP_DRYING_COMPLETE] = 'Drying Complete';
        $this->stepDescriptions[self::STEP_EQUILIBRATING] = 'Equilibrating';
        $this->stepDescriptions[self::STEP_EQUILIBRATED] = 'Equilibrated';
        $this->stepDescriptions[self::STEP_BAG_FINISHED] = 'Finished';

        $this->entryDescriptions[self::LOG_TYPE_DRYING] = 'Drying';
        $this->entryDescriptions[self::LOG_TYPE_EQUIL] = 'Equilibrating';
        $this->entryDescriptions[self::LOG_TYPE_MEASUREMENT] = 'Measurement';
    }

    public function doPhaseAnalysisRedirect()
    {
        $bag = $this->getBag(get_val('bag'));

        // get the bag points
        $points = $bag->getDryingTimevsWP();

        $x = array();
        $y = array();

        foreach( $points as $point )
        {
            $x[] = round(($point[0] / 60 / 60),2);
            $y[] = $point[1];
        }

        $redirect_url = 'x_values='.urlencode(implode(',', $x)).'&y_values='.urlencode(implode(',', $y)).'&threshold=2&remote_vars=true';

        header('Location: http://pv.lucani.com.au/?'.$redirect_url);
        exit();
    }

    public function loadScripts()
    {
        // JScript lib
        if( !is_admin() )
        {
            $this->emuApp->loadScript( 'moment', $this->emuApp->sThemeURL."/js/libs/moment.min.js", array( 'jquery' ) );
            
            $this->emuApp->loadScript( 'model-summaryplotter', $this->emuApp->sThemeURL."/js/model.summaryplotter.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'panel-bagfinished', $this->emuApp->sThemeURL."/js/panel.bagfinished.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'panel-equilibrated', $this->emuApp->sThemeURL."/js/panel.equilibrated.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'panel-equilibrating', $this->emuApp->sThemeURL."/js/panel.equilibrating.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'panel-dryingcomplete', $this->emuApp->sThemeURL."/js/panel.dryingcomplete.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'panel-drying', $this->emuApp->sThemeURL."/js/panel.drying.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'panel-dryingsetup', $this->emuApp->sThemeURL."/js/panel.dryingsetup.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'panel-newbag', $this->emuApp->sThemeURL."/js/panel.newbag.js?".date('ymdhis'), array( 'jquery' ) );
            $this->emuApp->loadScript( 'model-bag', $this->emuApp->sThemeURL."/js/model.bag.js?".date('ymdhis'), array( 'jquery' ) );
            
            $this->emuApp->loadScript( 'drier', $this->emuApp->sThemeURL."/js/app.drier.js?".date('ymdhis'), array( 'moment', 'model-summaryplotter', 'model-bag', 'panel-bagfinished', 'panel-equilibrated', 'panel-equilibrating', 'panel-dryingcomplete', 'panel-drying', 'panel-dryingsetup', 'panel-newbag' ) );
            $this->emuApp->loadScript( 'log-editor', $this->emuApp->sThemeURL."/js/app.logeditor.js?".date('ymdhis'), array( 'moment' ) );
        }
    }

    public function getBag($bag_id)
    {
        return $this->emuApp->getModel('bag', $bag_id);
    }

    public function getLogEntry($entry_id)
    {
        return $this->emuApp->getModel('logEntry', $entry_id);
    }

    public function getSampleGroup($group_id)
    {
        return $this->emuApp->getModel('sampleGroup', $group_id);
    }

    public function install()
    {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE {$this->emuApp->dbPrefix}sample_groups (
        dbID int(10) NOT NULL AUTO_INCREMENT,
        groupName varchar(300) default NULL,
        sampleGroupID int(10) default NULL,
        ownerID int(10) default NULL,
        dateCreated datetime default NULL,
        UNIQUE KEY id (dbID)
        );";

        dbDelta($sql);

        $sql = "CREATE TABLE {$this->emuApp->dbPrefix}bags (
        dbID int(10) NOT NULL AUTO_INCREMENT,
        displayID varchar(300) default NULL,
        sampleGroupID int(10) default NULL,
        currentStep int(10) default NULL,
        stepStartTime datetime default NULL,
        duration int(10) default NULL,
        UNIQUE KEY id (dbID)
        );";

        dbDelta($sql);

        $sql = "CREATE TABLE {$this->emuApp->dbPrefix}drying_logs (
        dbID int(10) NOT NULL AUTO_INCREMENT,
        bagID int(10) default NULL,
        entryType int(10) default NULL,
        logTime datetime default NULL,
        duration int(10) default NULL,
        waterPotential int(10) default NULL,
        UNIQUE KEY id (dbID)
        );";

        dbDelta($sql);

    }

}

?>