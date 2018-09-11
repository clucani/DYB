<?php
/*
Template Name: Drying Trend
*/

global $emuTheme;

// Make sure they're logged in
$owner_id = $emuTheme->userAdminManager->checkAuth();

?>

<?php get_header() ?>

      <div class="row">
        <div class="col-md-8 col-md-offset-2">
          
          <?php echo $emuTheme->getView('drying-trends') ?>

        </div>
      </div>

    </div>

<?php get_footer() ?>