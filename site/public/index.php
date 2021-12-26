<?php
require_once(dirname(__DIR__) . '/config/site_config.php');

if (MIC_CONNECTED) {
  redirectIfLoggedIn('main.php');
}

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

  <?php if (ACCESS_POINT_ACTIVE && !TIME_SYNCED) { ?>
    <script>
      const SERVER_TIMESTAMP = <?php echo microtime(true); ?> * 1e3; // Milliseconds
      const CLIENT_TIMESTAMP = Date.now(); // Milliseconds
    </script>
    <script src="js/sync_time.js"></script>
  <?php } ?>
</head>

<body class="fillview" style="overflow: hidden;">
  <div class="d-flex flex-column fillview">
    <div class="d-flex flex-column flex-grow-1 justify-content-center overflow-auto">
      <main id="main_container" class="fillview">
        <?php if (!MIC_CONNECTED) { ?>
          <div class="d-flex flex-row align-items-center justify-content-center h-100">
            <div class="d-flex flex-column mx-3">
              <div class="d-flex align-items-center justify-content-center alert alert-danger">
                <div class="text-center"><?php echo LANG['mic_not_connected']; ?></div>
              </div>
              <a class="btn btn-secondary" href="index.php"><?php echo LANG['refresh']; ?></a>
            </div>
          </div>
        <?php } else { ?>
          <div class="container fillview">
            <div class="row h-100 justify-content-center align-items-center">
              <?php if ($automatically_signed_out) { ?>
                <div id="signed_out_warning">
                  <div class="d-flex flex-row justify-content-center align-self-start mx-3 mt-3">
                    <div class="d-flex align-items-center justify-content-between alert alert-info">
                      <div style="width: 1rem;"></div>
                      <div class="mx-3 text-center"><?php echo LANG['was_signed_out']; ?></div>
                      <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor" onclick="$('#signed_out_warning').hide(); $('#signin_aside').removeClass('align-self-start');">
                        <use href="media/bootstrap-icons.svg#x-lg" />
                      </svg>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <aside id="signin_aside" class="col-auto<?php echo $automatically_signed_out ? ' align-self-start' : '' ?>">
                <div class="card px-4">
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
                        <button type="submit" name="submit" class="btn btn-primary" <?php echo MIC_CONNECTED ? '' : ' disabled'; ?>><?php echo LANG['sign_in']; ?></button>
                      </div>
                    </form>
                  </article>
                </div>
              </aside>
            </div>
          </div>
        <?php } ?>
      </main>
    </div>
  </div>
  <div style="position: relative; top: 100%; left: 0; width: 100%; height: 10vh; overflow: hidden;"></div>
</body>

<?php
require_once(TEMPLATES_DIR . '/jquery_js.php');
?>

<script src="js/index.js"></script>

</html>
