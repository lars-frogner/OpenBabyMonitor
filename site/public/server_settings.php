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

<body>
  <header>
    <?php require_once(TEMPLATES_PATH . '/navbar_settings.php'); ?>
  </header>

  <main>
    <div class="container">
      <h1>Server settings</h1>
      <form id="server_settings_form" action="" method="post">
        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
      </form>
  </main>
</body>

<?php
require_once(TEMPLATES_PATH . '/bootstrap_js.php');
require_once(TEMPLATES_PATH . '/jquery_js.php');
?>

<script src="js/utils.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/navbar_settings.js"></script>

<script>
  handleModalTexts('server_settings_form');
</script>

</html>
