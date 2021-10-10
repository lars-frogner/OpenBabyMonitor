<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(SRC_PATH . '/settings.php');
require_once(SRC_PATH . '/control.php');

$mode = readCurrentMode($_DATABASE);

$setting_type = 'listen';
$table_name = $setting_type . '_settings';
if (isset($_POST['submit'])) {
  $settings_edited = true;
  unset($_POST['submit']);
  updateValuesInTable($_DATABASE, $table_name, withPrimaryKey(convertSettingValues($setting_type, $_POST)));
  $values = readValuesFromTable($_DATABASE, $table_name, readTableColumnNamesFromConfig($table_name));
} elseif (isset($_POST['reset'])) {
  $settings_edited = true;
  $values = readTableInitialValuesFromConfig($table_name);
  updateValuesInTable($_DATABASE, $table_name, $values);
} else {
  $settings_edited = false;
  $values = readValuesFromTable($_DATABASE, $table_name, readTableColumnNamesFromConfig($table_name));
}
$grouped_values = groupSettingValues($setting_type, $values);
require_once(TEMPLATES_PATH . '/settings.php');
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
      <h1 class="my-4">Varselinnstillinger</h1>
      <form id="listen_settings_form" action="" method="post">
        <div class="row">
          <?php generateInputs($setting_type, $grouped_values); ?>
        </div>
        <div class="my-4">
          <button type="submit" name="submit" class="btn btn-primary">Bekreft</button>
          <button name="undo" class="btn btn-secondary" onclick="$('#listen_settings_form').trigger('reset');">Angre</button>
          <button type="submit" name="reset" class="btn btn-secondary">Tilbakestill</button>
        </div>
      </form>
    </div>
  </main>
</body>

<?php
require_once(TEMPLATES_PATH . '/bootstrap_js.php');
require_once(TEMPLATES_PATH . '/jquery_js.php');
?>

<script>
  const SETTINGS_FORM_ID = 'listen_settings_form';
  const SETTINGS_EDITED = <?php echo $settings_edited ? 'true' : 'false'; ?>;
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const SITE_MODE = <?php echo MODE_VALUES[$setting_type]; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
  const DETECT_FORM_CHANGES = true;
</script>
<script src="js/settings.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>
<script>
  <?php generateBehavior($setting_type); ?>
</script>
<script>
  $(function() {
    captureElementState(SETTINGS_FORM_ID);
  });
</script>

</html>
