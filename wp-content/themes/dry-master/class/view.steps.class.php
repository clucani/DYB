<?php
class emuV_Steps extends emuView
{
    public $bag;
    public $dryingHistory;

    public function build()
    {
        $this->bag = $this->vars['bag'];

        $panel_type = 'panel-default';

        switch($this->vars['step'])
        {
            case emuM_Bags::STEP_NEW_BAG:
                $step = 'stepNewBag';
                $header = 'New Bag';
            break;
            case emuM_Bags::STEP_DRYING_SETUP:
                $step = 'stepDryingSetup';
                $header = 'Setup Drying';
            break;
            case emuM_Bags::STEP_DRYING:
                $step = 'stepDrying';
                $header = 'Drying';
            break;
            case emuM_Bags::STEP_DRYING_COMPLETE:
                $step = 'stepDryingComplete';
                $header = 'Drying Complete';
                $panel_type = 'panel-danger';
            break;
            case emuM_Bags::STEP_EQUILIBRATING:
                $step = 'stepEquilibrating';
                $header = 'Equilibrating';
            break;
            case emuM_Bags::STEP_EQUILIBRATED:
                $step = 'stepEquilibrated';
                $header = 'Equilibrated';
                $panel_type = 'panel-info';
            break;
            case emuM_Bags::STEP_BAG_FINISHED:
                $step = 'stepBagFinished';
                $header = 'Finished';
            break;
        }

        ?>
        <div class="panel <?php echo $panel_type?>">
          <div class="panel-heading">
            <button type="button" class="close pull-right" aria-hidden="true">&times;</button>
            <strong class="display-id"><?php echo $this->bag->displayID?></strong>&nbsp;
            <?php echo $header?>
            <div class="update-id hidden"></div>
          </div>
          <?php 
          
          $this->$step();
          
          if((int)$this->vars['step'] !== emuM_Bags::STEP_NEW_BAG ) { 
            ?>
            <div class="panel-footer clearfix"><?php $this->bagActions(); ?></div>
            <?php
            
            $this->dryingHistory = array_reverse($this->bag->getDryingHistory());
            
            $this->plotPanel();
            $this->historyPanel();
          } 
          ?>
        </div>
        <?php
    }

    public function bagActions()
    {
      ?>
      <!-- <div class="panel-body actions-panel clearfix"> -->
        <?php if((int)$this->vars['step'] !== emuM_Bags::STEP_BAG_FINISHED ) { ?>
        <a class="btn-finish btn btn-default btn-sm pull-right" href="#">Finish</a>
        <a class="btn-show-plot btn btn-default btn-sm pull-right" href="#"><span class="glyphicon glyphicon-stats"></span></a>
        <?php } ?>
        <a class="show-dry-history btn btn-default btn-sm pull-right" href="#"><span class="glyphicon glyphicon-th-list"></span></a>
      <!-- </div> -->
      <?php
    }

    public function plotPanel()
    {
      ?>
      <div class="panel-body hidden plot-panel">
        <div class="bag-plot-wrapper"><div class="bag-plot"></div></div>
        <a class="" href="/?e-plugin=emuTheme&e-action=analyze-phases&bag=<?php echo $this->bag->getID()?>" target="_blank">Analyze Phases</a>
      </div>
      <?php
    }


    public function historyPanel()
    {
      ?>
      <div class="hidden history-panel">
        <table class="table table-condensed">
        <tbody>
        <?php

        foreach( $this->dryingHistory as $history )
        {
          $history = (object) $history;

          switch(true)
          {
            case ($history->wpInitial && (int) $history->wpFinal == 0 && (int) $history->dryingTotal > 0):
              ?>
              <tr><td>Drying for <?php echo $history->readable?></td></tr>
              <?php
            break;          
            case ($history->wpInitial && (int) $history->dryingTotal == 0):
              ?>
              <tr><td>&Psi; <?php echo $history->wpFinal?> after <?php echo $history->eQreadable ?> equilibrating</td></tr>
              <?php
            break;
            case ($history->wpInitial):
              ?>
              <tr><td>&Psi; <?php echo $history->wpFinal?>, <?php echo $history->readable ?> drying, <?php echo $history->lossPerHour?>/hour</td></tr>
              <?php
            break;
            default:
              ?>
              <tr><td>&Psi; <?php echo $history->wpFinal?> (initial)</td></tr>
              <?php
          }
        }
        ?>
        </tbody>
        <tfoot>
          <tr><td>
          <a class="" href="/log-editor/?bag=<?php echo $this->bag->getID()?>">Edit Log</a>
          </td></tr>
        </table>
      </div>
      <?php
    }


