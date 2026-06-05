<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');
require_once(SRC_DIR . '/settings.php');

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
        <div class="container">
          <h1 class="my-4"><?php echo LANG['listen_settings']; ?></h1>
          <form id="listen_settings_form" action="" method="post">
            <div class="row">
              <?php
              $input_scripts = generateInputs($setting_type, $grouped_values, [], []);
              ?>
            </div>
            <div class="my-4">
              <button type="submit" name="submit" class="btn btn-primary"><?php echo LANG['submit']; ?></button>
              <button type="submit" name="reset" style="display: none;" id="reset_submit_button" disabled></button><input type="button" class="btn btn-warning ms-2" id="reset_button" value="<?php echo LANG['reset']; ?>" />
            </div>
          </form>

          <!-- Push Notifications Section -->
          <hr class="my-4">
          <div class="mb-4">
            <h5 class="mb-3"><?php echo LANG['push_notifications']; ?></h5>
            <div id="push_status_box">
              <div id="push_unavailable_msg" style="display:none;" class="alert alert-secondary"></div>
              <div id="push_toggle_box" style="display:none;" class="d-flex align-items-center gap-3">
                <button id="push_toggle_btn" class="btn btn-outline-primary" onclick="handlePushToggle()">
                  <?php echo LANG['push_enable']; ?>
                </button>
                <span id="push_state_label" class="text-bm"></span>
              </div>
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
?>

<script src="js/confirmation_modal.js"></script>

<?php
require_once(TEMPLATES_DIR . '/notifications_js.php');
require_once(TEMPLATES_DIR . '/monitoring_js.php');
?>

<script>
  const SETTINGS_FORM_ID = 'listen_settings_form';
  const SETTINGS_EDITED = <?php echo $settings_edited ? 'true' : 'false'; ?>;
  const USES_CAMERA = <?php echo USES_CAMERA ? 'true' : 'false'; ?>;
  const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
  const SITE_MODE = <?php echo MODE_VALUES[$setting_type]; ?>;
  const INITIAL_MODE = <?php echo $mode; ?>;
  const DETECT_FORM_CHANGES = true;
</script>
<script src="js/style.js"></script>
<script src="js/jquery_utils.js"></script>
<script src="js/settings.js"></script>
<script src="js/navbar.js"></script>
<script src="js/navbar_settings.js"></script>
<script>
  <?php generateBehavior($setting_type); ?>
</script>
<?php if ($input_scripts != null) { ?>
  <script>
    <?php foreach ($input_scripts as $script) {
      echo $script . "\n";
    } ?>
  </script>
<?php } ?>
<script>
  $(function() {
    captureElementState(SETTINGS_FORM_ID);
    initPushToggle();
  });
</script>

<script>
const LANG_PUSH_ENABLE        = <?php echo json_encode(LANG['push_enable']); ?>;
const LANG_PUSH_DISABLE       = <?php echo json_encode(LANG['push_disable']); ?>;
const LANG_PUSH_ENABLED       = <?php echo json_encode(LANG['push_enabled']); ?>;
const LANG_PUSH_DISABLED      = <?php echo json_encode(LANG['push_disabled']); ?>;
const LANG_PUSH_NOT_SUPPORTED = <?php echo json_encode(LANG['push_not_supported']); ?>;
const LANG_PUSH_NEEDS_HTTPS   = <?php echo json_encode(LANG['push_requires_https']); ?>;
const LANG_PUSH_NO_INTERNET   = <?php echo json_encode(LANG['push_no_internet']); ?>;

function initPushToggle() {
    const unavail = document.getElementById('push_unavailable_msg');
    const box     = document.getElementById('push_toggle_box');
    const btn     = document.getElementById('push_toggle_btn');
    const lbl     = document.getElementById('push_state_label');

    if (!pushNotificationsAvailable()) {
        unavail.textContent = location.protocol !== 'https:'
            ? LANG_PUSH_NEEDS_HTTPS
            : LANG_PUSH_NOT_SUPPORTED;
        unavail.style.display = '';
        return;
    }

    if (pushRequiresInternetHint()) {
        unavail.textContent = LANG_PUSH_NO_INTERNET;
        unavail.className = 'alert alert-warning';
        unavail.style.display = '';
    }

    box.style.display = '';

    registerServiceWorker().then(function() {
        return isPushSubscribed();
    }).then(function(subscribed) {
        updatePushButton(subscribed);
    }).catch(function() {});

    function updatePushButton(subscribed) {
        btn.textContent = subscribed ? LANG_PUSH_DISABLE : LANG_PUSH_ENABLE;
        btn.className   = subscribed ? 'btn btn-outline-danger' : 'btn btn-outline-primary';
        lbl.textContent = subscribed ? LANG_PUSH_ENABLED : LANG_PUSH_DISABLED;
    }

    window._updatePushButton = updatePushButton;
}

function handlePushToggle() {
    isPushSubscribed().then(function(subscribed) {
        if (subscribed) {
            return unsubscribeFromPush().then(function() {
                window._updatePushButton && window._updatePushButton(false);
            });
        } else {
            return fetch('push_subscribe.php')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.vapidPublicKey) throw new Error('No VAPID key');
                    return subscribeToPush(data.vapidPublicKey);
                })
                .then(function() {
                    window._updatePushButton && window._updatePushButton(true);
                });
        }
    }).catch(function(err) {
        console.error('Push toggle error:', err);
    });
}
</script>

</html>
