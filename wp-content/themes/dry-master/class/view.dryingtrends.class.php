<?php
class emuV_DryingTrends extends emuView
{
    public $bag;
    public $threshold;
    public $visCounter = 1;

    public function build()
    {
      require_once $this->emuApp->pluginPath.'/lib/PHPExcel/Classes/PHPExcel.php';

      $this->threshold = post_val('threshold', 2);

      ?>
      <form method="post" action="" class="form-horizontal panel panel-default">
        <div class="panel-body">
          <div class="form-group">
            <label class="col-sm-2 control-label">Threshold</label>
            <div class="col-sm-8">
              <input type="text" class="form-control" id="threshold" name="threshold" value="<?php echo $this->threshold?>">
            </div>
            <div class="col-sm-2">
              <button type="submit" class="btn btn-default">Update</button>
            </div>
          </div>
        </div>
      </form>
      <?php
      $bagsManager = $this->emuApp->bagsManager;

      // Make sure they're logged in
      $owner_id = $this->emuApp->userAdminManager->checkAuth();

      $owner = $this->emuApp->userAdminManager->getOwner($owner_id);

      $sample_groups = $owner->getSampleGroups();

      foreach($sample_groups as $sampleGroup)
      {
        echo "<h3>".$sampleGroup->groupName."</h3>";

        foreach( $sampleGroup->getBags() as $bag )
        {
          ?>
          <div class="panel panel-default">
            <div class="panel-body">
              <?php $this->showBagDryingTrend($bag) ?>
            </div>
          </div>
          <?php
        }
      }
    }

    public function showBagDryingTrend($bag)
    {
      $phases = $bag->getDryingTrend($this->threshold);

      ?>
      <h4>Bag <?php echo $bag->displayID?></h4>
      <h5>Forward</h5>
      <?php $this->buildPhases($phases["forwards"], $bag); ?>
      <h5>Backwards</h5>
      <?php $this->buildPhases($phases["backwards"], $bag); ?>
    <?php
    }

    public function buildPhases($phases, $bag)
    {
      ?>
      <table class="table">
        <tr>
          <td>x</td>
          <td>y</td>
          <td>slope</td>
          <td>intercept</td>
          <td>steyx</td>
          <td>phase</td>
          <td>diff. prev.</td>
        </tr>
        <?php
        foreach($phases as $phase)
        {
          $phase = (object) $phase;
          $phase_color = $this->getPhaseColor($phase->number);
      
          foreach($phase->members as $member)
          {
            ?>
            <tr>
              <td style="color: <?php echo $phase_color?>"><?php echo $member->x?></td>
              <td style="color: <?php echo $phase_color?>"><?php echo $member->y?></td>
              <td style="color: <?php echo $phase_color?>"><?php echo round($member->slope, 2)?></td>
              <td style="color: <?php echo $phase_color?>"><?php echo round($member->intercept, 2)?></td>
              <td style="color: <?php echo $phase_color?>"><?php echo round($member->steyx, 2)?></td>
              <td style="color: <?php echo $phase_color?>"><?php echo $phase->number?></td>
              <td style="color: <?php echo $phase_color?>"><?php echo round($member->steyxDiff, 2)?></td>
            </tr>
            <?php
          }
        }
        ?>
      </table>

      <table class='table'>
        <tr>
          <td>Phase</td>
          <td>Eq.</td>
          <td>Slope</td>
          <td>Intercept</td>
        </tr>
        <?php
        foreach($phases as $phase)
        {
          $phase = (object) $phase;
          $phase_color = $this->getPhaseColor($phase->number);
          
          echo "<tr>";
          echo "<td style='color:".$phase_color."'>".$phase->number."</td>";
          echo "<td style='color:".$phase_color."'>y = ".$phase->slope."x + ".$phase->intercept."</td>";
          echo "<td style='color:".$phase_color."'>".$phase->slope."</td>";
          echo "<td style='color:".$phase_color."'>".$phase->intercept."</td>";
          echo "</tr>";
        }
        ?>
      </table>
      <?php

      if(count($phases) >= 2)
      {
        $last_index = count($phases)-1;

        $x = ($phases[$last_index]["intercept"] - $phases[$last_index-1]["intercept"]) / ($phases[$last_index-1]["slope"] - $phases[$last_index]["slope"]); 
        $TLP = $phases[$last_index]["slope"] * $x + $phases[$last_index]["intercept"];

        echo "<p>Estimated TLP: ".round($TLP,3);
      }

      ?>
      <div id="chart_div_<?php echo $this->visCounter?>" style="width: 100%; height: 300px;"></div>
      <script type="text/javascript">
      function drawVisualization<?php echo $this->visCounter?>() {
        
        var data = google.visualization.arrayToDataTable([
          ['Drying Time', 'WP', { role: 'style' }],
          <?php
          foreach($phases as $phase)
          {
            $phase = (object) $phase;
            $phase_color = $this->getPhaseColor($phase->number);

            foreach($phase->members as $member)
            {
              ?>
              [ <?php echo $member->x?>, <?php echo $member->y?>, '<?php echo $phase_color?>'],
              <?php            
            }
          }
          ?>
        ]);

        var options = {
          title: '<?php echo $bag->displayID?>',
          hAxis: {title: 'Drying Time', minValue: 0},
          vAxis: {title: 'WP', minValue: 0},
          legend: 'none'
        };

        var chart = new google.visualization.ScatterChart(document.getElementById('chart_div_<?php echo $this->visCounter?>'));
        chart.draw(data, options);
      }

      google.setOnLoadCallback(drawVisualization<?php echo $this->visCounter?>);
      
      </script>

      <?php
      $this->visCounter++;
    }

