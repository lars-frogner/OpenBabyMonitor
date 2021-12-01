<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(TEMPLATES_DIR . '/network_settings.php');

$connection_succeeded = null;
$password_changed = null;
$which_password = null;
if (isset($_POST['connect'])) {
  $ssid = $_POST['available_networks'];
  $password = isset($_POST['network_password']) ? $_POST['network_password'] : null;
  $remember = isset($_POST['remember']);
  $connection_succeeded = connectToNetwork($_DATABASE, $ssid, $password, $remember);
} elseif (isset($_POST['forget'])) {
  $ssid = $_POST['known_networks'];
  removeKnownNetwork($_DATABASE, $ssid);
} elseif (isset($_POST['change_site_password'])) {
  updatePassword($_DATABASE, $_POST['site_password']);
  $password_changed = true;
  $which_password = 'site';
} elseif (isset($_POST['change_ap_password'])) {
  executeSettingOfNewAPPassword($_POST['ap_password']);
  $password_changed = true;
  $which_password = 'ap';
}

$mode = readCurrentMode($_DATABASE);
$available_networks = obtainWirelessScanResults();
$known_networks = readKnownNetworks($_DATABASE);
$connected_network = obtainConnectedNetworkSSID();
?>

<link href="css/fill.css" rel="stylesheet">

<!DOCTYPE html>
<html class="fillview">

<head>
  <?php
  require_once(TEMPLATES_DIR . '/head_common.php');

  storeNetworkInfo($available_networks, $known_networks, $connected_network);
  ?>
</head>

