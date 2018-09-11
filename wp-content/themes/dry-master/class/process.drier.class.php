<?php

class emuP_Drier extends emuProcessor
{
    public $bag;
    public $responseBody; 
    public $method;

    public function process()
    {
        $this->error = false; // we'll be optimistic

        $this->bag = $this->emuApp->getModel('bag', post_val('bagID'));
        $this->method = post_val('method');

        $this->validateRequest();

        switch($this->method)
        {
            case 'create-bag':
                $response_body = $this->createBag();
                break;
            case 'update-step-time':
                $response_body = $this->updateStepTime();
                break;
            case 'delete-bag':
                $response_body = $this->deleteBag();
                break;
            case 'save-bag':
                $response_body = $this->saveBag();
                break;
            case 'plot-bag':
                $response_body = $this->plotBag();
                break;
            case 'plot-group':
                $response_body = $this->plotGroup();
                break;
            case 'group-table':
                $response_body = $this->plotGroupTable();
                break;
            case 'start-drying':
                $response_body = $this->startDrying();
                break;
            case 'view-log':
                $response_body = $this->viewLog();
                break;
            case 'stop-drying':
                $response_body = $this->stopDrying();
                break;
            case 'start-eq':
                $response_body = $this->startEq();
                break;
            case 'stop-eq':
                $response_body = $this->stopEq();
                break;
            case 'save-wp':
                $response_body = $this->saveWP();
                break;
            case 'finish-bag':
                $response_body = $this->finishBag();
                break;
        }
        
        $this->doResponse( ($this->error ? 'error' : 'OK'), $response_body );
    }

