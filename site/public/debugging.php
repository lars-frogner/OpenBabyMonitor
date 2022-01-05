<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
?>

<link href="css/fill.css" rel="stylesheet">

<!DOCTYPE html>
<html class="fillview">

<head>
  <?php
  require_once(TEMPLATES_DIR . '/head_common.php');
  ?>
</head>

<body class="fillview" style="overflow: hidden;">
  <div class="d-flex flex-column fillview">
    <header>
      <?php
      require_once(TEMPLATES_DIR . '/navbar.php');
      require_once(TEMPLATES_DIR . '/confirmation_modal.php');
      ?>
    </header>

    <main class="d-flex flex-column flex-grow-1 align-items-center justify-content-center overflow-auto text-center">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-auto">
            <a class="btn btn-secondary d-flex justify-content-center align-items-center" href="obtain_logs.php">
              <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                <use href="media/bootstrap-icons.svg#file-earmark-text" />
              </svg>
              <?php echo LANG['download_logs']; ?>
            </a>
          </div>
        </div>
      </div>
    </main>
  </div>
  <div style="position: relative; top: 100%; left: 0; width: 100%; height: 10vh; overflow: hidden;"></div>
</body>

<?php
require_once(TEMPLATES_DIR . '/bootstrap_js.php');
require_once(TEMPLATES_DIR . '/jquery_js.php');
require_once(TEMPLATES_DIR . '/js-cookie_js.php');
?>

<script src="js/confirmation_modal.js"></script>

<?php
require_once(TEMPLATES_DIR . '/notifications_js.php');
require_once(TEMPLATES_DIR . '/monitoring_js.php');
?>

<script src="js/style.js"></script>
<script src="js/jquery_utils.js"></script>

<script src="js/navbar.js"></script>
<script src="js/navbar_main.js"></script>
<script src="js/debugging.js"></script>

</html>
