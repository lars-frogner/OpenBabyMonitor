<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(TEMPLATES_DIR . '/network_settings.php');

$connection_succeeded = null;
$password_changed = null;
$which_password = null;
if (isset($_POST['connect'])) {
  $ssid = $_POST['available_networks'];
  $password = isset($_POST['password']) ? $_POST['password'] : null;
  $remember = isset($_POST['remember']);
  $connection_succeeded = connectToNetwork($_DATABASE, $ssid, $password, $remember);
} elseif (isset($_POST['forget'])) {
  $ssid = $_POST['known_networks'];
  removeKnownNetwork($_DATABASE, $ssid);
} elseif (isset($_POST['change_site_password'])) {
  updatePassword($_DATABASE, $_POST['password']);
  $password_changed = true;
  $which_password = 'site';
} elseif (isset($_POST['change_ap_password'])) {
  executeSettingOfNewAPPassword($_POST['password']);
  $password_changed = true;
  $which_password = 'ap';
}

$mode = readCurrentMode($_DATABASE);
$available_networks = obtainWirelessScanResults();
$known_networks = readKnownNetworks($_DATABASE);
$connected_network = obtainConnectedNetworkSSID();
?>

<!DOCTYPE html>
<html>

<head>
  <?php
  require_once(TEMPLATES_DIR . '/head_common.php');
  ?>
</head>

<body>
  <header>
    <?php
    require_once(TEMPLATES_DIR . '/navbar.php');
    require_once(TEMPLATES_DIR . '/confirmation_modal.php');
    require_once(TEMPLATES_DIR . '/jquery_js.php');
    ?>
  </header>

  <main id="main_container" style="display: none;">
    <?php if ($connection_succeeded === true) { ?>
      <div class="d-flex flex-row justify-content-center mt-3">
        <div class="d-flex flex-column">
          <span class="alert alert-success text-center"><?php echo LANG['connection_succeeded']; ?></span>
        </div>
      </div>
    <?php } ?>
    <?php if ($connection_succeeded === false) { ?>
      <div class="d-flex flex-row justify-content-center mt-3">
        <div class="d-flex flex-column">
          <span class="alert alert-danger text-center"><?php echo LANG['connection_failed']; ?></span>
        </div>
      </div>
    <?php } ?>
    <div style="display: none;" id="switching_network_info">
      <div class="d-flex flex-row justify-content-center mt-3">
        <div class="d-flex flex-column text-center">
          <span class="alert alert-warning"><?php echo LANG['switching_network']; ?></span>
          <a class="btn btn-secondary" href="network_settings.php"><?php echo LANG['refresh']; ?></a>
        </div>
      </div>
    </div>
    <?php if ($password_changed === true) { ?>
      <div class="d-flex flex-row justify-content-center mt-3">
        <div class="d-flex flex-column">
          <span class="alert alert-success text-center"><?php echo LANG[$which_password . '_password_changed']; ?></span>
        </div>
      </div>
    <?php } ?>
    <div class="container" id="network_settings_form_container">
      <h1 class="my-4"><?php echo LANG['network_settings']; ?></h1>
      <form id="network_settings_form" action="" method="post">
        <div class="row mb-3">
          <div class="col-auto mx-3 my-2">
            <div class="form-group">
              <label class="form-label h3" for="available_networks"><?php echo LANG['available_networks']; ?></label>
              <?php generateAvailableNetworksSelect($available_networks, $known_networks, $connected_network, 'available_networks'); ?>
            </div>
            <div class="row form-group align-items-center my-3">
              <div class="col-auto mb-3">
                <div class="form-floating">
                  <input type="password" name="password" class="form-control" id="network_password_input" placeholder="" disabled>
                  <label class="text-bm" for="network_password_input">
                    <?php echo LANG['password'] . ' (8-63 ' . LANG['password_characters'] . ')'; ?>
                  </label>
                </div>
              </div>
              <div class="col-auto mb-3">
                <div class="form-check">
                  <input type="checkbox" name="remember" class="form-check-input" id="remember_check" disabled>
                  <label class="form-check-label" for="remember_check"><?php echo LANG['remember']; ?></label>
                </div>
              </div>
              <div class="col-auto mb-3">
                <button type="submit" name="connect" style="display: none;" id="connect_submit_button" disabled></button><button class="btn btn-secondary" id="connect_button" disabled><?php echo LANG['connect']; ?></button>
                <a href="activate_ap_mode.php" style="display: none;" id="disconnect_submit_button" disabled></a><button class="btn btn-secondary" id="disconnect_button" disabled><?php echo LANG['disconnect']; ?></button>
              </div>
            </div>
          </div>
          <div class="col-auto mx-3 my-2">
            <label class="form-label h3" for="known_networks"><?php echo LANG['known_networks']; ?></label>
            <?php generateKnownNetworksSelect($known_networks, 'known_networks'); ?>
            <div class="form-group my-3">
              <button type="submit" name="forget" style="display: none;" id="forget_submit_button" disabled></button><button class="btn btn-secondary" id="forget_button" disabled><?php echo LANG['forget']; ?></button>
            </div>
          </div>
          <div class="col-auto mx-3 my-2">
            <h3><?php echo LANG['change_site_password']; ?></h3>
            <div class="form-floating">
              <input type="password" name="password" class="form-control" id="site_password_input" placeholder="">
              <label class="text-bm" for="site_password_input">
                <?php echo LANG['new_password'] . ' (4+ ' . LANG['password_characters'] . ')'; ?>
              </label>
            </div>
            <div class="form-group my-3">
              <button type="submit" name="change_site_password" style="display: none;" id="change_site_password_submit_button" disabled></button><button class="btn btn-secondary" id="change_site_password_button" disabled><?php echo LANG['change']; ?></button>
            </div>
          </div>
          <div class="col-auto mx-3 my-2">
            <h3><?php echo LANG['change_ap_password']; ?></h3>
            <div class="form-floating">
              <input type="password" name="password" class="form-control" id="ap_password_input" placeholder="">
              <label class="text-bm" for="ap_password_input">
                <?php echo LANG['new_password'] . ' (8-63 ' . LANG['password_characters'] . ')'; ?>
              </label>
            </div>
            <div class="form-group my-3">
              <button type="submit" name="change_ap_password" style="display: none;" id="change_ap_password_submit_button" disabled></button><button class="btn btn-secondary" id="change_ap_password_button" disabled><?php echo LANG['change']; ?></button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </main>
</body>

<?php
require_once(TEMPLATES_DIR . '/bootstrap_js.php');
require_once(TEMPLATES_DIR . '/js-cookie_js.php');

require_once(TEMPLATES_DIR . '/notifications_js.php');
?>

<script>
  const SETTINGS_FORM_ID = 'network_settings_form';
  const USES_CAMERA = <?php echo USES_CAMERA ? 'true' : 'false'; ?>;
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
  const DETECT_FORM_CHANGES = false;
</script>
<script src="js/style.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/monitoring.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>
<script src="js/network_settings.js"></script>
<script>
  $(function() {
    captureElementState(SETTINGS_FORM_ID);
  });
</script>

</html>
