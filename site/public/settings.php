<?php
require_once('../config/config.php');
redirectIfLoggedOut('index.php');
?>
<!DOCTYPE html>
<html>
  <head>
    <?php
      require_once(TEMPLATES_PATH . '/head_common.php');
    ?>
  </head>

  <body>
    <?php require_once(TEMPLATES_PATH . '/navbar.php'); ?>
  </body>
</html>
