<?php
/*
Template Name: Bags
*/

global $emuTheme;

// Make sure they're logged in
$owner_id = $emuTheme->userAdminManager->checkAuth();

$owner = $emuTheme->userAdminManager->getOwner($owner_id);

$group_id = get_val('group');

if( !$group_id ) 
{
  if(!$group_id = $emuTheme->getSessionData('group'))
  {
    header('Location: /');
    exit();
  }
}

$emuTheme->setSessionData('group', $group_id);

global $sampleGroup;

$sampleGroup = $emuTheme->getModel('sampleGroup', $group_id);

if($sampleGroup->ownerID <> $owner->getID())
  exit('foobar!');

$bags = $sampleGroup->getBags();

?>

<?php get_header() ?>

      <div class="modal fade" id="groupSummaryModal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title">Group Summary</h4>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="dry-plot-wrapper">
                  <div class="dry-plot" id="dryPlotter"></div>
                </div>
                <div class="table-wrapper">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-show-table-view pull-left">Table View</button>
              <button type="button" class="btn btn-default btn-show-graph-view pull-left hidden">Graph View</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
          </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
      </div><!-- /.modal -->      
      
      <div class="row" style="margin-bottom: 20px">
        <div class="col-md-12">
          <a class="btn-show-group-summary btn btn-default btn-md pull-right" href="#"><span class="glyphicon glyphicon-stats"></span>&nbsp;&nbsp;Summary</a>
        </div>
      </div>

      <div class="row" id="drier">
        <?php
        foreach( $bags as $bag )
        {
          // pre($bag->getCurrentPhaseModel());
          ?>
          <div class="col-md-4 col-sm-6 bag">
            <?php echo $emuTheme->getView('steps', array('step' => $bag->currentStep, 'bag' => $bag ))?>
            <input type="hidden" name="bag_id" class="bag-id" value="<?php echo $bag->getID()?>" />
            <input type="hidden" name="step" class="step" value="<?php echo $bag->currentStep?>" />
          </div>
          <?php
        }
        ?>

        <div class="col-md-4 col-sm-6 hidden new-bag-template">
          <?php echo $emuTheme->getView('steps', array('step' => emuM_Bags::STEP_NEW_BAG, 'bag' => $emuTheme->getModel('bag'))) ?>
          <input type="hidden" name="bag_id" class="bag-id" value="" />
          <input type="hidden" name="step" class="step" value="<?php echo emuM_Bags::STEP_NEW_BAG?>" />
        </div>
        
        <div class="col-md-4 col-sm-6 new-bag-button">
          <div class="panel panel-default text-center">
            <div class="button">
              <button type="button" class="btn btn-default" id="btnNewBag">New Bag</button>
            </div>
          </div>
        </div>
        
        <input type="hidden" name="sample_group" id="sampleGroup" value="<?php echo get_val('group')?>" />
      
      </div>
      

    
<?php get_footer() ?>