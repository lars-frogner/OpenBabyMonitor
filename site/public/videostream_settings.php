<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(SRC_PATH . '/settings.php');
require_once(SRC_PATH . '/control.php');

$mode = readCurrentMode($_DATABASE);

$setting_type = 'videostream';
$table_name = $setting_type . '_settings';
if (isset($_POST['submit'])) {
  $settings_edited = true;
  unset($_POST['submit']);
  $values = convertSettingValues($setting_type, $_POST);
  updateValuesInTable($_DATABASE, $table_name, withPrimaryKey($values));
} else {
  $settings_edited = false;
  $values = readValuesFromTable($_DATABASE, $table_name, readTableColumnNamesFromConfig($table_name));
}
$grouped_values = groupSettingValues($setting_type, $values);
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
    require_once(TEMPLATES_PATH . '/settings.php');
    ?>
  </header>

  <main>
    <div class="container">
      <h1>Video settings</h1>
      <form id="videostream_settings_form" action="" method="post">
        <div class='row'>
          <?php generateInputs($setting_type, $grouped_values); ?>
        </div>
        <div class="row">
          <div class='col'>
            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
          </div>
        </div>
      </form>
  </main>
</body>

<?php
require_once(TEMPLATES_PATH . '/bootstrap_js.php');
require_once(TEMPLATES_PATH . '/jquery_js.php');
?>

<script>
  const SETTINGS_FORM_ID = 'videostream_settings_form';
  const SETTINGS_EDITED = <?php echo $settings_edited ? 'true' : 'false'; ?>;
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const SITE_MODE = <?php echo MODE_VALUES[$setting_type]; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
</script>
<script src="js/settings.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>

</html>
