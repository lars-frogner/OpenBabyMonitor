<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

$mode = readCurrentMode($_DATABASE);
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
    <?php
    require_once(TEMPLATES_PATH . '/navbar.php');
    require_once(TEMPLATES_PATH . '/confirmation_modal.php');
    ?>
  </header>

  <main>
    <div class="container">
      <h1>Audio settings</h1>
      <form id="audiostream_settings_form" action="" method="post">
        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
      </form>
  </main>
</body>

<?php
require_once(TEMPLATES_PATH . '/bootstrap_js.php');
require_once(TEMPLATES_PATH . '/jquery_js.php');
?>

<script>
  const SETTINGS_FORM_ID = 'audiostream_settings_form';
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
</script>
<script src="js/jquery_utils.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>

</html>
