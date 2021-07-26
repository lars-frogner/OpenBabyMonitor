<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

$mode = readCurrentMode($_DATABASE);
define('HIDDEN_STYLE', 'style="display: none;"');
?>

<!DOCTYPE html>
<html>

<head>
  <?php
  require_once(TEMPLATES_PATH . '/head_common.php');
  require_once(TEMPLATES_PATH . '/video-js_css.php');
  ?>
</head>

<body class="mb-0">
  <div class="d-flex flex-column overflow-hidden min-vh-100 vh-100">
    <header>
      <?php require_once(TEMPLATES_PATH . '/navbar.php'); ?>
    </header>

    <main id="main" class="d-flex flex-column flex-grow-1 overflow-auto justify-content-center">

      <div id="mode_content_listen" <?php echo ($mode != LISTEN_MODE) ? HIDDEN_STYLE : '' ?>>
        <div class="d-flex flex-row justify-content-center">
          <p>Listen</p>
        </div>
      </div>

      <div id="mode_content_audio" <?php echo ($mode != AUDIOSTREAM_MODE) ? HIDDEN_STYLE : '' ?>>
        <div class="d-flex flex-row justify-content-center">
          <p>Audio</p>
        </div>
      </div>

      <div id="mode_content_video" class="h-100" <?php echo ($mode != VIDEOSTREAM_MODE) ? HIDDEN_STYLE : '' ?>>
        <div id="mode_content_video_box" class="d-flex flex-row justify-content-center h-100">
        </div>
      </div>

      <div id="mode_content_standby" <?php echo ($mode != STANDBY_MODE) ? HIDDEN_STYLE : '' ?>>
        <div class="d-flex flex-row justify-content-center">
          <p>Standby</p>
        </div>
      </div>

      <div id="mode_content_waiting" <?php echo HIDDEN_STYLE; ?>>
        <div class="d-flex flex-row justify-content-center">
          <span class="spinner-grow text-dark"></span>
        </div>
      </div>

      <div id="mode_content_error" <?php echo HIDDEN_STYLE; ?>>
        <div class="d-flex flex-row justify-content-center text-center">
          <span id="mode_content_error_message" class="alert alert-danger">Error
          </span>
          <a class=" btn btn-secondary" href="main.php">Refresh</a>
        </div>
      </div>

    </main>

    <footer class="d-flex flex-grow-0 flex-shrink-1 justify-content-center">
      <div class="btn-group">
        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_listen" autocomplete="off" onchange="requestModeChange(this);" <?php echo ($mode == LISTEN_MODE) ? 'checked' : '' ?> value="<?php echo LISTEN_MODE ?>">
        <label class="btn btn-outline-primary" for="mode_radio_listen">Listen</label>

        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_audio" autocomplete="off" onchange="requestModeChange(this);" <?php echo ($mode == AUDIOSTREAM_MODE) ? 'checked' : '' ?> value="<?php echo AUDIOSTREAM_MODE ?>">
        <label class="btn btn-outline-primary" for="mode_radio_audio">Audio</label>

        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_video" autocomplete="off" onchange="requestModeChange(this);" <?php echo ($mode == VIDEOSTREAM_MODE) ? 'checked' : '' ?> value="<?php echo VIDEOSTREAM_MODE ?>">
        <label class="btn btn-outline-primary" for="mode_radio_video">Video</label>

        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_standby" autocomplete="off" onchange="requestModeChange(this);" <?php echo ($mode == STANDBY_MODE) ? 'checked' : '' ?> value="<?php echo STANDBY_MODE ?>">
        <label class="btn btn-outline-primary" for="mode_radio_standby">Standby</label>
      </div>
    </footer>
  </div>

  <?php
  require_once(TEMPLATES_PATH . '/bootstrap_js.php');
  require_once(TEMPLATES_PATH . '/video-js_js.php');
  ?>

  <script src="js/main.js"></script>

  <?php
  if ($mode == VIDEOSTREAM_MODE) {
  ?><script>
      create_video_element();
    </script><?php
            }
              ?>

</body>

</html>
