<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(SRC_DIR . '/settings.php');

$mode = readCurrentMode($_DATABASE);

if (isset($_POST['change_device_password'])) {
  executeSettingOfNewDevicePassword($_POST['device_password']);
  $password_changed = true;
} else {
  $password_changed = false;
}

$setting_type = 'system';
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
require_once(TEMPLATES_DIR . '/settings.php');
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

    <div class="d-flex flex-column flex-grow-1 justify-content-center overflow-auto">
      <main id="main_container" class="fillview" style="display: none;">
        <?php if ($password_changed === true) { ?>
          <div id="device_password_changed_msg">
            <div class="d-flex flex-row justify-content-center mx-3 mt-3">
              <div class="d-flex align-items-center justify-content-between alert alert-success">
                <div style="width: 1rem;"></div>
                <div class="mx-3 text-center"><?php echo LANG['device_password_changed']; ?></div>
                <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor" onclick="$('#device_password_changed_msg').hide();">
                  <use href="media/bootstrap-icons.svg#x-lg" />
                </svg>
              </div>
            </div>
          </div>
        <?php } ?>
        <div class="container">
          <h1 class="my-4"><?php echo LANG['system_settings']; ?></h1>
          <form id="system_settings_form" action="" method="post">
            <div class="row">
              <?php
              $permission_button_html = '<div class="border text-center my-3"><p class="my-2" id="notification_message"></p><button id="notification_button" class="btn btn-primary mb-2" style="display: none;"></button></div>';
              generateInputs($setting_type, $grouped_values, ['browser_notifications' => $permission_button_html], []);
              ?>
            </div>
            <div class="my-4">
              <button type="submit" name="submit" class="btn btn-primary"><?php echo LANG['submit']; ?></button>
              <button name="undo" class="btn btn-secondary" onclick="$('#system_settings_form').trigger('reset');"><?php echo LANG['undo']; ?></button>
              <button type="submit" name="reset" class="btn btn-secondary"><?php echo LANG['reset']; ?></button>
            </div>

            <div class="row mt-5">
              <div class="col-auto px-3">
                <label class="form-label h3 mb-3"><?php echo LANG['change_device_password']; ?></label>
                <div class="form-floating">
                  <input type="password" name="device_password" class="form-control" id="device_password_input" placeholder="">
                  <label class="text-bm" for="device_password_input">
                    <?php echo LANG['new_password']; ?>
                  </label>
                </div>
                <div class="form-group my-3">
                  <button type="submit" name="change_device_password" style="display: none;" id="change_device_password_submit_button" disabled></button><button class="btn btn-secondary" id="change_device_password_button" disabled><?php echo LANG['change']; ?></button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </main>
    </div>
  </div>
  <div style="position: relative; top: 100%; left: 0; width: 100%; height: 10vh; overflow: hidden;"></div>
</body>

<?php
require_once(TEMPLATES_DIR . '/bootstrap_js.php');
require_once(TEMPLATES_DIR . '/jquery_js.php');
require_once(TEMPLATES_DIR . '/js-cookie_js.php');

require_once(TEMPLATES_DIR . '/notifications_js.php');
require_once(TEMPLATES_DIR . '/monitoring_js.php');
?>

<script>
  const SETTINGS_FORM_ID = 'system_settings_form';
  const SETTINGS_EDITED = <?php echo $settings_edited ? 'true' : 'false'; ?>;
  const USES_CAMERA = <?php echo USES_CAMERA ? 'true' : 'false'; ?>;
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const SITE_MODE = null;
  const INITIAL_MODE = <?php echo $mode; ?>;
  const DETECT_FORM_CHANGES = true;
</script>
<script src="js/style.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/settings.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>
<script src="js/system_settings.js"></script>
<script>
  <?php generateBehavior($setting_type); ?>

  $(function() {
    captureElementState(SETTINGS_FORM_ID);
  });
</script>

</html>
