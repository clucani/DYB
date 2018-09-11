<?php
/*
Template Name: Sample Groups
*/

global $emuTheme;

// Make sure they're logged in
$owner_id = $emuTheme->userAdminManager->checkAuth();

$owner = $emuTheme->userAdminManager->getOwner($owner_id);

$sample_groups = $owner->getSampleGroups();

$session_sample_group = $emuTheme->getSessionData('group');

?>

<?php get_header() ?>

      <div class="row">
        <div class="col-md-4 col-md-offset-4">
          <h4>Sample Groups</h4>

          <?php echo $emuTheme->getMessages('sample-groups') ?>

          <form method="post" action="" role="form">

            <div class="panel panel-default">
              <div class="panel-heading">New Sample Group</div>
              <div class="panel-body">
                <div class="form-group">
                  <label class="">Group Name</label>
                  <input type="text" class="form-control" name="group_name" value="" />
                </div>
                <input type="submit" class="btn btn-default" name="e-button" value="Create" />
              </div>
            </div>
            
            <div class="form-group">
              <label class="">Sample Group</label>
              <select name="sample_group" class="form-control">
                <option value="">Select a sample...</option>
                <?php 
                foreach($sample_groups as $group)
                  echo '<option value="'.$group->getID().'"'.($group->getID() == $session_sample_group ? ' selected="selected"' : '').'>'.$group->groupName.'</option>';
                ?>
              </select>
            </div>
          
            <div class="form-group">
              <input type="submit" name="e-button" class="btn btn-primary pull-right" value="View" />
              <input type="submit" name="e-button" class="btn btn-warning" value="Delete" onClick="return confirm('Are you sure');" />
            </div>

            <?php $emuTheme->bindProcessor('sample-groups') ?>
          
          </form>

        </div>
      </div>

    </div>

<?php get_footer() ?>