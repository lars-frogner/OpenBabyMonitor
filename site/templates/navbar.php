<nav id="navbar" class="navbar navbar-expand-md navbar-<?php echo COLOR_SCHEME; ?>" style="display: none;">
  <div class="container-fluid">
    <a id="title_nav_link" class="navbar-brand" href="main.php"><?php echo LANG['nav_title']; ?></a>
    <button id="navbar_toggler" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar_entries">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbar_entries">
      <ul class="navbar-nav w-100 mb-0">
        <li class="nav-item">
          <a id="modes_nav_link" class="nav-link<?php echo LOCATION == 'main' ? ' active' : ''; ?> d-flex align-items-center disabled" href="main.php">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#menu-button" />
            </svg>
            <?php echo LANG['nav_modes']; ?>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center disabled" href="#" data-bs-toggle="dropdown">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#gear-fill" />
            </svg>
            <?php echo LANG['nav_settings']; ?>
          </a>
          <ul class="dropdown-menu" style="min-width: 1em;">
            <li><a id="listen_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'listen_settings' ? ' active' : ''; ?> d-flex align-items-center" href="listen_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#megaphone-fill" />
                </svg>
                <?php echo LANG['nav_notification']; ?>
              </a></li>
            <li><a id="audiostream_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'audiostream_settings' ? ' active' : ''; ?> d-flex align-items-center" href="audiostream_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#soundwave" />
                </svg>
                <?php echo LANG['nav_audio']; ?>
              </a></li>
            <?php if (USES_CAMERA) { ?>
              <li><a id="videostream_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'videostream_settings' ? ' active' : ''; ?> d-flex align-items-center" href="videostream_settings.php">
                  <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                    <use href="media/bootstrap-icons.svg#film" />
                  </svg>
                  <?php echo LANG['nav_video']; ?>
                </a></li>
            <?php } ?>
            <li><a id="network_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'network_settings' ? ' active' : ''; ?> d-flex align-items-center" href="network_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#wifi" />
                </svg>
                <?php echo LANG['nav_network']; ?>
              </a></li>
            <li><a id="system_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'system_settings' ? ' active' : ''; ?> d-flex align-items-center" href="system_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#wrench" />
                </svg>
                <?php echo LANG['nav_system']; ?>
              </a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center disabled" href="#" data-bs-toggle="dropdown">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#server" />
            </svg>
            <?php echo LANG['nav_server']; ?>
          </a>
          <ul class="dropdown-menu" style="min-width: 1em;">
            <li>
              <div class="pe-2 dropdown-item d-flex align-items-center">
                <div class="d-flex align-items-center" role="button" onclick="$('#ap_mode_switch').click();">
                  <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                    <use href="media/bootstrap-icons.svg#broadcast-pin" />
                  </svg>
                  <?php echo LANG['nav_access_point']; ?>
                </div>
                <div class="form-check form-switch my-0 ms-2">
                  <input id="ap_mode_switch" class="form-check-input" type="checkbox" role="button" <?php echo ACCESS_POINT_ACTIVE ? 'checked' : ''; ?>>
                </div>
              </div>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a id="logout_nav_link" class="dropdown-item d-flex align-items-center" href="logout.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#door-open" />
                </svg>
                <?php echo LANG['nav_sign_out']; ?>
              </a></li>
            <li><a id="reboot_nav_link" class="dropdown-item d-flex align-items-center" href="reboot.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#arrow-counterclockwise" />
                </svg>
                <?php echo LANG['nav_reboot']; ?>
              </a></li>
            <li><a id="shutdown_nav_link" class="dropdown-item d-flex align-items-center" href="shutdown.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#power" />
                </svg>
                <?php echo LANG['nav_shutdown']; ?>
              </a></li>
            <li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a id="server_status_nav_link" class="dropdown-item<?php echo LOCATION == 'server_status' ? ' active' : ''; ?> d-flex align-items-center" href="server_status.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#activity" />
                </svg>
                <?php echo LANG['nav_server_status']; ?>
              </a></li>
            <li><a id="server_debugging_nav_link" class="dropdown-item<?php echo LOCATION == 'debugging' ? ' active' : ''; ?> d-flex align-items-center" href="debugging.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#bug" />
                </svg>
                <?php echo LANG['nav_debugging']; ?>
              </a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center disabled" href="#" data-bs-toggle="dropdown">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#chat-text" />
            </svg>
            <?php echo LANG['nav_language']; ?>
          </a>
          <ul class="dropdown-menu" style="min-width: 1em;">
            <li>
              <a id="language_en_nav_link" class="dropdown-item<?php echo LANGUAGE == 'en' ? ' active"' : '" href="' . URL_WITHOUT_SEARCH . '?lang=en"'; ?>">
                <span class="me-2 flag-icon flag-icon-gb" style="height: 1.1em; width: 1.1em;"></span>
                English
              </a>
            </li>
            <li>
              <a id="language_no_nav_link" class="dropdown-item<?php echo LANGUAGE == 'no' ? ' active"' : '" href="' . URL_WITHOUT_SEARCH . '?lang=no"'; ?>">
                <span class="me-2 flag-icon flag-icon-no" style="height: 1.1em; width: 1.1em;"></span>
                Norsk
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center disabled" href="#" data-bs-toggle="dropdown">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use href="media/bootstrap-icons.svg#question-circle" />
            </svg>
            <?php echo LANG['nav_help']; ?>
          </a>
          <ul class="dropdown-menu" style="min-width: 1em;">
            <li><a id="help_docs_nav_link" class="dropdown-item<?php echo LOCATION == 'documentation' ? ' active' : ''; ?> d-flex align-items-center" href="documentation.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#book" />
                </svg>
                <?php echo LANG['nav_docs']; ?>
              </a></li>
            <li>
              <<?php echo ACCESS_POINT_ACTIVE ? 'button' : 'a'; ?> id="help_github_nav_link" class="dropdown-item d-flex align-items-center" href="https://github.com/lars-frogner/OpenBabyMonitor" <?php echo ACCESS_POINT_ACTIVE ? ' disabled' : ''; ?>>
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use href="media/bootstrap-icons.svg#github" />
                </svg>
                GitHub
              </<?php echo ACCESS_POINT_ACTIVE ? 'button' : 'a'; ?>>
            </li>
          </ul>
        </li>
        <?php if ($_MEASURE_TEMPERATURE) { ?>
          <li id="temperature_nav_item" class="nav-item d-flex align-items-center text-bm">
            <div class="d-flex align-items-center py-2">
              <svg class="bi me-1" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                <use href="media/bootstrap-icons.svg#motherboard" />
              </svg>
              <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                <use href="media/bootstrap-icons.svg#thermometer-half" />
              </svg>
              <div id="temperature_label"></div>
            </div>
          </li>
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>

<script>
  const ANY_KNOWN_NETWORKS = <?php echo anyKnownNetworks($_DATABASE) ? 'true' : 'false'; ?>;
</script>
