<?php

class bag extends emuDbEntity
{
    public $durationLastStep = 0;

    function config()
    {
        global $emuTheme;

        $this->dbPrefix = $emuTheme->dbPrefix;
        $this->dbTable = 'bags';
    }

    public function startStep($step, $duration = 0)
    {
        // Store the previous step duration
        $this->durationLastStep = time() - strtotime($this->stepStartTime);

        // Set the current step
        $this->currentStep = $step;
        $this->stepStartTime = apply_date_format('db');
        $this->duration = $duration;
    }

    public function addDryingTime($duration)
    {
        $this->addLogEntry(emuM_Bags::LOG_TYPE_DRYING, $duration);
    }

    public function addEqTime($duration)
    {
        $this->addLogEntry(emuM_Bags::LOG_TYPE_EQUIL, $duration);
    }

    public function addMeasurement($measurement)
    {
        $this->addLogEntry(emuM_Bags::LOG_TYPE_MEASUREMENT, $measurement);
    }

    public function delete()
    {
        $this->deleteLogEntries();
        parent::delete();
    }

    public function deleteLogEntries()
    {
        global $emuTheme, $wpdb;

        $sql = "delete from ".$emuTheme->dbPrefix."drying_logs where bagID = ".$this->getID();
        $wpdb->query($sql);
    }

    public function addLogEntry($type, $value)
    {
        global $emuTheme;

        $logEntry = $emuTheme->getModel('logEntry');
        $logEntry->entryType = $type;

        if($type == emuM_Bags::LOG_TYPE_MEASUREMENT)
            $logEntry->waterPotential = $value;
        else
            $logEntry->duration = $value;

        $logEntry->logTime = apply_date_format('db');
        $logEntry->bagID = $this->getID();
        $logEntry->save();
    }

    public function getStepDescription()
    {
        global $emuTheme;
        return $emuTheme->bagsManager->stepDescriptions[$this->currentStep];
    }

    public function getStepColor()
    {
        switch($this->currentStep)
        {
            case emuM_Bags::STEP_DRYING_SETUP:
                return "#d73027";
            break;
            case emuM_Bags::STEP_DRYING:
                return "#fc8d59";
            break;
            case emuM_Bags::STEP_DRYING_COMPLETE:
                return "#fee090";
            break;
            case emuM_Bags::STEP_EQUILIBRATING:
                return "#e0f3f8";
            break;
            case emuM_Bags::STEP_EQUILIBRATED:
                return "#91bfdb";
            break;
            case emuM_Bags::STEP_BAG_FINISHED:
            case emuM_Bags::STEP_NEW_BAG:
                return "#4575b4";
            break;
        }        
    }

    public function getSampleGroup()
    {
        global $emuTheme;
        return $emuTheme->bagsManager->getSampleGroup($this->sampleGroupID);
    }

    public function getDryingPhases()
    {
        global $emuTheme;

        // Get the points
        $points = $this->getDryingTimevsWP();

        // Phase determination better if the points are reversed
        $points = array_reverse($points);

        // Get the phases
        return $emuTheme->getPhases($points, 2, array());
    }

    public function getCurrentPhaseModel()
    {
        $phases = $this->getDryingPhases();
        return (object) $phases[0];
    }

    public function getWPEstimate($final = false)
    {
        // Get measured WP and Drying Times (order first to last measurement)
        $drying_history = $this->getDryingHistory();
        $drying_history = array_reverse($drying_history);
        
        // First check the current step
        switch($this->currentStep)
        {
            case emuM_Bags::STEP_NEW_BAG:
            
                // can't provide estimate
                return 0;
            
            break;
            
            case emuM_Bags::STEP_DRYING_SETUP:
            
                return $this->getLastKnownWP();
            
            break;

            // For all of the following we don't have the final measured WP
            // so we have to estimate based on...
            case emuM_Bags::STEP_DRYING:
            case emuM_Bags::STEP_DRYING_COMPLETE:

                // ... total logged drying time plus however long the current step has been running
                $drying_time = $this->getDryingDuration() + ($final ? $this->duration : $this->getStepTotalTime());

            break;

            case emuM_Bags::STEP_EQUILIBRATING:

            // ... the total drying time 
                $drying_time = $this->getDryingDuration();
                
            break;

            case emuM_Bags::STEP_EQUILIBRATED:
                
            // ... the total drying time
                $drying_time = $this->getDryingDuration();

            break;

            case emuM_Bags::STEP_BAG_FINISHED:
                return $this->getLastKnownWP();

            break;
        }    

        $phase = $this->getCurrentPhaseModel();

        // prx($phase->regression["obj"]->getValueOfYForX($drying_time));
        if(isset($phase->regression["best"])) {
            $wp_estimate = $phase->regression["best"]->obj->getValueOfYForX($drying_time);
        }   
        else
            $wp_estimate = 0;

        // $wp_estimate = $drying_time * $phase->slope + $phase->intercept;

        return $wp_estimate;
    }

    public function getLastKnownWP()
    {
        global $emuTheme, $wpdb;
        $sql = "select waterPotential from ".$emuTheme->dbPrefix."drying_logs where bagID = ".$this->getID()." and entryType = ".emuM_Bags::LOG_TYPE_MEASUREMENT." order by logTime desc limit 1";
        return $wpdb->get_var($sql);        
    }

    public function getLastEquilDuration()
    {
        global $emuTheme, $wpdb;
        $sql = "select duration from ".$emuTheme->dbPrefix."drying_logs where bagID = ".$this->getID()." and entryType = ".emuM_Bags::LOG_TYPE_EQUIL." order by logTime desc limit 1";
        return $wpdb->get_var($sql);
    }

