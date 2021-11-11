<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(SRC_DIR . '/control.php');

setPHPSysInfoDefaultTemplate((COLOR_SCHEME === 'dark') ? 'dark' : 'phpsysinfo');
?>

<!DOCTYPE html>
<html>

<head>
  <?php
  require_once(TEMPLATES_DIR . '/head_common.php');
  ?>
</head>

<body>
  <div class="d-flex flex-column min-vh-100 vh-100">
    <header>
      <?php
      require_once(TEMPLATES_DIR . '/navbar.php');
      require_once(TEMPLATES_DIR . '/confirmation_modal.php');
      ?>
    </header>

    <main class="d-flex flex-column flex-grow-1 align-items-center justify-content-center overflow-auto text-center">
      <iframe id="phpsysinfo" src="library/phpsysinfo/index.php" title="phpsysinfo" width="100%" height="100%" style="display: none;"></iframe>
      <span id="server_status_busy_spinner" class="spinner-border" style="display: none;"></span>
    </main>
  </div>
</body>

<?php
require_once(TEMPLATES_DIR . '/bootstrap_js.php');
require_once(TEMPLATES_DIR . '/jquery_js.php');
require_once(TEMPLATES_DIR . '/js-cookie_js.php');
?>

<script src="js/style.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/server_status.js"></script>

</html>
