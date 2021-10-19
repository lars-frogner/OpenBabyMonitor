<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

waitForModeLock();
$mode = readCurrentMode($_DATABASE);
define('HIDDEN_STYLE', 'style="display: none;"');
require_once(TEMPLATES_PATH . '/main.php');
?>

<!DOCTYPE html>
<html>

<head>
  <?php
  require_once(TEMPLATES_PATH . '/head_common.php');
  require_once(TEMPLATES_PATH . '/video-js_css.php');
  ?>

  <style>
    .checked {
      display: none;
    }

    .active .checked {
      display: inline-block;
    }

    .active .unchecked {
      display: none;
    }
  </style>

  <script>
    const SERVER_TIMESTAMP = <?php echo microtime(true); ?> * 1e3; // Milliseconds
    const CLIENT_TIMESTAMP = Date.now(); // Milliseconds
  </script>
  <script src="js/sync_time.js"></script>
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

          <div id="mode_content_listen" class="col-auto text-center text-bm" <?php echo ($mode != MODE_VALUES['listen']) ? HIDDEN_STYLE : '' ?>>
            <svg id="listen_inactive_icon" class="bi mb-5" style="width: 25vh; height: 25vh; display: none;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#bell" />
            </svg>
            <svg id="listen_active_icon" class="bi mb-5" style="width: 25vh; height: 25vh; display: none;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#bell-fill" />
            </svg>
            <p id="listen_notifications_msg"></p>
            <button id="listen_enable_notifications_button" class="btn btn-primary" <?php echo HIDDEN_STYLE ?>>Tillat varsler</button>
            <a id="listen_redirect_secure_link" class="btn btn-primary" href="https://<?php echo "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" <?php echo HIDDEN_STYLE ?>>Gå til kryptert versjon av siden</a>
          </div>

          <div id="mode_content_audio" class="col w-100" <?php echo ($mode != MODE_VALUES['audiostream']) ? HIDDEN_STYLE : '' ?>>
            <div class="row justify-content-center align-items-center">
              <div class="col-sm-6 mb-5 text-center" <?php echo HIDDEN_STYLE ?>>
                <p id="mode_content_audio_error" class="alert alert-danger text-center"></p>
                <a class="btn btn-secondary" href="main.php">Forny siden</a>
              </div>
            </div>
            <div class="row align-items-center justify-content-center" id="audiostream_visualization_mode_box" <?php echo HIDDEN_STYLE; ?>>
              <div class="col-auto fw-bold">
                Visualisering
              </div>
              <div class="col-auto">
                <form>
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="audiostream_control_radio" id="audiostream_none_radio" onclick="switchAudioVisualizationModeTo(null);" checked>
                    <label class="form-check-label" for="none_radio">Ingen</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="audiostream_control_radio" id="audiostream_time_radio" onclick="switchAudioVisualizationModeTo('time');">
                    <label class="form-check-label" for="time_radio">Bølge</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="audiostream_control_radio" id="audiostream_frequency_radio" onclick="switchAudioVisualizationModeTo('frequency');">
                    <label class="form-check-label" for="frequency_radio">Frekvenser</label>
                  </div>
                </form>
              </div>
            </div>
            <div class="row align-items-center justify-content-center" id="audiostream_fftsize_range_box" <?php echo HIDDEN_STYLE; ?>>
              <div class="col-auto">
                <label class="form-label" for="audiostream_fftsize_range">Målinger per bilde</label>
              </div>
              <div class="col-auto">
                <div class="row flex-nowrap">
                  <div class="col-10">
                    <input type="range" class="form-range" value="7" min="5" max="13" step="1" id="audiostream_fftsize_range" oninput="$('#audiostream_fftsize_range_value').html(2**this.value); switchFFTSizePowerTo(this.value);">
                  </div>
                  <div class="col-1">
                    <output id="audiostream_fftsize_range_value">128</output>
                  </div>
                </div>
              </div>
            </div>
            <div class="row justify-content-center" id="audiostream_canvas_box">
            </div>
            <div class="row justify-content-center" id="audiostream_player_box">
            </div>
          </div>

          <div id="mode_content_video" class="h-100 px-0" <?php echo ($mode != MODE_VALUES['videostream']) ? HIDDEN_STYLE : '' ?>>
            <div id="mode_content_video_box" class="h-100">
            </div>
          </div>

          <div id="mode_content_standby" class="col-auto text-center" <?php echo ($mode != MODE_VALUES['standby']) ? HIDDEN_STYLE : '' ?>>
            <svg class="bi mb-5 text-bm" style="width: 25vh; height: 25vh" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#moon-fill" />
            </svg>
            <p class="mb-5 text-bm">Enheten er i hvilemodus</p>

            <div class="row mb-3 align-items-center justify-content-center">
              <div id="latency_container" class="col-auto text-center text-bm" style="min-width: 6em; display: none">
                <svg id="latency_icon" class="bi" style="width: 2em; height: 2em" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#stopwatch" />
                </svg>
                <p id="latency_text" class="mb-0"></p>
              </div>
              <div class="col-auto text-center">
                <p class="mb-0 text-bm">Forbindelse</p>
                <button id="measure_bandwidth_button" class="btn btn-bm" data-toggle="tooltip" data-placement="top" title="Mål ned- og opplastningshastighet" disabled>
                  <svg class="bi" style="width: 2.5em; height: 2.5em" fill="currentColor">
                    <use xlink:href="media/bootstrap-icons.svg#speedometer2" />
                  </svg>
                  <p class="mb-0">Test</p>
                </button>
                <span id="measure_bandwidth_busy_spinner" class="spinner-border mt-3" <?php echo HIDDEN_STYLE; ?>></span>
              </div>
              <div id="download_speed_container" class="col-auto text-center text-bm" style="min-width: 6em; display: none">
                <svg id="download_speed_icon" class="bi" style="width: 2em; height: 2em" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#cloud-arrow-down" />
                </svg>
                <p id="download_speed_text" class="mb-0"></p>
              </div>
            </div>

            <div id="connection_progress_bar_container" class="progress" <?php echo HIDDEN_STYLE; ?>>
              <div id="connection_progress_bar" class="progress-bar" style="width: 0%"></div>
            </div>

            <div class="row mb-5 align-items-start justify-content-center">
              <p id="connection_results_message" class="mb-3 text-bm" <?php echo HIDDEN_STYLE; ?>>Tilgjengelige funksjoner</p>
              <div id="connection_results_listen" class="col-auto text-center text-bm" <?php echo HIDDEN_STYLE; ?>>
                <svg class="bi" style="width: 2em; height: 2em" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#bell-fill" />
                </svg>
                <p id="connection_results_listen_text" class="mb-0"></p>
              </div>
              <div id="connection_results_audio" class="col-auto text-center text-bm" <?php echo HIDDEN_STYLE; ?>>
                <svg class="bi" style="width: 2em; height: 2em" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#mic-fill" />
                </svg>
                <p id="connection_results_audio_text" class="mb-0"></p>
              </div>
              <div id="connection_results_video" class="col-auto text-center text-bm" <?php echo HIDDEN_STYLE; ?>>
                <svg class="bi" style="width: 2em; height: 2em" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#camera-video-fill" />
                </svg>
                <p id="connection_results_video_text" class="mb-0"></p>
              </div>
            </div>
          </div>

          <div id="mode_content_waiting" class="col-auto" <?php echo HIDDEN_STYLE; ?>>
            <span class="spinner-grow text-bm"></span>
          </div>

          <div id="mode_content_error" class="col-auto" <?php echo HIDDEN_STYLE; ?>>
            <div class="container">
              <div class="row ">
                <div class="col-sm-6 text-center">
                  <p id="mode_content_error_message" class="alert alert-danger text-center"></p>
                  <a class="btn btn-secondary" href="main.php">Forny siden</a>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <footer class="d-flex flex-grow-0 flex-shrink-1 justify-content-center">
      <div class="btn-group" data-toggle="buttons">
        <?php
        createModeRadioButton($mode, 'listen', 'Varsle', 'bell');
        createModeRadioButton($mode, 'audiostream', 'Lytte', 'mic');
        createModeRadioButton($mode, 'videostream', 'Se', 'camera-video');
        createModeRadioButton($mode, 'standby', 'Hvile', 'moon');
        ?>
      </div>
    </footer>
  </div>

  <?php
  require_once(TEMPLATES_PATH . '/bootstrap_js.php');
  require_once(TEMPLATES_PATH . '/video-js_js.php');
  require_once(TEMPLATES_PATH . '/hls-js_js.php');
  require_once(TEMPLATES_PATH . '/jquery_js.php');
  ?>

  <script>
    const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
    const LISTEN_MODE = <?php echo MODE_VALUES['listen']; ?>;
    const AUDIOSTREAM_MODE = <?php echo MODE_VALUES['audiostream']; ?>;
    const VIDEOSTREAM_MODE = <?php echo MODE_VALUES['videostream']; ?>;
    const INITIAL_MODE = <?php echo $mode; ?>;

    const USES_SECURE_PROTOCOL = <?php echo USES_SECURE_PROTOCOL ? 'true' : 'false' ?>;

    const SETTING_SAMPLING_RATE = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'sampling_rate', true); ?>;
    const SETTING_VOLUME = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'volume', true); ?>;
    const min_frequency = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'min_frequency', true); ?>;
    const max_frequency = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'max_frequency', true); ?>;
    const SETTING_MIN_FREQUENCY = Math.min(min_frequency, max_frequency);
    const SETTING_MAX_FREQUENCY = Math.max(min_frequency, max_frequency);
  </script>
  <script src="js/confirmation_modal.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/listen.js"></script>
  <script src="js/navbar_main.js"></script>
  <script src="js/audio_video.js"></script>
  <script src="js/audio.js"></script>
  <script src="js/video.js"></script>
  <script src="js/main.js"></script>
  <script src="js/network.js"></script>

</body>

</html>
