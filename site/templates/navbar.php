<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand">Babymonitor</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
      aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
      <div class="navbar-nav">
        <a class="nav-link<?php echo (LOCATION == 'main' ? ' active" aria_current="page' : '') ?>" href="main.php">Moduser</a>
        <a class="nav-link<?php echo (LOCATION == 'settings' ? ' active" aria_current="page': '') ?>" href="settings.php">Innstillinger</a>
        <a class="nav-link" href="logout.php">Logg ut</a>
      </div>
    </div>
  </div>
</nav>
