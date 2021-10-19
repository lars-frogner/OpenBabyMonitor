<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

require_once(TEMPLATES_DIR . '/server_settings.php');

$connection_succeeded = null;
if (isset($_POST['connect'])) {
  $ssid = $_POST['available_networks'];
  $password = isset($_POST['password']) ? $_POST['password'] : null;
  $remember = isset($_POST['remember']);
  $connection_succeeded = connectToNetwork($_DATABASE, $ssid, $password, $remember);
} elseif (isset($_POST['forget'])) {
  $ssid = $_POST['known_networks'];
  removeKnownNetwork($_DATABASE, $ssid);
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

  <main>
    <div <?php echo ($connection_succeeded === true) ? '' : 'style="display: none;"'; ?>>
      <div class="d-flex flex-row justify-content-center">
        <div class="d-flex flex-column">
          <span class="alert alert-success text-center">Gratulerer, tilkoblingen var vellykket!</span>
        </div>
      </div>
    </div>
    <div <?php echo ($connection_succeeded === false) ? '' : 'style="display: none;"'; ?>>
      <div class="d-flex flex-row justify-content-center">
        <div class="d-flex flex-column">
          <span class="alert alert-danger text-center">Tilkobling mislyktes, vennligst prøv igjen.</span>
        </div>
      </div>
    </div>
    <div style="display: none;" id="switching_network_info">
      <div class="d-flex flex-row justify-content-center">
        <div class="d-flex flex-column text-center">
          <span class="alert alert-warning">Bytter nettverk, tilkoblingen vil bli brutt.</span>
          <a class="btn btn-secondary" href="server_settings.php">Forny siden</a>
        </div>
      </div>
    </div>
    <div class="container" id="server_settings_form_container">
      <h1 class="my-4">Nettverksinnstillinger</h1>
      <form id="server_settings_form" action="" method="post">
        <div class="row mb-3">
          <div class="col-auto">
            <div class="form-group">
              <label class="form-label h3" for="available_networks">Tilgjengelige nettverk</label>
              <?php generateAvailableNetworksSelect($available_networks, $known_networks, $connected_network, 'available_networks'); ?>
            </div>
            <div class="row form-group align-items-center my-3">
              <div class="col-auto">
                <label class="col-form-label" for="password_input">Passord</label>
              </div>
              <div class="col-auto">
                <input type="password" name="password" class="form-control" placeholder="8-63 tegn" id="password_input" disabled>
              </div>
              <div class="col-auto">
                <div class="form-check">
                  <input type="checkbox" name="remember" class="form-check-input" id="remember_check" disabled>
                  <label class="form-check-label" for="remember_check">Husk</label>
                </div>
              </div>
              <div class="col-auto mt-3">
                <button type="submit" name="connect" style="display: none;" id="connect_submit_button"></button><button class="btn btn-secondary" id="connect_button" disabled>Koble til</button>
                <a href="activate_ap_mode.php" style="display: none;" id="disconnect_submit_button"></a><button class="btn btn-secondary" id="disconnect_button" disabled>Koble fra</button>
              </div>
            </div>
          </div>
          <div class="col-auto">
            <label class="form-label h3" for="known_networks">Kjente nettverk</label>
            <?php generateKnownNetworksSelect($known_networks, 'known_networks'); ?>
            <div class="form-group my-3">
              <button type="submit" name="forget" style="display: none;" id="forget_submit_button"></button><button class="btn btn-secondary" id="forget_button" disabled>Glem</button>
            </div>
          </div>
        </div>
      </form>
    </div>
  </main>
</body>

<?php
require_once(TEMPLATES_DIR . '/bootstrap_js.php');
?>

<script>
  const SETTINGS_FORM_ID = 'server_settings_form';
  const SETTINGS_FORM_CONTAINER_ID = 'server_settings_form_container';
  const SWITCHING_INFO_ID = 'switching_network_info';
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
  const AVAILABLE_NETWORKS_SELECT_ID = 'available_networks';
  const KNOWN_NETWORKS_SELECT_ID = 'known_networks';
  const PASSWORD_INPUT_ID = 'password_input';
  const REMEMBER_CHECK_ID = 'remember_check';
  const CONNECT_SUBMIT_BUTTON_ID = 'connect_submit_button';
  const CONNECT_BUTTON_ID = 'connect_button';
  const DISCONNECT_SUBMIT_BUTTON_ID = 'disconnect_submit_button';
  const DISCONNECT_BUTTON_ID = 'disconnect_button';
  const FORGET_SUBMIT_BUTTON_ID = 'forget_submit_button';
  const FORGET_BUTTON_ID = 'forget_button';
  const DETECT_FORM_CHANGES = false;
</script>
<script src="js/jquery_utils.js"></script>
<script src="js/confirmation_modal.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>
<script src="js/server_settings.js"></script>
<script>
  $(function() {
    captureElementState(SETTINGS_FORM_ID);
  });
</script>

</html>
