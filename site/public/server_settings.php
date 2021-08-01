<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

$mode = readCurrentMode($_DATABASE);
$networks = obtainWirelessScanResults();
require_once(TEMPLATES_PATH . '/server_settings.php');
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
      <h1 class="my-4">Enhetsinnstillinger</h1>
      <form id="server_settings_form" action="" method="post">
        <div class="row mb-3">
          <div class="col">
            <div class="form-group">
              <label class="form-label h3" for="available_networks">Tilgjengelige nettverk</label>
              <?php generateAvailableNetworksSelect($networks, 'available_networks'); ?>
            </div>
            <div class="row form-group align-items-center my-3">
              <div class="col-auto">
                <label class="col-form-label" for="password_input">Passord</label>
              </div>
              <div class="col-auto">
                <input type="password" class="form-control" id="password_input">
              </div>
              <div class="col-auto">
                <button type="submit" name="connect" style="display: none;" id="connect_submit_button"></button><button class="btn btn-primary" id="connect_button">Koble til</button>
                <button type="submit" name="disconnect" style="display: none;" id="disconnect_submit_button"></button><button class="btn btn-warning" id="disconnect_button">Koble fra</button>
              </div>
            </div>
          </div>
          <div class="col">
            <label class="form-label h3" for="known_networks">Kjente nettverk</label>
            <select class="form-select" size="3" id="known_networks">
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
            </select>
            <div class="form-group my-3">
              <button type="submit" name="forget" style="display: none;" id="forget_submit_button"></button><button class="btn btn-danger" id="forget_button">Glem</button>
            </div>
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
  const SETTINGS_FORM_ID = 'server_settings_form';
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
  const AVAILABLE_NETWORKS_SELECT_ID = 'available_networks';
  const KNOWN_NETWORKS_SELECT_ID = 'known_networks';
  const PASSWORD_INPUT_ID = 'password_input';
  const CONNECT_SUBMIT_BUTTON_ID = 'connect_submit_button';
  const CONNECT_BUTTON_ID = 'connect_button';
  const DISCONNECT_SUBMIT_BUTTON_ID = 'disconnect_submit_button';
  const DISCONNECT_BUTTON_ID = 'disconnect_button';
  const FORGET_SUBMIT_BUTTON_ID = 'forget_submit_button';
  const FORGET_BUTTON_ID = 'forget_button';
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
