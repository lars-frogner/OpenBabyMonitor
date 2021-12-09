<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(SRC_DIR . '/settings.php');

$mode = readCurrentMode($_DATABASE);

$current_timezone = getenv('BM_TIMEZONE');
$timezones = getValidTimezones();

$server_property_changed = null;
if (isset($_POST['change_timezone'])) {
  $timezone = $_POST['timezone'];
  if ($timezone != $current_timezone) {
    executeSettingOfNewTimezone($timezone);
    $current_timezone = $timezone;
    $server_property_changed = 'timezone_changed';
  }
}
if (isset($_POST['change_hostname'])) {
  $hostname = $_POST['hostname'];
  logout(PROTOCOL . "://$hostname", function () use ($hostname) {
    executeSettingOfNewHostname($hostname);
  });
}
if (isset($_POST['change_device_password'])) {
  executeSettingOfNewDevicePassword($_POST['device_password']);
  $server_property_changed = 'device_password_changed';
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

if ($settings_edited) {
  readMonitoringSettings();
}
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
        <div class="container">
          <div class="row">
            <?php if ($server_property_changed !== null) { ?>
              <div id="server_property_changed_msg">
                <div class="d-flex flex-row justify-content-center mx-3 mt-3">
                  <div class="d-flex align-items-center justify-content-between alert alert-success">
                    <div style="width: 1rem;"></div>
                    <div class="mx-3 text-center"><?php echo LANG[$server_property_changed]; ?></div>
                    <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor" onclick="$('#server_property_changed_msg').hide();">
                      <use href="media/bootstrap-icons.svg#x-lg" />
                    </svg>
                  </div>
                </div>
              </div>
            <?php } ?>
            <h1 class="my-4"><?php echo LANG['system_settings']; ?></h1>
            <div class="col-auto mb-5">
              <form id="system_settings_form" action="" method="post">
                <div class="row">
                  <?php
                  $permission_button_html = '<div class="border text-center my-3"><p class="my-2" id="notification_message"></p><button id="notification_button" class="btn btn-primary mb-2" style="display: none;"></button></div>';
                  generateInputs($setting_type, $grouped_values, ['browser_notifications' => $permission_button_html], []);
                  ?>
                </div>
                <div class="my-4">
                  <button type="submit" name="submit" class="btn btn-primary"><?php echo LANG['submit']; ?></button>
                  <button type="submit" name="reset" style="display: none;" id="reset_submit_button" disabled></button><input type="button" class="btn btn-warning ms-2" id="reset_button" value="<?php echo LANG['reset']; ?>" />
                </div>
              </form>
            </div>
            <div class="col-auto">
              <h2><?php echo LANG['server_properties']; ?></h2>
              <form id="server_properties_form" action="" method="post">
                <div class="row">
                  <div class="col-auto px-3 mb-3" style="max-width: 20rem;">
                    <div class="row align-items-center mb-3">
                      <label class="form-label" for="server_timezone_select">
                        <?php echo LANG['timezone']; ?>
                      </label>
                      <div class="col-8">
                        <select name="timezone" class="form-select" id="server_timezone_select">
                          <?php foreach ($timezones as $timezone) { ?>
                            <option value="<?php echo $timezone; ?>" <?php echo ($timezone == $current_timezone) ? 'selected' : ''; ?>><?php echo $timezone; ?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="col-4">
                        <button type="submit" name="change_timezone" style="display: none;" id="change_timezone_submit_button" disabled></button><button class="btn btn-primary" id="change_timezone_button" disabled><?php echo LANG['change']; ?></button>
                      </div>
                    </div>
                    <hr class="me-2">
                    <div class="row align-items-center">
                      <label class="form-label" for="hostname_input">
                        <?php echo LANG['hostname']; ?>
                      </label>
                      <div class="col-8">
                        <input type="text" name="hostname" class="form-control" id="hostname_input" placeholder="" value="<?php echo SERVER_HOSTNAME; ?>">
                      </div>
                      <div class="col-4">
                        <button type="submit" name="change_hostname" style="display: none;" id="change_hostname_submit_button" disabled></button><button class="btn btn-primary" id="change_hostname_button" disabled><?php echo LANG['change']; ?></button>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-auto">
                        <h6 id="hostname_input_invalid_msg" class="text-warning fw-normal fs-6 mt-2" style="display: none;"><small><?php echo LANG['hostname_invalid']; ?></small></h6>
                      </div>
                    </div>
                    <hr class="me-2">
                    <div class="row align-items-center my-3">
                      <label class="form-label" for="device_password_input">
                        <?php echo LANG['new_password']; ?>
                      </label>
                      <div class="col-8">
                        <input type="password" name="device_password" class="form-control" id="device_password_input" placeholder=""></input>
                      </div>
                      <div class="col-4">
                        <button type="submit" name="change_device_password" style="display: none;" id="change_device_password_submit_button" disabled></button><button class="btn btn-primary" id="change_device_password_button" disabled><?php echo LANG['change']; ?></button>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
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

  const SERVER_HOSTNAME = '<?php echo SERVER_HOSTNAME; ?>';
  const CURRENT_TIMEZONE = '<?php echo $current_timezone; ?>';
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
