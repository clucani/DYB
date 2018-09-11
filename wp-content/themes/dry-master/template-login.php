<?php
/*
Template Name: Login
*/

global $emuTheme;

if(get_val('logout')) {
  $emuTheme->userAdminManager->setUserLoggedOut();
  $emuTheme->deleteSessionData('group');
  header('Location: /login');
  exit();
}

?>

<?php get_header() ?>

      <div class="row">
      <form class="col-md-6 col-md-offset-3" role="form" action="" id="signUp" method="post" autocomplete="off">
        
        <h1>Login</h1>

        <p class="lead"><small>Fields marked * are required.</small></p>

        <?php echo $emuTheme->getMessages('login', $wrapper_start = '<div class="alert alert-danger">', $wrapper_end = '</div>' ); ?>

        <div class="form-horizontal">
          <div class="form-group">
            <label for="email" class="col-sm-2 control-label">Email*</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="email" name="email" placeholder="Email">
            </div>
          </div>
          <div class="form-group">
            <label for="user_password" class="col-sm-2 control-label">Password*</label>
            <div class="col-sm-10">
              <input type="password" class="form-control" name="user_password" id="user_password" placeholder="Password">
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <input type="submit" class="btn btn-default" name="e-button" value="Sign in" />
            </div>
          </div>
        </div>

        <?php $emuTheme->bindProcessor('login')?>

      </form>
      </div>

    </div>

<?php get_footer() ?>