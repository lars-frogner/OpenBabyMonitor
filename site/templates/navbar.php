<nav id="navbar" class="navbar navbar-expand-lg">
  <div class="container-fluid">
    <a id="title_nav_link" class="navbar-brand" href="main.php"><?php echo LANG['nav_title']; ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar_entries">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbar_entries">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a id="modes_nav_link" class="nav-link<?php echo LOCATION == 'main' ? ' active' : ''; ?> d-flex align-items-center disabled" href="main.php">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#menu-button" />
            </svg>
            <?php echo LANG['nav_modes']; ?>
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center disabled" href="#" data-bs-toggle="dropdown">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#gear" />
            </svg>
            <?php echo LANG['nav_settings']; ?>
          </a>
          <ul class="dropdown-menu" style="min-width: 1em">
            <li><a id="listen_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'listen_settings' ? ' active' : ''; ?> d-flex align-items-center" href="listen_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#megaphone" />
                </svg>
                <?php echo LANG['nav_notification']; ?>
              </a></li>
            <li><a id="audiostream_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'audiostream_settings' ? ' active' : ''; ?> d-flex align-items-center" href="audiostream_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#soundwave" />
                </svg>
                <?php echo LANG['nav_audio']; ?>
              </a></li>
            <li><a id="videostream_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'videostream_settings' ? ' active' : ''; ?> d-flex align-items-center" href="videostream_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#film" />
                </svg>
                <?php echo LANG['nav_video']; ?>
              </a></li>
            <li><a id="server_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'server_settings' ? ' active' : ''; ?> d-flex align-items-center" href="server_settings.php">
                <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
                  <use xlink:href="media/bootstrap-icons.svg#wifi" />
                </svg>
                <?php echo LANG['nav_network']; ?>
              </a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center disabled" href="#" data-bs-toggle="dropdown">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#chat-text" />
            </svg>
            <?php echo LANG['nav_language']; ?>
          </a>
          <ul class="dropdown-menu" style="min-width: 1em">
            <li>
              <a id="language_no_nav_link" class="dropdown-item<?php echo LANGUAGE == 'no' ? ' active"' : '" href="' . URL_WITHOUT_SEARCH . '?lang=no"'; ?>">
                <span class="me-2 flag-icon flag-icon-no" style="height: 1.1em; width: 1.1em;"></span>
                Norsk
              </a>
            </li>
            <li>
              <a id="language_en_nav_link" class="dropdown-item<?php echo LANGUAGE == 'en' ? ' active"' : '" href="' . URL_WITHOUT_SEARCH . '?lang=en"'; ?>">
                <span class="me-2 flag-icon flag-icon-gb" style="height: 1.1em; width: 1.1em;"></span>
                English
              </a>
            </li>
          </ul>
        </li>
        <li class="nav-item">
          <a id="logout_nav_link" class="nav-link d-flex align-items-center disabled" href="logout.php">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#door-open" />
            </svg>
            <?php echo LANG['nav_sign_out']; ?>
          </a>
        </li>
        <li class="nav-item">
          <a id="reboot_nav_link" class="nav-link d-flex align-items-center disabled" href="reboot.php">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#arrow-counterclockwise" />
            </svg>
            <?php echo LANG['nav_reboot']; ?>
          </a>
        </li>
        <li class="nav-item">
          <a id="shutdown_nav_link" class="nav-link d-flex align-items-center disabled" href="shutdown.php">
            <svg class="bi me-2" style="height: 1.1em; width: 1.1em;" fill="currentColor">
              <use xlink:href="media/bootstrap-icons.svg#power" />
            </svg>
            <?php echo LANG['nav_shutdown']; ?>
          </a>
        </li>
        <?php
        if (false && !ACCESS_POINT_ACTIVE) {
        ?><li class="nav-item">
            <a id="ap_mode_nav_link" class="nav-link disabled" href="activate_ap_mode.php"><?php echo LANG['nav_ap_mode']; ?></a>
          </li>
        <?php }
        if (false && !CONNECTED_TO_EXTERNAL_NETWORK) {
        ?><li class="nav-item">
            <a id="client_mode_nav_link" class="nav-link disabled" href="activate_client_mode.php"><?php echo LANG['nav_client_mode']; ?></a>
          </li>
        <?php } ?>
      </ul>
    </div>
  </div>
</nav>
