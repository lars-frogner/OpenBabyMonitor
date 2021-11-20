<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedIn('main.php');

$target_uri = getURIFromGETRequest();
$automatically_signed_out = $target_uri != null;
if (!$automatically_signed_out) {
  $target_uri = 'main.php';
}

$login_successful = null;
if (isset($_POST['submit'])) {
  $login_successful = tryLogin($_DATABASE, $_POST['password'], addQueryToURI($target_uri, 'signin', '1'));
}
?>

<link href="css/fill.css" rel="stylesheet">

<!DOCTYPE html>
<html class="fillview">

<head>
  <?php require_once(TEMPLATES_DIR . '/head_common.php'); ?>
</head>

<body class="fillview">
  <main id="main_container" class="fillview">
    <div class="container h-100">
      <div class="row h-100 justify-content-center align-items-center">
        <?php if ($automatically_signed_out) { ?>
          <div class="d-flex flex-row justify-content-center align-self-start mt-3">
            <div class="d-flex flex-column">
              <span class="alert alert-warning text-center"><?php echo LANG['was_signed_out']; ?></span>
            </div>
          </div>
        <?php } ?>
        <aside class="col-sm-5<?php echo $automatically_signed_out ? ' align-self-start' : '' ?>">
          <div class="card">
            <article class="card-body">
              <h3 class="card-title text-center"><?php echo LANG['please_sign_in']; ?></h3>
              <?php if ($login_successful === false) { ?>
                <hr>
                <p class="text-center text-danger"><?php echo LANG['wrong_password']; ?></p>
              <?php } ?>
              <form action="" method="post">
                <div class="my-3 form-group">
                  <label class="visually-hidden" for="password"><?php echo LANG['password']; ?></label>
                  <input type="password" name="password" class="form-control" id="password" placeholder="<?php echo LANG['password']; ?>" required>
                </div>
                <div class="form-group text-center">
                  <button type="submit" name="submit" class="btn btn-primary"><?php echo LANG['sign_in']; ?></button>
                </div>
              </form>
            </article>
          </div>
        </aside>
      </div>
    </div>
  </main>
</body>

<?php
require_once(TEMPLATES_DIR . '/jquery_js.php');
?>

<script src="js/index.js"></script>

</html>