    public function getPhaseColor($number)
    {
      switch((int)$number)
      {
        case 0: return 'red';
        case 1: return 'blue';
        case 2: return 'green';
        case 3: return 'magenta';
      }
      return '#ccc';
    }

    public function build_old()
    {
        global $emuTheme;

        ?>

        <form method="post" action="" role="form">
          
          <table class="table">
            <tr>
              <th>Group</th>
              <th>Bag</th>
              <th>Drying Running</th>
              <th>Drying</th>
              <th>&Psi; Initial</th>
              <th>&Psi; Final</th>
              <th>&Psi;/hour</th>
            </tr>

            <?php
            
            $sample_groups = $emuTheme->bagsManager->getSampleGroups();

            foreach( $sample_groups as $group )
            {
              $group_bags = $group->getBags();

              foreach( $group_bags as $bag )
              {
                $drying_history = $bag->getDryingHistory();

                foreach( $drying_history as $history )
                {
                ?>
                  <?php
                  $history = (object) $history;

                  switch(true)
                  {
                    case ($history->wpInitial && (int) $history->wpFinal == 0 && (int) $history->dryingTotal > 0):
                      // Drying for <?php echo $history->readable
                      ?>
                      <?php
                    break;          
                    case ($history->wpInitial && (int) $history->dryingTotal == 0):
                      // &Psi; <?php echo $history->wpFinal after  echo $history->eQreadable equilibrating
                      ?>
                      <?php
                    break;
                    case ($history->wpInitial):
                      ?>
                      <tr>
                        <td><?php echo $group->groupName?></td>
                        <td><?php echo $bag->displayID?></td>
                        <td><?php echo round($history->dryingRunningTotal / 60)?></td>
                        <td><?php echo round($history->dryingTotal / 60)?></td>
                        <td><?php echo $history->wpInitial?></td>
                        <td><?php echo $history->wpFinal?></td>
                        <td><?php echo $history->lossPerHour?></td>
                      </tr>
                      <?php
                    break;
                    default:
                      // &Psi; <?php echo $history->wpFinal (initial)
                      ?>
                      <?php
                  }
                }
              }
            }
            ?>
          </table>
        </form>
        <?php
    }
}
