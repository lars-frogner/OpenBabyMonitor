<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(TEMPLATES_DIR . '/network_settings.php');

$current_ap_channel = AP_CHANNEL;

$current_ap_ssid = AP_SSID;

$current_country_code = COUNTRY_CODE;
$country_codes = getValidCountryCodes();

$connection_succeeded = null;
$network_property_changed = null;
if (isset($_POST['connect'])) {
  $ssid = $_POST['available_networks'];
  $password = isset($_POST['network_password']) ? $_POST['network_password'] : null;
  $remember = isset($_POST['remember']);
  $connection_succeeded = connectToNetwork($_DATABASE, $ssid, $password, $remember);
} elseif (isset($_POST['forget'])) {
  $ssid = $_POST['known_networks'];
  removeKnownNetwork($_DATABASE, $ssid);
} elseif (isset($_POST['change_ap_channel'])) {
  $ap_channel = $_POST['ap_channel'];
  if ($ap_channel != $current_ap_channel) {
    executeSettingOfNewAPChannel($ap_channel);
    $current_ap_channel = $ap_channel;
    $network_property_changed = 'ap_channel_changed';
  }
} elseif (isset($_POST['change_ap_ssid_password'])) {
  $ap_ssid = $_POST['ap_ssid'];
  $password = $_POST['ap_password'];
  if ($ap_ssid == $current_ap_ssid) {
    executeSettingOfNewAPPassword($password);
    $network_property_changed = 'ap_password_changed';
  } else {
    executeSettingOfNewAPSSIDAndPassword($ap_ssid, $password);
    $current_ap_ssid = $ap_ssid;
    $network_property_changed = 'ap_ssid_password_changed';
  }
} elseif (isset($_POST['change_site_password'])) {
  updatePassword($_DATABASE, $_POST['site_password']);
  $network_property_changed = 'site_password_changed';
} elseif (isset($_POST['change_country_code'])) {
  $country_code = $_POST['country_code'];
  if ($country_code != $current_country_code) {
    executeSettingOfNewCountryCode($country_code);
    $current_country_code = $country_code;
    $network_property_changed = 'country_code_changed';
  }
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
        <div class="container">
          <div class="row">
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
                  <div class="d-flex align-items-center justify-content-center alert alert-info">
                    <div class="text-center"><?php echo LANG['switching_network']; ?></div>
                  </div>
                  <a class="btn btn-secondary" href="network_settings.php"><?php echo LANG['refresh']; ?></a>
                </div>
              </div>
            </div>
            <?php if ($network_property_changed !== null) { ?>
              <div id="network_property_changed_msg" class="network-status-msg">
                <div class="d-flex flex-row justify-content-center mx-3 mt-3">
                  <div class="d-flex align-items-center justify-content-between alert alert-success">
                    <div style="width: 1rem;"></div>
                    <div class="mx-3 text-center"><?php echo LANG[$network_property_changed]; ?></div>
                    <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor" onclick="$('#network_property_changed_msg').hide();">
                      <use href="media/bootstrap-icons.svg#x-lg" />
                    </svg>
                  </div>
                </div>
              </div>
            <?php } ?>
            <h1 class="my-4 network-settings-form-container"><?php echo LANG['network_settings']; ?></h1>
            <div class="col-auto mb-3 network-settings-form-container">
              <form class="mb-0" action="" method="post">
                <div class="row">
                  <div class="col-auto px-3 my-2">
                    <div class="form-group">
                      <div class="form-label h2 mb-3" for="available_networks"><?php echo LANG['available_networks']; ?></div>
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
                    <label class="form-label h2 mb-3" for="known_networks"><?php echo LANG['known_networks']; ?></label>
                    <?php generateKnownNetworksRadioAndSelect($known_networks, 'known_networks'); ?>
                    <div class="form-group my-3">
                      <button type="submit" name="forget" style="display: none;" id="forget_submit_button" disabled></button><button class="btn btn-secondary" id="forget_button" disabled><?php echo LANG['forget']; ?></button>
                    </div>
                  </div>
                  <div class="col-auto px-3 my-2" style="min-width: 26rem; max-width: 26rem;">
                    <h2><?php echo LANG['access_point']; ?></h2>
                    <div class="row align-items-center mb-3">
                      <label class="form-label mb-0" for="ap_channel_range">
                        <?php echo LANG['ap_channel']; ?>
                      </label>
                      <div class="col-8">
                        <div class="row flex-nowrap">
                          <div class="col-10">
                            <input type="range" name="ap_channel" class="form-range" value="<?php echo $current_ap_channel; ?>" min="1" max="11" step="1" id="ap_channel_range">
                          </div>
                          <div class="col-2 px-0">
                            <output id="ap_channel_range_output"><?php echo $current_ap_channel; ?></output>
                          </div>
                        </div>
                      </div>
                      <div class="col-4">
                        <button type="submit" name="change_ap_channel" style="display: none;" id="change_ap_channel_submit_button" disabled></button><button class="btn btn-primary" id="change_ap_channel_button" disabled><?php echo LANG['change']; ?></button>
                      </div>
                    </div>
                    <hr class="me-4">
                    <div class="row align-items-center">
                      <div class="col-8">
                        <label class="form-label" for="ap_ssid_input">
                          <?php echo LANG['ap_ssid'] . ' (1-32 ' . LANG['password_characters'] . ')'; ?>
                        </label>
                        <input type="text" name="ap_ssid" class="form-control" id="ap_ssid_input" placeholder="" value="<?php echo $current_ap_ssid; ?>">
                        <label class="form-label mt-3" for="ap_password_input">
                          <?php echo LANG['new_password'] . ' (8-63 ' . LANG['password_characters'] . ')'; ?>
                        </label>
                        <input type="password" name="ap_password" class="form-control" id="ap_password_input" placeholder=""></input>
                      </div>
                      <div class="col-4">
                        <button type="submit" name="change_ap_ssid_password" style="display: none;" id="change_ap_ssid_password_submit_button" disabled></button><button class="btn btn-primary" id="change_ap_ssid_password_button" disabled><?php echo LANG['change']; ?></button>
                      </div>
                    </div>
                    <div class="row align-items-center mb-3">
                      <div class="col-8">
                        <h6 id="ap_ssid_requires_password_msg" class="text-warning fw-normal fs-6 mt-2 mx-0 px-0" style="display: none;"><small><?php echo LANG['ap_ssid_requires_password']; ?></small></h6>
                      </div>
                    </div>
                  </div>
                  <div class="col-auto px-3 my-2" style="max-width: 20rem;">
                    <h2><?php echo LANG['website']; ?></h2>
                    <div class="row align-items-center mb-3">
                      <label class="form-label" for="site_password_input">
                        <?php echo LANG['new_password'] . ' (4+ ' . LANG['password_characters'] . ')'; ?>
                      </label>
                      <div class="col-8">
                        <input type="password" name="site_password" class="form-control" id="site_password_input" placeholder=""></input>
                      </div>
                      <div class="col-4">
                        <button type="submit" name="change_site_password" style="display: none;" id="change_site_password_submit_button" disabled></button><button class="btn btn-primary" id="change_site_password_button" disabled><?php echo LANG['change']; ?></button>
                      </div>
                    </div>
                  </div>
                  <div class="col-auto px-3 my-2" style="max-width: 20rem;">
                    <h2><?php echo LANG['network']; ?></h2>
                    <div class="row align-items-center mb-3">
                      <label class="form-label" for="country_code_select">
                        <?php echo LANG['country_code']; ?>
                      </label>
                      <div class="col-8">
                        <select name="country_code" class="form-select" id="country_code_select">
                          <?php foreach ($country_codes as $country_code) { ?>
                            <option value="<?php echo $country_code; ?>" <?php echo ($country_code == $current_country_code) ? 'selected' : ''; ?>><?php echo $country_code; ?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="col-4">
                        <button type="submit" name="change_country_code" style="display: none;" id="change_country_code_submit_button" disabled></button><button class="btn btn-primary" id="change_country_code_button" disabled><?php echo LANG['change']; ?></button>
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
require_once(TEMPLATES_DIR . '/js-cookie_js.php');

require_once(TEMPLATES_DIR . '/notifications_js.php');
require_once(TEMPLATES_DIR . '/monitoring_js.php');
?>

<script>
  const SETTINGS_FORM_ID = null;
  const USES_CAMERA = <?php echo USES_CAMERA ? 'true' : 'false'; ?>;
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
  const DETECT_FORM_CHANGES = false;

  const ACCESS_POINT_ACTIVE = <?php echo ACCESS_POINT_ACTIVE ? 'true' : 'false'; ?>;
  const AP_CHANNEL = <?php echo $current_ap_channel; ?>;
  const AP_SSID = '<?php echo $current_ap_ssid; ?>';
  const COUNTRY_CODE = '<?php echo $current_country_code; ?>';
</script>
<script src="js/style.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>
<script src="js/network_settings.js"></script>

</html>
