<?php
require_once(dirname(__DIR__) . '/config/site_config.php');
redirectIfLoggedOut('index.php');

waitForModeLock();
$mode = readCurrentMode($_DATABASE);

define('INFERENCE_MODEL', readValuesFromTable($_DATABASE, 'listen_settings', 'model', true));

define('HIDDEN_STYLE', 'style="display: none;"');

require_once(TEMPLATES_DIR . '/main.php');
?>

<link href="css/fill.css" rel="stylesheet">

<!DOCTYPE html>
<html class="fillview">

<head>
  <?php
  require_once(TEMPLATES_DIR . '/head_common.php');
  if (USES_CAMERA) {
    require_once(TEMPLATES_DIR . '/video-js_css.php');
  }
  ?>

  <link href="css/main.css" rel="stylesheet">

  <?php if (ACCESS_POINT_ACTIVE) { ?>
    <script>
      const SERVER_TIMESTAMP = <?php echo microtime(true); ?> * 1e3; // Milliseconds
      const CLIENT_TIMESTAMP = Date.now(); // Milliseconds
    </script>
    <script src="js/sync_time.js"></script>
  <?php } ?>

</head>

<body class="fillview" style="overflow: hidden;">
  <div class="d-flex flex-column fillview">
    <header>
      <?php
      require_once(TEMPLATES_DIR . '/navbar.php');
      require_once(TEMPLATES_DIR . '/confirmation_modal.php');
      ?>
    </header>

    <main id="main" class="d-flex flex-column flex-grow-1 justify-content-center overflow-auto">

      <div id="main_container" class="container-fluid h-100" style="display: none;">
        <div class="row h-100 align-items-center justify-content-center px-0">

          <div id="mode_content_listen" class="col-auto text-center text-bm" <?php echo ($mode != MODE_VALUES['listen']) ? HIDDEN_STYLE : ''; ?>>
            <svg id="listen_icon" class="bi mb-4 mt-3" style="width: 15vh; height: 15vh;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#bell-fill" />
            </svg>

            <p><?php echo LANG['listening_for_activity']; ?></p>

            <div class="row align-items-center justify-content-center mb-3 mt-5" id="listen_visualization_mode_box">
              <div class="col-auto fw-bold">
                <?php echo LANG['visualization']; ?>
              </div>
              <div class="col-auto">
                <form class="mb-0">
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="listen_control_radio" id="listen_none_radio" autocomplete="off" onclick="deactivateLiveResultsMode();" checked disabled>
                    <label class="form-check-label" for="listen_none_radio"><?php echo LANG['none']; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="listen_control_radio" id="listen_live_radio" autocomplete="off" onclick="activateLiveResultsMode();" disabled>
                    <label class="form-check-label" for="listen_live_radio"><?php echo LANG['audio_interpretation']; ?></label>
                  </div>
                </form>
              </div>
            </div>

            <div class="row justify-content-center mb-4">
              <div class="col-auto">
                <div class="card" id="listen_info_card" style="width: 18rem; display: none;">
                  <div class="card-header py-1 btn-bm d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#listen_info_card_body" onclick="toggleListenInfoCardHeaderIcon();">
                    <div style="width: 1rem;"></div>
                    <div><?php echo LANG['recording_details']; ?></div>
                    <svg class="bi" style="width: 1rem; height: 1rem;" fill="currentColor">
                      <use id="listen_info_card_header_icon" href="media/bootstrap-icons.svg#caret-down-fill" />
                    </svg>
                  </div>
                  <div id="listen_info_card_body" class="collapse">
                    <ul class="list-group list-group-flush">
                      <li class="list-group-item py-0">
                        <div class="row align-items-center">
                          <div class="col-8 py-1 text-start border-end"><?php echo LANG['foreground_contrast']; ?></div>
                          <div id="listen_info_card_contrast" class="col-4 py-1 text-end listen-info-card-item"></div>
                        </div>
                      </li>
                      <li class="list-group-item py-0">
                        <div class="row align-items-center">
                          <div class="col-8 py-1 text-start border-end"><?php echo LANG['background_level']; ?></div>
                          <div id="listen_info_card_bg_sound_level" class="col-4 py-1 text-end listen-info-card-item"></div>
                        </div>
                      </li>
                      <li class="list-group-item py-0">
                        <div class="row align-items-center">
                          <div class="col-8 py-1 text-start border-end"><?php echo LANG['delay']; ?></div>
                          <div id="listen_info_card_delay" class="col-4 py-1 text-end listen-info-card-item"></div>
                        </div>
                      </li>
                      <li class="list-group-item py-0">
                        <div class="row align-items-center">
                          <div class="col-8 py-1 text-start border-end"><?php echo LANG['interval']; ?></div>
                          <div id="listen_info_card_interval" class="col-4 py-1 text-end listen-info-card-item"></div>
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <?php if (INFERENCE_MODEL == 'sound_level_threshold') { ?>
              <div id="listen_animation_container" class="container ratio px-0 mb-3" style="position: relative; --bs-aspect-ratio: 20%; display: none;">
                <svg class="listen-animation-label bi" style="position: absolute; left: 0%; top: calc(50% - 2.5rem / 2); width: 2.5rem; height: 2.5rem;">
                  <use href="media/bootstrap-icons.svg#volume-off-fill" />
                </svg>
                <div id="listen_animation_line" style="position: absolute; left: 2.5rem; top: calc(50% - 1px); width: calc(100% - (2 * 2.5rem + 0.5rem)); height: 0%;">
                  <div id="listen_animation_indicator" style="position: absolute; width: 2.2rem; height: 2.2rem; top: -2.2rem; left: 0%;">
                    <div class="listen-animation-indicator" style="position: absolute; width: 100%; height: 100%; left: -50%; top: calc(100% / 2 - 70.71%); border-radius: 50%;"></div>
                    <div class="listen-animation-indicator" style="position: absolute; width: 70.72%; height: 35.36%; left: calc(-70.72% / 2); top: calc(100% - 35.36%); -webkit-clip-path: polygon(0% 0%, 50% 100%, 100% 0%); clip-path: polygon(0% 0%, 50% 100%, 100% 0%);"></div>
                    <svg class="listen-animation-label-bg bi" style="position: absolute; width: calc(100% - 30%); height: calc(100% - 30%); left: calc((30% - 100%) / 2 + 2%); top: calc((30% - 100%) / 2 - 70.71% + 100%);">
                      <use href="media/bootstrap-icons.svg#ear-fill" />
                    </svg>
                  </div>
                  <div id="listen_animation_context" style="position: absolute; width: 2rem; height: calc(0.87 * 2rem); top: 0%; left: 0%;">
                    <svg class="listen-animation-label bi" style="position: absolute; width: 100%; height: 100%; left: -50%; top: 0%;">
                      <use href="media/bootstrap-icons.svg#exclamation-triangle-fill" />
                    </svg>
                  </div>
                </div>
                <svg class="listen-animation-label bi" style="position: absolute; left: calc(100% - 2.5rem); top: calc(50% - 2.5rem / 2); width: 2.5rem; height: 2.5rem;">
                  <use href="media/bootstrap-icons.svg#volume-up-fill" />
                </svg>
              </div>
            <?php } else { ?>
              <div id="listen_animation_container" class="container ratio px-0 mb-3" style="position: relative; width: 25vh; --bs-aspect-ratio: 86.6%; display: none;">
                <div id="listen_animation_context" style="position: absolute; width: 100%; height: 100%; filter: brightness(3); background: linear-gradient(to top, rgba(20,39,59,1), rgba(20,39,59,0)), linear-gradient(to left, rgba(31,57,35,1), rgba(31,57,35,0) 80%), linear-gradient(to right, rgba(80,10,10,1), rgba(80,10,10,0) 80%); -webkit-clip-path: polygon(0% 0%, 50% 100%, 100% 0%); clip-path: polygon(0% 0%, 50% 100%, 100% 0%);"></div>
                <svg class="listen-animation-label-bg bi" style="position: absolute; left: 1.0rem; top: 0.2rem; width: 1.8rem; height: 1.8rem;">
                  <use href="media/bootstrap-icons.svg#emoji-frown-fill" />
                </svg>
                <svg class="listen-animation-label-bg bi" style="position: absolute; left: calc(100% - 1.8rem - 1.0rem); top: 0.2rem;  width: 1.8rem; height: 1.8rem;">
                  <use href="media/bootstrap-icons.svg#emoji-smile-fill" />
                </svg>
                <svg class="listen-animation-label-bg bi" style="position: absolute; left: calc(50% - 0.9rem); top: calc(100% - 1.8rem - 1.3rem); width: 1.8rem; height: 1.8rem;">
                  <use href="media/bootstrap-icons.svg#emoji-expressionless-fill" />
                </svg>
                <svg id="listen_animation_indicator" class="bi" style="position: absolute; width: 1.5rem; height: 1.5rem;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#bullseye" />
                </svg>
              </div>
            <?php } ?>
          </div>

          <div id="mode_content_audio" class="col-auto text-center text-bm" <?php echo ($mode != MODE_VALUES['audiostream']) ? HIDDEN_STYLE : ''; ?>>
            <div class="row justify-content-center align-items-center">
              <div class="col-sm-6 mb-5 text-center" style="display: none;">
                <p id="mode_content_audio_error" class="alert alert-danger text-center"></p>
                <a class="btn btn-secondary" href="main.php"><?php echo LANG['refresh_page']; ?></a>
              </div>
            </div>

            <svg id="audiostream_icon" class="bi mt-3" style="width: 15vh; height: 15vh;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#mic-fill" />
            </svg>

            <div class="row align-items-center justify-content-center mb-4 mt-5" id="audiostream_visualization_mode_box" style="display: none;">
              <div class="col-auto fw-bold">
                <?php echo LANG['visualization']; ?>
              </div>
              <div class="col-auto">
                <form class="mb-0">
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="audiostream_control_radio" id="audiostream_none_radio" autocomplete="off" onclick="switchAudioVisualizationModeTo(null);" checked>
                    <label class="form-check-label" for="audiostream_none_radio"><?php echo LANG['none']; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="audiostream_control_radio" id="audiostream_time_radio" autocomplete="off" onclick="switchAudioVisualizationModeTo('time');">
                    <label class="form-check-label" for="audiostream_time_radio"><?php echo LANG['wave']; ?></label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input type="radio" class="form-check-input" name="audiostream_control_radio" id="audiostream_frequency_radio" autocomplete="off" onclick="switchAudioVisualizationModeTo('frequency');">
                    <label class="form-check-label" for="audiostream_frequency_radio"><?php echo LANG['frequencies']; ?></label>
                  </div>
                </form>
              </div>
            </div>
            <div class="row align-items-center justify-content-center mb-2" id="audiostream_fftsize_range_box" style="display: none;">
              <div class="col-auto">
                <label class="form-label" for="audiostream_fftsize_range"><?php echo LANG['samples_per_frame']; ?></label>
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
            <div class="row justify-content-center mb-3" id="audiostream_player_box">
            </div>
          </div>

          <?php if (USES_CAMERA) { ?>
            <div id="mode_content_video" class="h-100 px-0" <?php echo ($mode != MODE_VALUES['videostream']) ? HIDDEN_STYLE : ''; ?>>
              <div id="mode_content_video_box" class="h-100">
              </div>
            </div>
          <?php } ?>

          <div id="mode_content_standby" class="col-auto text-center" <?php echo ($mode != MODE_VALUES['standby']) ? HIDDEN_STYLE : ''; ?>>
            <svg class="bi mb-4 mt-3 text-bm" style="width: 20vh; height: 20vh;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#moon-fill" />
            </svg>
            <p class="mb-5 text-bm"><?php echo LANG['device_in_standby']; ?></p>

            <div class="row mb-3 align-items-center justify-content-center">
              <div id="latency_container" class="col-auto text-center text-bm" style="min-width: 6rem; display: none;">
                <svg id="latency_icon" class="bi" style="width: 2rem; height: 2rem;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#stopwatch" />
                </svg>
                <p id="latency_text" class="mb-0"></p>
              </div>
              <div class="col-auto text-center">
                <p class="mb-0 text-bm"><?php echo LANG['connection']; ?></p>
                <button id="measure_bandwidth_button" class="btn btn-bm" disabled>
                  <svg class="bi" style="width: 2.5rem; height: 2.5rem;" fill="currentColor">
                    <use href="media/bootstrap-icons.svg#speedometer2" />
                  </svg>
                  <p class="mb-0"><?php echo LANG['test_connection']; ?></p>
                </button>
                <span id="measure_bandwidth_busy_spinner" class="spinner-border mt-3" style="display: none;"></span>
              </div>
              <div id="download_speed_container" class="col-auto text-center text-bm" style="min-width: 6rem; display: none;">
                <svg id="download_speed_icon" class="bi" style="width: 2rem; height: 2rem;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#cloud-arrow-down" />
                </svg>
                <p id="download_speed_text" class="mb-0"></p>
              </div>
            </div>

            <div id="connection_progress_bar_container" class="progress" style="display: none;">
              <div id="connection_progress_bar" class="progress-bar" style="width: 0%"></div>
            </div>

            <div class="row mb-5 align-items-start justify-content-center">
              <p id="connection_results_message" class="mb-3 text-bm" style="display: none;"><?php echo LANG['available_features']; ?></p>
              <div id="connection_results_listen" class="col-auto text-center text-bm" style="display: none;">
                <svg class="bi" style="width: 2rem; height: 2rem;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#bell-fill" />
                </svg>
                <p id="connection_results_listen_text" class="mb-0"></p>
              </div>
              <div id="connection_results_audio" class="col-auto text-center text-bm" style="display: none;">
                <svg class="bi" style="width: 2rem; height: 2rem;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#mic-fill" />
                </svg>
                <p id="connection_results_audio_text" class="mb-0"></p>
              </div>
              <?php if (USES_CAMERA) { ?>
                <div id="connection_results_video" class="col-auto text-center text-bm" style="display: none;">
                  <svg class="bi" style="width: 2rem; height: 2rem;" fill="currentColor">
                    <use href="media/bootstrap-icons.svg#camera-video-fill" />
                  </svg>
                  <p id="connection_results_video_text" class="mb-0"></p>
                </div>
              <?php } ?>
            </div>
          </div>

          <div id="mode_content_waiting" class="col-auto" style="display: none;">
            <span class="spinner-grow text-bm"></span>
          </div>

          <div id="mode_content_error" class="col-auto" style="display: none;">
            <div class="container">
              <div class="row justify-content-center">
                <div class="col-auto text-center">
                  <p id="mode_content_error_message" class="alert alert-danger text-center"></p>
                  <a class="btn btn-secondary" href="main.php"><?php echo LANG['refresh_page']; ?></a>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <footer class="d-flex flex-grow-0 flex-shrink-1 justify-content-center">
      <div id="footer_container" class="btn-group" data-toggle="buttons" style="display: none;">
        <?php
        createModeRadioButton($mode, 'standby', LANG['standby'], 'moon');
        createModeRadioButton($mode, 'listen', LANG['notify'], 'bell');
        createModeRadioButton($mode, 'audiostream', LANG['listen'], 'mic');
        if (USES_CAMERA) {
          createModeRadioButton($mode, 'videostream', LANG['observe'], 'camera-video');
        }
        ?>
      </div>
    </footer>
  </div>

  <?php
  require_once(TEMPLATES_DIR . '/bootstrap_js.php');
  if (USES_CAMERA) {
    require_once(TEMPLATES_DIR . '/video-js_js.php');
  }
  require_once(TEMPLATES_DIR . '/hls-js_js.php');
  require_once(TEMPLATES_DIR . '/anime_js.php');
  require_once(TEMPLATES_DIR . '/nosleep-js_js.php');
  require_once(TEMPLATES_DIR . '/jquery_js.php');
  require_once(TEMPLATES_DIR . '/js-cookie_js.php');

  require_once(TEMPLATES_DIR . '/notifications_js.php');
  require_once(TEMPLATES_DIR . '/monitoring_js.php');
  ?>

  <script>
    const USES_CAMERA = <?php echo USES_CAMERA ? 'true' : 'false'; ?>;
    const ACCESS_POINT_ACTIVE = <?php echo ACCESS_POINT_ACTIVE ? 'true' : 'false'; ?>;
    const STANDBY_MODE = <?php echo MODE_VALUES['standby']; ?>;
    const LISTEN_MODE = <?php echo MODE_VALUES['listen']; ?>;
    const AUDIOSTREAM_MODE = <?php echo MODE_VALUES['audiostream']; ?>;
    const VIDEOSTREAM_MODE = <?php echo (USES_CAMERA) ? MODE_VALUES['videostream'] : 'null'; ?>;
    const INITIAL_MODE = <?php echo $mode; ?>;

    const SETTING_MODEL = '<?php echo INFERENCE_MODEL; ?>';
    const SETTING_MIN_SOUND_CONTRAST = <?php echo readValuesFromTable($_DATABASE, 'listen_settings', 'min_sound_contrast', true); ?>;
    const SETTING_AUTOPLAY_ON_NOTIFY = <?php echo readValuesFromTable($_DATABASE, 'listen_settings', 'autoplay_on_notify', true); ?>;
    const SETTING_SAMPLING_RATE = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'sampling_rate', true); ?>;
    const SETTING_VOLUME = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'volume', true); ?>;
    const min_frequency = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'min_frequency', true); ?>;
    const max_frequency = <?php echo readValuesFromTable($_DATABASE, 'audiostream_settings', 'max_frequency', true); ?>;
    const SETTING_MIN_FREQUENCY = Math.min(min_frequency, max_frequency);
    const SETTING_MAX_FREQUENCY = Math.max(min_frequency, max_frequency);
  </script>
  <script src="js/style.js"></script>
  <script src="js/confirmation_modal.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/listen.js"></script>
  <script src="js/navbar_main.js"></script>
  <script src="js/audio_video.js"></script>
  <script src="js/audio.js"></script>
  <?php if (USES_CAMERA) { ?>
    <script src="js/video.js"></script>
  <?php } ?>
  <script src="js/main.js"></script>
  <script src="js/network.js"></script>

  <div style="position: relative; top: 100%; left: 0; width: 100%; height: 10vh; overflow: hidden;"></div>
</body>

</html>
