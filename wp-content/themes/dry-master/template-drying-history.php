<?php
/*
Template Name: Drying History
*/

global $emuTheme;

// Make sure they're logged in
$owner_id = $emuTheme->userAdminManager->checkAuth();

$owner = $emuTheme->userAdminManager->getOwner($owner_id);

$sample_groups = $owner->getSampleGroups();

?>

<?php get_header() ?>

      <div class="row">
        <div class="col-md-4 col-md-offset-4">
          
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

        </div>
      </div>

    </div>

<?php get_footer() ?>
