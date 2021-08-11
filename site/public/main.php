<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

waitForModeLock();
$mode = readCurrentMode($_DATABASE);
define('HIDDEN_STYLE', 'style="display: none;"');
?>

<!DOCTYPE html>
<html>

<head>
  <script>
    const SERVER_TIMESTAMP = <?php echo microtime(true); ?> * 1e3; // Milliseconds
    const CLIENT_TIMESTAMP = Date.now(); // Milliseconds
  </script>
  <script src="js/sync_time.js"></script>

  <?php
  require_once(TEMPLATES_PATH . '/head_common.php');
  require_once(TEMPLATES_PATH . '/video-js_css.php');
  ?>
</head>

<body>
  <div class="d-flex flex-column min-vh-100 vh-100">
    <header>
      <?php
      require_once(TEMPLATES_PATH . '/navbar.php');
      require_once(TEMPLATES_PATH . '/confirmation_modal.php');
      ?>
    </header>

    <main id="main" class="d-flex flex-column flex-grow-1 justify-content-center overflow-auto">

      <div class="container-fluid h-100">
        <div class="row h-100 align-items-center justify-content-center px-0">

          <div id="mode_content_listen" class="col-auto" <?php echo ($mode != MODE_VALUES['listen']) ? HIDDEN_STYLE : '' ?>>
            Listen
          </div>

          <div id="mode_content_audio" class="col-auto" <?php echo ($mode != MODE_VALUES['audiostream']) ? HIDDEN_STYLE : '' ?>>
            <div id="mode_content_audio_box" class="row justify-content-center">
            </div>
          </div>

          <div id="mode_content_video" class="h-100 px-0" <?php echo ($mode != MODE_VALUES['videostream']) ? HIDDEN_STYLE : '' ?>>
            <div id="mode_content_video_box" class="h-100">
            </div>
          </div>

          <div id="mode_content_standby" class="col-auto" <?php echo ($mode != MODE_VALUES['standby']) ? HIDDEN_STYLE : '' ?>>
            Standby
          </div>

          <div id="mode_content_waiting" class="col-auto" <?php echo HIDDEN_STYLE; ?>>
            <span class="spinner-grow text-dark"></span>
          </div>

          <div id="mode_content_error" class="col-auto" <?php echo HIDDEN_STYLE; ?>>
            <div class="container">
              <div class="row">
                <span id="mode_content_error_message" class="alert alert-danger text-center">Error
                </span>
                <a class=" btn btn-secondary" href="main.php">Refresh</a>
              </div>
            </div>
          </div>

        </div>
      </div>

    </main>

    <footer class="d-flex flex-grow-0 flex-shrink-1 justify-content-center">
      <div class="btn-group">
        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_listen" autocomplete="off" disabled <?php echo ($mode == MODE_VALUES['listen']) ? 'checked' : '' ?> value="<?php echo MODE_VALUES['listen'] ?>">
        <label class="btn btn-outline-primary" for="mode_radio_listen">Listen</label>

        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_audio" autocomplete="off" disabled <?php echo ($mode == MODE_VALUES['audiostream']) ? 'checked' : '' ?> value="<?php echo MODE_VALUES['audiostream'] ?>">
        <label class="btn btn-outline-primary" for="mode_radio_audio">Audio</label>

        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_video" autocomplete="off" disabled <?php echo ($mode == MODE_VALUES['videostream']) ? 'checked' : '' ?> value="<?php echo MODE_VALUES['videostream'] ?>">
        <label class="btn btn-outline-primary" for="mode_radio_video">Video</label>

        <input type="radio" class="btn-check" name="mode_radio" id="mode_radio_standby" autocomplete="off" disabled <?php echo ($mode == MODE_VALUES['standby']) ? 'checked' : '' ?> value="<?php echo MODE_VALUES['standby'] ?>">
        <label class="btn btn-outline-primary" for="mode_radio_standby">Standby</label>
      </div>
    </footer>
  </div>

  <?php
  require_once(TEMPLATES_PATH . '/bootstrap_js.php');
  require_once(TEMPLATES_PATH . '/video-js_js.php');
  require_once(TEMPLATES_PATH . '/jquery_js.php');
  ?>

  <script>
    const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
    const AUDIOSTREAM_MODE = <?php echo MODE_VALUES['audiostream']; ?>;
    const VIDEOSTREAM_MODE = <?php echo MODE_VALUES['videostream']; ?>;
    const INITIAL_MODE = <?php echo $mode; ?>;

    const AUDIO_SRC = <?php echo '\'http://' . SERVER_IP . ':' . getenv('BM_MICSTREAM_PORT') . getenv('BM_MICSTREAM_ENDPOINT') . '\''; ?>;
  </script>
  <script src="js/confirmation_modal.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/navbar_main.js"></script>
  <script src="js/audio.js"></script>
  <script src="js/video.js"></script>
  <script src="js/main.js"></script>

</body>

</html>