    public function stepNewBag()
    {
      ?>    
      <div class="panel-body">
        <div class="form-group">
          <label for="bag_id">Bag ID</label>
          <input type="text" class="form-control display-bag-id" value="" placeholder="e.g. B1" />
        </div>
        <div class="form-group">
          <label for="bag_id">Initial &Psi;</label>
          <input type="text" class="form-control initial-wp" value="" />
        </div>
        <div class="form-group">
          <button type="button" class="btn btn-primary btn-setup-drying">Setup Drying</button>
        </div>
        <div class="form-inline">
          <button type="button" class="btn btn-default btn-start-eq">Start Eq.</button>
          <select class="form-control eq-time">
            <option value="300">5 min</option>
            <option value="600">10 min</option>
            <option value="900" selected="selected">15 min</option>
            <option value="1200">20 min</option>
          </select>
        </div>
      </div>
      <?php
    }

    public function stepDryingSetup()
    {
        $phase = $this->bag->getCurrentPhaseModel();
        ?>
        <div class="panel-body timer">
          <div class="clock">00:00:00</div>      
          <div class="text-center wp-estimate"><?php echo $phase->slope ? '<small>Est. &Psi; '.$this->bag->getLastKnownWP().'</small>' : ''?></div>
          <div class="row timer-buttons">
            <div class="col-md-12 text-center top">
              <button type="button" type="button" class="btn btn-primary btn-sm btn-plus-one-hour">+1 hour</button>
              <button type="button" type="button" class="btn btn-primary btn-sm btn-plus-thirty-min">+30 min</button>
              <button type="button" type="button" class="btn btn-primary btn-sm btn-plus-ten-min">+10 min</button>
            </div>
            <div class="col-md-12 text-center">
              <button type="button" type="button" class="btn btn-success btn-sm btn-minus-one-hour">-1 hour</button>
              <button type="button" type="button" class="btn btn-success btn-sm btn-minus-thirty-min">-30 min</button>
              <button type="button" type="button" class="btn btn-success btn-sm btn-minus-ten-min">-10 min</button>
            </div>
          </div>
          <hr />
          <div class="text-center">
            <button type="button" type="button" class="btn btn-primary btn-start-drying">Start</button>
          </div>
          <input type="hidden" name="slope" class="slope" value="<?php echo $phase->slope?>" />
          <input type="hidden" name="intercept" class="intercept" value="<?php echo $phase->intercept?>" />
          <input type="hidden" name="regression" class="regression" value='<?php echo json_encode($phase->regression)?>' />
          <input type="hidden" name="total-drying-time" class="total-drying-time" value="<?php echo $this->bag->getDryingDuration()?>" />
        </div>
        <?php
    }

    public function stepBagFinished()
    {
        ?>
        <?php
    }

    public function stepDrying()
    {
        $phase = $this->bag->getCurrentPhaseModel();

        ?>
        <div class="panel-body timer">
          <div class="text-center"><small>Drying complete in...</small></div>
          <div class="clock"><?php echo gmdate("H:i:s", $this->bag->getStepTimeRemaining())?></div>      
          <div class="text-center"><small>Total Drying Time <span class="total-drying-time-container">00:00:00</span></small></div>
          <div class="text-center wp-estimate"></div>
          <div class="row timer-buttons">
            <div class="col-md-12 text-center top">
              <button type="button" type="button" class="btn btn-default btn-sm btn-plus-one-hour">+1 hour</button>
              <button type="button" type="button" class="btn btn-default btn-sm btn-plus-thirty-min">+30 min</button>
              <button type="button" type="button" class="btn btn-default btn-sm btn-plus-ten-min">+10 min</button>
            </div>
            <div class="col-md-12 text-center">
              <button type="button" type="button" class="btn btn-default btn-sm btn-minus-one-hour">-1 hour</button>
              <button type="button" type="button" class="btn btn-default btn-sm btn-minus-thirty-min">-30 min</button>
              <button type="button" type="button" class="btn btn-default btn-sm btn-minus-ten-min">-10 min</button>
            </div>
          </div>
          <hr />
          <div class="text-center">
            <button type="button" type="button" class="btn btn-default btn-stop-drying">Stop</button>
          </div>
          <input type="hidden" name="regression" class="regression" value='<?php echo json_encode($phase->regression)?>' />
          <input type="hidden" name="slope" class="slope" value="<?php echo $phase->slope?>" />
          <input type="hidden" name="intercept" class="intercept" value="<?php echo $phase->intercept?>" />
          <input type="hidden" name="total-drying-time" class="total-drying-time" value="<?php echo $this->bag->getDryingDuration()?>" />
          <input type="hidden" name="stepStartTime" class="step-start-time" value="<?php echo $this->bag->stepStartTime?>" />
          <input type="hidden" name="stepDuration" class="step-duration" value="<?php echo $this->bag->duration?>" />
        </div>
        <?php
    }