    public function validateRequest()
    {
        switch($this->method)
        {
            case 'start-drying':
                $request_current_step = emuM_Bags::STEP_DRYING_SETUP;
                break;
            case 'stop-drying':
                $request_current_step = emuM_Bags::STEP_DRYING;
                break;
            case 'start-eq':
                $request_current_step = emuM_Bags::STEP_DRYING_COMPLETE;
                break;
            case 'stop-eq':
                $request_current_step = emuM_Bags::STEP_EQUILIBRATING;
                break;
            case 'save-wp':
                $request_current_step = emuM_Bags::STEP_EQUILIBRATED;
                break;
            default:
                return;
        }

        // Check that the bag is actually on that step...
        if( (int)$this->bag->currentStep !== (int)$request_current_step)
        {
            // if not then just return the current step
            $this->doResponse('OK', $this->loadStep($bag->currentStep));
        }
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

    private function startDrying()
    {
        $this->bag->startStep(emuM_Bags::STEP_DRYING, post_val('duration'));
        $this->bag->save();

        return $this->loadStep(emuM_Bags::STEP_DRYING);
    }

    private function viewLog()
    {
        return array( 
            'log' => $this->emuApp->getView('drying-log', array('bag' => $this->bag)) 
        );
    }
    
    private function plotBag()
    {
        $this->phases = $this->bag->getDryingPhases();    

        $phases_points = array();

        $this->phases = array_reverse($this->phases);

        $n_phases = count($this->phases);

        for($phase_n = 0, $n_phases = count($this->phases); $phase_n < $n_phases; $phase_n++)
        {
            $phase = $this->phases[$phase_n];

            $points = array();
            
            $phase = (object) $phase;

            foreach($phase->members as $member)
            {
               // $plot_points[] = array(round(($member->x / 60 / 60),2), round($member->y), $member->excluded ? '#f4f4f4' : $phase_color);
               $points[] = array(round(($member->x / 60 / 60), 2), round($member->y));
            }

            $phase_color = $this->getPhaseColor($phase->number);

            if($phase_n == ($n_phases - 1) && $phase->regression)
                $regression = $phase->regression["best"];
            else
                $regression = null;

            $phases_points[] = (object) array( 
                "points" => $points, 
                "color" => $this->getPhaseColor($phase->number),
                "regression" => $regression 
            );
        }

        // Add a current / predicted point if we can...
        switch($this->bag->currentStep)
        {
            case emuM_Bags::STEP_DRYING:
            case emuM_Bags::STEP_DRYING_COMPLETE:
            
                // Current
                $plot_points[] = array(round((($this->bag->getDryingDuration() + $this->bag->getStepTotalTime()) / 60 / 60),2), round($this->bag->getWPEstimate()), 'color: '.$phase_color.';fill-color: none;stroke-opacity:0.6');
                
                // Final
                $plot_points[] = array(round((($this->bag->getDryingDuration() + $this->bag->duration) / 60 / 60),2), round($this->bag->getWPEstimate($final = true)), 'color: '.$phase_color.';fill-opacity: 0.3;stroke-opacity:0.1');

            break;
            case emuM_Bags::STEP_EQUILIBRATING:
            case emuM_Bags::STEP_EQUILIBRATED:
                // Final
                $plot_points[] = array(round((($this->bag->getDryingDuration() + $this->bag->duration) / 60 / 60),2), round($this->bag->getWPEstimate($final = true)), 'color: '.$phase_color.';fill-opacity: 0.3;stroke-opacity:0.1');

            break;

        }

        return array( 
            'phases' => $phases_points
        );
    }

    public function getPhaseColor($number)
    {
      switch((int)$number)
      {
        case 0: return 'red';
        case 1: return 'blue';
        case 2: return 'magenta';
        case 3: return 'orange';
      }
      return '#ccc';
    }

    private function plotGroupTable()
    {
        $group = $this->emuApp->getModel('sampleGroup', post_val('sampleGroupID'));

        // Get all the bags
        $bags = $group->getBags();

        $rows = array();

        foreach($bags as $bag)
        {
            $current = $bag->getWPEstimate();
            $final = $bag->getWPEstimate($final = true);
            $rows[] = array(
                "display_id" => $bag->displayID, 
                "current" => $current ? $current : 0, 
                "final" => $final ? $final : 0, 
                "step_desc" => $bag->getStepDescription(), 
                "step_color" => $bag->getStepColor()
            );
        }

        $table = '<table class="table">';
        $table .= '<thead><tr><th>Bag ID</th><th>Step</th><th>Current &Psi;</th><th>Final &Psi;</th></tr></thead>';
        $table .= '<tbody>';
        foreach($rows as $row)
        {
            $table .= '<tr>';
            $table .= '<td style="color:'.$row["step_color"].'">'.$row["display_id"].'</td>';
            $table .= '<td style="color:'.$row["step_color"].'">'.$row["step_desc"].'</td>';
            $table .= '<td style="color:'.$row["step_color"].'">'.round($row["current"]).'</td>';
            $table .= '<td style="color:'.$row["step_color"].'">'.round($row["final"]).'</td>';
            $table .= '</tr>';
        }
        $table .= '</tbody>';
        $table .= '</table>';

        return array( 
            'table' => $table
        );        
    }

    private function plotGroup()
    {
        $group = $this->emuApp->getModel('sampleGroup', post_val('sampleGroupID'));

        // Get all the bags
        $bags = $group->getBags();

        $plots = array(
            array(
                'Bag', 
                'Current', 
                (object) array('role' => 'annotation'), 
                (object) array('role' => 'style'), 
                'Final', 
                (object) array('role' => 'annotation'), 
                (object) array('role' => 'style')
            )
        );

        foreach($bags as $bag)
        {
            // $current = $bag->getWPEstimate();
            $current = $bag->getLastKnownWP();
            $final = $bag->getWPEstimate($final = true);
            $plots[] = array(
                $bag->displayID, 
                $current ? $current : 0, 
                '',
                '#d0dcd4',
                $final ? $final : 0, 
                $bag->getStepDescription(), 
                $bag->getStepColor()
            );
        }

        return array( 
            'points' => $plots
        );
    }
    
    private function stopDrying()
    {
        $this->bag->startStep(emuM_Bags::STEP_DRYING_COMPLETE);
        $this->bag->save();

        $this->bag->addDryingTime($this->bag->durationLastStep);
        
        return $this->loadStep(emuM_Bags::STEP_DRYING_COMPLETE);
    }
    
    private function finishBag()
    {
        $this->bag->startStep(emuM_Bags::STEP_BAG_FINISHED);
        $this->bag->save();

        return $this->loadStep(emuM_Bags::STEP_BAG_FINISHED);
    }
    
    private function stopEq()
    {
        $this->bag->startStep(emuM_Bags::STEP_EQUILIBRATED);
        $this->bag->save();

        $this->bag->addEqTime($this->bag->durationLastStep);

        return $this->loadStep(emuM_Bags::STEP_EQUILIBRATED);
    }
        
    private function saveWP()
    {
        $this->bag->startStep(emuM_Bags::STEP_DRYING_SETUP);
        $this->bag->save();
        
        $this->bag->addEqTime($this->bag->durationLastStep);
        $this->bag->addMeasurement(post_val('wp'));

        return $this->loadStep(emuM_Bags::STEP_DRYING_SETUP);
    }
    
    private function startEq()
    {
        $this->bag->startStep(emuM_Bags::STEP_EQUILIBRATING, post_val('eqDuration'));
        $this->bag->save();

        $this->bag->addDryingTime($this->bag->durationLastStep);
        
        return $this->loadStep(emuM_Bags::STEP_EQUILIBRATING);
    }
    
    public function loadStep($step)
    {
        return array( 
            'step' => $step, 
            'panel' => $this->emuApp->getView('steps', array('step' => $step, 'bag' => $this->bag))
        );
    }

    private function createBag()
    {
        $this->bag->displayID = post_val('displayBagID');
        $this->bag->sampleGroupID = post_val('sampleGroupID');
        
        if(post_val('startEq') == 'true')
        {
            $this->bag->startStep(emuM_Bags::STEP_EQUILIBRATING, post_val('eqDuration'));
            $step = emuM_Bags::STEP_EQUILIBRATING;
        }
        else
        {
            $this->bag->startStep(emuM_Bags::STEP_DRYING_SETUP);
            $this->bag->currentStep = emuM_Bags::STEP_DRYING_SETUP;
            $step = emuM_Bags::STEP_DRYING_SETUP;
        }
        
        $this->bag->save();

        if(strlen(trim(post_val('initialWP'))) > 0)
            $this->bag->addMeasurement(post_val('initialWP'));

        return array( 
            'step' => $step, 
            'bagID' => $this->bag->getID(),
            'panel' => $this->emuApp->getView('steps', array('step' => $step, 'bag' => $this->bag))
        );
    }
    
    private function deleteBag()
    {
        $this->bag->delete();
        return array();
    }
    
    private function saveBag()
    {
        $this->bag->displayID = post_val('displayBagID');
        $this->bag->save();
        
        return array();
    }
    
    private function updateStepTime()
    {
        $this->bag->duration = post_val('duration');
        $this->bag->update();
        
        return array();
    }
}

?>