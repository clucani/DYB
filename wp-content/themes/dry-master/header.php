<?php

global $emuTheme;

if($group_id = $emuTheme->getSessionData('group'))
  $sampleGroup = $emuTheme->bagsManager->getSampleGroup($group_id);


if($emuTheme->userAdminManager->isUserLoggedIn())
{
  $owner_id = $emuTheme->userAdminManager->checkAuth();

  $owner = $emuTheme->userAdminManager->getOwner($owner_id);

  $sample_groups = $owner->getSampleGroups();
}  


?>
<!DOCTYPE html>
<html>
  <head>
    <title>DYB</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      // Load the Visualization API and the piechart package.
      google.load('visualization', '1.0', {'packages':['corechart']});
    </script>
    <?php wp_head(); ?>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

  </head>
  <body>

    <div class="navbar navbar-default navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <?php          
          if(isset($sampleGroup))
          {
            // echo '<a href="bags/?group='.$sampleGroup->getID().'" class="navbar-brand">'.$sampleGroup->groupName.'</a>';
          }
          ?>
          <?php if($emuTheme->userAdminManager->isUserLoggedIn()) { ?>
            <form class="navbar-form navbar-brand" method="post" action="" role="form" id="sampleGroupForm">
              <div class="form-group">
                <select name="sample_group" id="sampleGroup" class="form-control">
                  <option value="">Select a sample...</option>
                  <?php 
                  foreach($sample_groups as $group)
                    echo '<option value="'.$group->getID().'"'.($group->getID() == $group_id ? ' selected="selected"' : '').'>'.$group->groupName.'</option>';
                  ?>
                </select>
              </div>
              <input type="hidden" name="e-button" value="View" />
              <?php $emuTheme->bindProcessor('sample-groups') ?>
            </form>
          <?php } ?>
        </div>
        <div class="collapse navbar-collapse">
          <div class="">
            <?php if($emuTheme->userAdminManager->isUserLoggedIn()) { ?>
            <p class="navbar-text navbar-right">Signed in · <a href="/login?logout=please">Logout</a></p>
            <?php } ?>
          </div>
          <!-- AddThis Button END -->
          <ul class="nav navbar-nav">
            <?php          
            if(isset($sampleGroup))
            {
              echo '<li'.(is_page('bags') ? ' class="active"' : '').'><a href="bags/?group='.$sampleGroup->getID().'">Bags</a></li>';
            }
            ?>
            <?php if($emuTheme->userAdminManager->isUserLoggedIn()) { ?>
            <li <?php echo is_page('sample-groups') ? 'class="active"' : '' ?>><a href="/sample-groups">Sample Groups</a></li>
            <li <?php echo is_page('log-editor') ? 'class="active"' : '' ?>><a href="/log-editor">Log Editor</a></li>
            <?php } else { ?>
            <li <?php echo is_page('login') ? 'class="active"' : '' ?>><a href="/login">Login</a></li>
            <?php } ?>
          </ul>
          

        </div><!--/.qnav-collapse -->
      </div>
    </div>


    <div class="container" id="mainWrap">