    public function stepDryingComplete()
    {
        ?>
        <div class="panel-body timer">
          <div class="text-center"><small>Drying Time</small></div>
          <div class="clock"><?php echo gmdate("H:i:s", $this->bag->getLastDryDuration())?></div>
          <div class="extra-time text-center">+<?php echo gmdate("H:i:s", $this->bag->getStepTotalTime() - $this->bag->getLastDryDuration())?></div>      
          <hr />
          <div class="form-group">
            <label>Equilibrate for...</label>
            <div class="form-inline">
              <select class="form-control eq-time">
                <option value="300">5 min</option>
                <option value="600">10 min</option>
                <option value="900" selected="selected">15 min</option>
                <option value="1200">20 min</option>
              </select>
              <button type="button" type="button" class="btn btn-primary btn-start-eq">Start</button>
            </div>
          </div>
          <input type="hidden" name="stepStartTime" class="step-start-time" value="<?php echo $this->bag->stepStartTime?>" />
          <input type="hidden" name="stepDuration" class="step-duration" value="<?php echo $this->bag->getLastDryDuration()?>" />
        </div>
        <?php
    }

    public function stepEquilibrating()
    {
        ?>
        <div class="panel-body timer">
          <div class="text-center"><small>Time Remaining</small></div>
          <div class="clock"><?php echo gmdate("H:i:s", $this->bag->getStepTimeRemaining())?></div>      
          <div class="text-center"><small>Total Eq. Time <span class="total-eq-time"><?php echo gmdate("H:i:s", $this->bag->getStepTotalTime())?></span></small></div>
          <hr />
          <div class="text-center">
            <button type="button" type="button" class="btn btn-default btn-stop-eq">Stop</button>
          </div>
          <input type="hidden" name="stepStartTime" class="step-start-time" value="<?php echo $this->bag->stepStartTime?>" />
          <input type="hidden" name="stepDuration" class="step-duration" value="<?php echo $this->bag->duration?>" />
        </div>
        <?php
    }

    public function stepEquilibrated()
    {
        ?>
        <div class="panel-body timer">
          <div class="text-center"><small>Time Equilibrating</small></div>
          <div class="clock"><?php echo gmdate("H:i:s", $this->bag->getLastEquilDuration())?></div>
          <div class="extra-time text-center">+<?php echo gmdate("H:i:s", $this->bag->getStepTotalTime())?></div>      
          <?php
          // Can we estimate the WP?
          // See if we have one in the history
          if(isset($this->dryingHistory[1]['lossPerHour']) && $this->dryingHistory[1]['lossPerHour'])
          {
            echo '<div class="text-center"><small>After '.$this->dryingHistory[0]['readable'].' drying (est. &Psi; '.round($this->bag->getWPEstimate()).')</small></div>';
          }

          ?>
          <hr />
          <div class="form-group">
            <label>Measured &Psi;</label>
            <input type="text" class="form-control wp" value="" />
          </div>
          <div class="text-center">
            <button type="button" type="button" class="btn btn-primary btn-save-wp">Save</button>
          </div>
          <input type="hidden" name="stepStartTime" class="step-start-time" value="<?php echo $this->bag->stepStartTime?>" />
          <input type="hidden" name="stepDuration" class="step-duration" value="<?php echo $this->bag->getStepTotalTime()?>" />
        </div>
        <?php
    }
}
