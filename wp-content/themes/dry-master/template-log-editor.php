<?php
/*
Template Name: Log Editor
*/
global $emuTheme;

// Make sure they're logged in
$owner_id = $emuTheme->userAdminManager->checkAuth();

$owner = $emuTheme->userAdminManager->getOwner($owner_id);

$sample_groups = $owner->getSampleGroups();

if($bag_id = get_val('bag'))
{
  $defaultBag = $emuTheme->bagsManager->getBag($bag_id);
  $defaultGroup = $defaultBag->getSampleGroup();
  $default_bags = $defaultGroup->getBags();
}
else
{
  if($group_id = $emuTheme->getSessionData('group'))
    $defaultGroup = $emuTheme->bagsManager->getSampleGroup($group_id);
  else
    $defaultGroup = $sample_groups[0];
    
  $default_bags = $defaultGroup->getBags();
  $defaultBag = $default_bags[0];
}

?>

<?php get_header() ?>

    <?php echo $emuTheme->getView('log-editor-modal') ?>

    <div class="container" id="logEditor">

      <div class="row">
        <div class="col-sm-8 col-sm-offset-2">
          <div class="well well-sm">Choose the sample group and bag to edit. To edit an entry click on the entry row and update the details in the popup form.</div>
          <div class="form-group">
            <label>Sample Group</label>
            <select name="sample_group" class="sample-group form-control">
              <?php
              foreach( $sample_groups as $group )
                echo '<option value="'.$group->getID().'"'.($group->getID() == $defaultGroup->getID() ? ' selected="selected"' : '').'>'.$group->groupName.'</option>';
              ?>
            </select>
          </div>
          <div class="form-group">
            <label>View log for</label>
            <select name="group_bag" class="group-bag form-control">
              <?php
              foreach($default_bags as $bag)
                echo '<option value="'.$bag->getID().'"'.($bag->getID() == $defaultBag->getID() ? ' selected="selected"' : '').'>'.$bag->displayID.'</option>';
              ?>
            </select>
          </div>
          <p><input type="button" class="btn-view-log btn btn-primary" value="View" /></p>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-8 col-sm-offset-2">
          <?php echo $emuTheme->getView('drying-log', array('bag' => $defaultBag) ) ?>
        </div>
      </div>
    </div>

<?php get_footer() ?>