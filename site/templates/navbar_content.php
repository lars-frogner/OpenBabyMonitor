<div class="container-fluid">
  <a class="navbar-brand">Babymonitor</a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar_entries">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbar_entries">
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item">
        <a id="modes_nav_link" class="nav-link<?php echo LOCATION == 'main' ? ' active' : '' ?> disabled" href="main.php">Moduser</a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle disabled" href="#" data-bs-toggle="dropdown">Innstillinger</a>
        <ul class="dropdown-menu">
          <li><a id="listen_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'listen_settings' ? ' active' : '' ?>" href="listen_settings.php">Varsel</a></li>
          <li><a id="audiostream_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'audiostream_settings' ? ' active' : '' ?>" href="audiostream_settings.php">Lyd</a></li>
          <li><a id="videostream_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'videostream_settings' ? ' active' : '' ?>" href="videostream_settings.php">Video</a></li>
          <li><a id="server_settings_nav_link" class="dropdown-item<?php echo LOCATION == 'server_settings' ? ' active' : '' ?>" href="server_settings.php">Nettverk</a></li>
        </ul>
      </li>
      <li class="nav-item">
        <a id="logout_nav_link" class="nav-link disabled" href="logout.php">Logg ut</a>
      </li>
      <li class="nav-item">
        <a id="reboot_nav_link" class="nav-link disabled" href="reboot.php">Restart</a>
      </li>
      <li class="nav-item">
        <a id="shutdown_nav_link" class="nav-link disabled" href="shutdown.php">Slå av</a>
      </li>
      <?php
      if (!ACCESS_POINT_ACTIVE) {
      ?><li class="nav-item">
          <a id="ap_mode_nav_link" class="nav-link disabled" href="activate_ap_mode.php">Aktiver tilgangspunkt</a>
        </li>
      <?php }
      if (!CONNECTED_TO_EXTERNAL_NETWORK) {
      ?><li class="nav-item">
          <a id="client_mode_nav_link" class="nav-link disabled" href="activate_client_mode.php">Koble til nettverk</a>
        </li>
      <?php } ?>
    </ul>
  </div>
</div>