<body class="fillview" style="overflow: hidden;">
  <div class="d-flex flex-column fillview">
    <header>
      <?php
      require_once(TEMPLATES_DIR . '/navbar.php');
      require_once(TEMPLATES_DIR . '/confirmation_modal.php');
      require_once(TEMPLATES_DIR . '/jquery_js.php');
      ?>
    </header>

    <div class="d-flex flex-column flex-grow-1 justify-content-center overflow-auto">
      <main id="main_container" class="fillview" style="display: none;">

        <?php if ($connection_succeeded === true) { ?>
          <div id="connection_succeeded_msg" class="network-status-msg">
            <div class="d-flex flex-row justify-content-center mx-3 mt-3">
              <div class="d-flex align-items-center justify-content-between alert alert-success">
                <div style="width: 1rem;"></div>
                <div class="mx-3 text-center"><?php echo LANG['connection_succeeded']; ?></div>
                <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor" onclick="$('#connection_succeeded_msg').hide();">
                  <use href="media/bootstrap-icons.svg#x-lg" />
                </svg>
              </div>
            </div>
          </div>
        <?php } ?>
        <?php if ($connection_succeeded === false) { ?>
          <div id="connection_failed_msg" class="network-status-msg">
            <div class="d-flex flex-row justify-content-center mx-3 mt-3">
              <div class="d-flex align-items-center justify-content-between alert alert-danger">
                <div style="width: 1rem;"></div>
                <div class="mx-3 text-center"><?php echo LANG['connection_failed']; ?></div>
                <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor" onclick="$('#connection_failed_msg').hide();">
                  <use href="media/bootstrap-icons.svg#x-lg" />
                </svg>
              </div>
            </div>
          </div>
        <?php } ?>
        <div style="display: none;" id="switching_network_info" class="fillview">
          <div class="d-flex flex-row align-items-center justify-content-center h-100">
            <div class="d-flex flex-column mx-3">
              <div class="d-flex align-items-center justify-content-center alert alert-warning">
                <div class="text-center"><?php echo LANG['switching_network']; ?></div>
              </div>
              <a class="btn btn-secondary" href="network_settings.php"><?php echo LANG['refresh']; ?></a>
            </div>
          </div>
        </div>
        <?php if ($password_changed === true) { ?>
          <div id="password_changed_msg" class="network-status-msg">
            <div class="d-flex flex-row justify-content-center mx-3 mt-3">
              <div class="d-flex align-items-center justify-content-between alert alert-success">
                <div style="width: 1rem;"></div>
                <div class="text-center"><?php echo LANG[$which_password . '_password_changed']; ?></div>
                <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor" onclick="$('#password_changed_msg').hide();">
                  <use href="media/bootstrap-icons.svg#x-lg" />
                </svg>
              </div>
            </div>
          </div>
        <?php } ?>
        <div class="container" id="network_settings_form_container">
          <h1 class="my-4"><?php echo LANG['network_settings']; ?></h1>
          <form id="network_settings_form" action="" method="post">
            <div class="row mb-3">
              <div class="col-auto px-3 my-2">
                <div class="form-group">
                  <div class="form-label h3 mb-3" for="available_networks"><?php echo LANG['available_networks']; ?></div>
                  <?php generateAvailableNetworksRadioAndSelect($available_networks, 'available_networks'); ?>
                </div>
                <div class="row form-group align-items-center my-3">
                  <div class="col-auto">
                    <div class="form-floating">
                      <input type="password" name="network_password" class="form-control" id="network_password_input" placeholder="" disabled>
                      <label class="text-bm" for="network_password_input">
                        <?php echo LANG['password'] . ' (8-63 ' . LANG['password_characters'] . ')'; ?>
                      </label>
                    </div>
                  </div>
                  <div class="col-auto my-2">
                    <div class="form-check">
                      <input type="checkbox" name="remember" class="form-check-input" id="remember_check" disabled>
                      <label class="form-check-label" for="remember_check"><?php echo LANG['remember']; ?></label>
                    </div>
                  </div>
                </div>
                <div class="row form-group align-items-center mb-3">
                  <div class="col-auto">
                    <button type="submit" name="connect" style="display: none;" id="connect_submit_button" disabled></button>
                    <button class="btn btn-secondary" id="connect_button" disabled><?php echo LANG['connect']; ?></button>
                    <a href="activate_ap_mode.php" style="display: none;" id="disconnect_submit_button" disabled></a>
                    <button class="btn btn-secondary" id="disconnect_button" disabled><?php echo LANG['disconnect']; ?></button>
                  </div>
                </div>
              </div>
              <div class="col-auto px-3 my-2">
                <label class="form-label h3 mb-3" for="known_networks"><?php echo LANG['known_networks']; ?></label>
                <?php generateKnownNetworksRadioAndSelect($known_networks, 'known_networks'); ?>
                <div class="form-group my-3">
                  <button type="submit" name="forget" style="display: none;" id="forget_submit_button" disabled></button><button class="btn btn-secondary" id="forget_button" disabled><?php echo LANG['forget']; ?></button>
                </div>
              </div>
              <div class="col-auto px-3 my-2">
                <label class="form-label h3 mb-3"><?php echo LANG['change_site_password']; ?></label>
                <div class="form-floating">
                  <input type="password" name="site_password" class="form-control" id="site_password_input" placeholder="">
                  <label class="text-bm" for="site_password_input">
                    <?php echo LANG['new_password'] . ' (4+ ' . LANG['password_characters'] . ')'; ?>
                  </label>
                </div>
                <div class="form-group my-3">
                  <button type="submit" name="change_site_password" style="display: none;" id="change_site_password_submit_button" disabled></button><button class="btn btn-secondary" id="change_site_password_button" disabled><?php echo LANG['change']; ?></button>
                </div>
              </div>
              <div class="col-auto px-3 my-2">
                <label class="form-label h3 mb-3"><?php echo LANG['change_ap_password']; ?></label>
                <div class="form-floating">
                  <input type="password" name="ap_password" class="form-control" id="ap_password_input" placeholder="">
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
    </div>
  </div>
  <div style="position: relative; top: 100%; left: 0; width: 100%; height: 10vh; overflow: hidden;"></div>
</body>

<?php
require_once(TEMPLATES_DIR . '/bootstrap_js.php');
require_once(TEMPLATES_DIR . '/js-cookie_js.php');

require_once(TEMPLATES_DIR . '/notifications_js.php');
require_once(TEMPLATES_DIR . '/monitoring_js.php');
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
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>
<script src="js/network_settings.js"></script>

</html>
