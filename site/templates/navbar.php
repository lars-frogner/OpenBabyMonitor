<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand">Babymonitor</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar_entries">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbar_entries">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?php echo LOCATION == 'main' ? ' active' : '' ?>" href="main.php">Moduser</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" id="settings_dropdown">Innstillinger</a>
          <ul class="dropdown-menu" aria-labelledby="settings_dropdown">
            <li><a class="dropdown-item<?php echo LOCATION == 'listen_settings' ? ' active' : '' ?>" href="listen_settings.php">Listen</a></li>
            <li><a class="dropdown-item<?php echo LOCATION == 'audiostream_settings' ? ' active' : '' ?>" href="audiostream_settings.php">Audio</a></li>
            <li><a class="dropdown-item<?php echo LOCATION == 'videostream_settings' ? ' active' : '' ?>" href="videostream_settings.php">Video</a></li>
            <li><a class="dropdown-item<?php echo LOCATION == 'server_settings' ? ' active' : '' ?>" href="server_settings.php">Server</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Logg ut</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
