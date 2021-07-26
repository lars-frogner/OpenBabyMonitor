<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
?>

<!DOCTYPE html>
<html>

<head>
  <?php
  require_once(TEMPLATES_PATH . '/head_common.php');
  ?>
</head>

<body class="mb-0">
  <div class="d-flex flex-column overflow-hidden min-vh-100 vh-100">
    <header>
      <?php require_once(TEMPLATES_PATH . '/navbar.php'); ?>
    </header>

    <main class="d-flex flex-column flex-grow-1 overflow-auto justify-content-center">
    </main>
  </div>
</body>

<?php
require_once(TEMPLATES_PATH . '/bootstrap_js.php');
?>

</html>