    public function getLastDryDuration()
    {
        global $emuTheme, $wpdb;
        $sql = "select duration from ".$emuTheme->dbPrefix."drying_logs where bagID = ".$this->getID()." and entryType = ".emuM_Bags::LOG_TYPE_DRYING." order by logTime desc limit 1";
        return $wpdb->get_var($sql);
    }

    public function getDryingDuration()
    {
        global $wpdb, $emuTheme;

        $sql = "select sum(duration) from ".$emuTheme->dbPrefix."drying_logs where entryType = ".emuM_Bags::LOG_TYPE_DRYING." and bagID = ".$this->getID()." group by bagID";
        $duration = $wpdb->get_var($sql);

        return (int) $duration;
    }

    public function getEqDuration()
    {
        global $wpdb, $emuTheme;

        $sql = "select sum(duration) from ".$emuTheme->dbPrefix."drying_logs where entryType = ".emuM_Bags::LOG_TYPE_EQUIL." and bagID = ".$this->getID()." group by bagID";
        $duration = $wpdb->get_var($sql);

        return (int) $duration;
    }

    public function getStepTimeRemaining()
    {
      $unix_time = strtotime($this->stepStartTime);
      $step_end_time = $unix_time + $this->duration;
      $current_time = time();
      
      $remaining_time = $step_end_time - $current_time;
      return $remaining_time;
    }

    public function getStepTotalTime()
    {
      $unix_time = strtotime($this->stepStartTime);
      $current_time = time();
      
      $total_time = $current_time - $unix_time;
      return $total_time;
    }

    public function getDryingTimevsWP()
    {
        return $this->getPlottableHistory($header = false);
    }

    public function getPlottableHistory($header = true)
    {
        $drying_history = $this->getDryingHistory();

        $plots = array();

        foreach( $drying_history as $history )
        {
            $history = (object) $history;

            switch(true)
            {
              case ($history->wpInitial && (int) $history->wpFinal == 0 && (int) $history->dryingTotal > 0):
                // Drying for <?php echo $history->readable
              break;          
              case ($history->wpInitial && (int) $history->dryingTotal == 0):
                // &Psi; <?php echo $history->wpFinal after  echo $history->eQreadable equilibrating
              break;
              case ($history->wpInitial):
                $plots[] = array($history->dryingRunningTotal, $history->wpFinal);
              break;
              default:
                $plots[] = array(0, $history->wpFinal); 
                // &Psi; <?php echo $history->wpFinal (initial)
            }
        }

        if(count($plots) > 0 && $header)
            array_unshift($plots, array('Drying Time', 'WP'));        
        
        return $plots;

    }

    public function getLogEntries()
    {
        global $wpdb, $emuTheme;

        // Get the list of WP measurements
        $sql = "select dbID from ".$emuTheme->dbPrefix."drying_logs where bagID = ".$this->getID()." order by logTime asc";

        $entry_ids = $wpdb->get_col($sql);

        $entries = array();

        foreach($entry_ids as $id)
        {
            $entries[] = $emuTheme->bagsManager->getLogEntry($id);
        }

        return $entries;
    }


    public function getDryingTrend($threshold)
    {
        global $emuTheme;

      $plots = $this->getDryingTimevsWP();
      $result = array();

      // Calculate phases going forward and backwards
      $result["forwards"] = $emuTheme->getPhases($plots, $threshold);
      $result["backwards"] = $emuTheme->getPhases(array_reverse($plots), $threshold);

      return $result;
    }
    
    public function getDryingHistory()
    {
        global $wpdb, $emuTheme;

        // Add up periods of drying / equilibrating between water potential measurements
        $dry_history = array();
        $eq_history = array();
        $wp_pairs = array();
        $wp_pair = array();

        $log_entries = $this->getLogEntries();

        $drying_time_total = 0;
        $drying_running_total = 0;
        $eq_time_total = 0;
        $dry_history_index = 0;

        foreach($log_entries as $row)
        {
            switch( $row->entryType )
            {
                case emuM_Bags::LOG_TYPE_DRYING:
                    $drying_time_total += $row->duration;
                    $drying_running_total += $row->duration;
                break;
                case emuM_Bags::LOG_TYPE_EQUIL:
                    $eq_time_total += $row->duration;
                break;
                case emuM_Bags::LOG_TYPE_MEASUREMENT:
                    $wp_initial = count($dry_history) >= 1 ? $dry_history[$dry_history_index - 1]['wpFinal'] : '';
                    $dry_history[$dry_history_index] = array( 
                        'wpFinal' => $row->waterPotential, 
                        'wpInitial' => $wp_initial,
                        'dryingTotal' => $drying_time_total,
                        'dryingRunningTotal' => $drying_running_total,
                        'eqTotal' => $eq_time_total,
                        'readable' => 'about '.human_time_diff( time(), time() + $drying_time_total ),
                        'eQreadable' => 'about '.human_time_diff( time(), time() + $eq_time_total ),
                        'lossPerHour' => ($wp_initial && $drying_time_total) ? round((($row->waterPotential - $wp_initial) / $drying_time_total) * 60 * 60) : ''
                    );
                    $drying_time_total = 0;
                    $eq_time_total = 0;
                    $dry_history_index++;
                break;
            }
        }

        if($drying_time_total > 0) 
        {
            $dry_history[$dry_history_index] = array( 
                'wpFinal' => '', 
                'wpInitial' => $dry_history[$dry_history_index - 1]['wpFinal'],
                'dryingTotal' => $drying_time_total,
                'eqTotal' => $eq_time_total,
                'readable' => 'about '.human_time_diff( time(), time() + $drying_time_total ),
                'eQreadable' => 'about '.human_time_diff( time(), time() + $eq_time_total ),
                'lossPerHour' => ''
            );
        }

        return $dry_history;

    }

}
?>