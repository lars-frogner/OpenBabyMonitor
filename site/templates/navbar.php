<!--
Uses the following variables if set:
$logout_text, $reboot_text, $shutdown_text, $ap_text, $client_text
-->
<?php
require_once(TEMPLATES_PATH . '/utils.php');
echo render(TEMPLATES_PATH . '/confirmation_modal.php', array('id' => 'logout_modal', 'href' => 'logout.php', 'title' => 'Er du sikker på at du vil logge ut?', 'static_text' => '', 'dynamic_text' => (isset($logout_text) ? $logout_text : ''), 'confirm' => 'Logg ut', 'cancel' => 'Avbryt'));
echo render(TEMPLATES_PATH . '/confirmation_modal.php', array('id' => 'reboot_modal', 'href' => 'reboot.php', 'title' => 'Er du sikker på at du vil restarte enheten?', 'static_text' => '', 'dynamic_text' => (isset($reboot_text) ? $reboot_text : ''), 'confirm' => 'Restart', 'cancel' => 'Avbryt'));
echo render(TEMPLATES_PATH . '/confirmation_modal.php', array('id' => 'shutdown_modal', 'href' => 'shutdown.php', 'title' => 'Er du sikker på at du vil slå av enheten?', 'tstatic_ext' => '', 'dynamic_text' => (isset($shutdown_text) ? $shutdown_text : ''), 'confirm' => 'Slå av', 'cancel' => 'Avbryt'));
if (!ACCESS_POINT_ACTIVE) {
  echo render(TEMPLATES_PATH . '/confirmation_modal.php', array('id' => 'ap_mode_modal', 'href' => 'activate_ap_mode.php', 'title' => 'Er du sikker på at du vil opprette et trådløst tilgangspunkt på enheten?', 'static_text' => 'Du er nå koblet til via et eksternt trådløst nettverk. Enheten vil koble seg av nettverket og opprette sitt eget tilgangspunkt. Du vil logges ut og midlertidig miste forbindelsen med enheten. Enheten vil ikke lenger ha tilgang til internett.', 'dynamic_text' => (isset($ap_text) ? $ap_text : ''), 'confirm' => 'Opprett', 'cancel' => 'Avbryt'));
}
if (!CONNECTED_TO_EXTERNAL_NETWORK) {
  echo render(TEMPLATES_PATH . '/confirmation_modal.php', array('id' => 'client_mode_modal', 'href' => 'activate_client_mode.php', 'title' => 'Er du sikker på at du vil koble enheten til et eksternt trådløst nettverk?', 'static_text' => 'Du er nå koblet til via enhetens tilgangspunkt, som vil deaktiveres. Du vil logges ut og midlertidig miste forbindelsen med enheten.', 'dynamic_text' => (isset($client_text) ? $client_text : ''), 'confirm' => 'Koble til', 'cancel' => 'Avbryt'));
}
?>

<script>
  const LOGOUT_MODAL_ID = 'logout_modal';
  const REBOOT_MODAL_ID = 'reboot_modal';
  const SHUTDOWN_MODAL_ID = 'shutdown_modal';
  const AP_MODAL_ID = 'ap_mode_modal';
  const CLIENT_MODAL_ID = 'client_mode_modal';
</script>

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
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Innstillinger</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item<?php echo LOCATION == 'listen_settings' ? ' active' : '' ?>" href="listen_settings.php">Listen</a></li>
            <li><a class="dropdown-item<?php echo LOCATION == 'audiostream_settings' ? ' active' : '' ?>" href="audiostream_settings.php">Audio</a></li>
            <li><a class="dropdown-item<?php echo LOCATION == 'videostream_settings' ? ' active' : '' ?>" href="videostream_settings.php">Video</a></li>
            <li><a class="dropdown-item<?php echo LOCATION == 'server_settings' ? ' active' : '' ?>" href="server_settings.php">Server</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a id="logout_nav_link" class="nav-link" data-bs-toggle="modal" data-bs-target="#logout_modal" href="#logout_modal">Logg ut</a>
        </li>
        <li class="nav-item">
          <a id="reboot_nav_link" class="nav-link" data-bs-toggle="modal" data-bs-target="#reboot_modal" href="#reboot_modal">Restart</a>
        </li>
        <li class="nav-item">
          <a id="shutdown_nav_link" class="nav-link" data-bs-toggle="modal" data-bs-target="#shutdown_modal" href="#shutdown_modal">Slå av</a>
        </li>
        <?php
        if (!ACCESS_POINT_ACTIVE) {
        ?><li class="nav-item">
            <a id="ap_mode_nav_link" class="nav-link" data-bs-toggle="modal" data-bs-target="#ap_mode_modal" href="#ap_mode_modal">Aktiver tilgangspunkt</a>
          </li><?php
              }
              if (!CONNECTED_TO_EXTERNAL_NETWORK) {
                ?>
          <li class="nav-item">
            <a id="client_mode_nav_link" class="nav-link" data-bs-toggle="modal" data-bs-target="#client_mode_modal" href="#client_mode_modal">Koble til nettverk</a>
          </li><?php
              } ?>
      </ul>
    </div>
  </div>
</nav>

<script>
  const LOGOUT_NAV_LINK_ID = 'logout_nav_link';
  const REBOOT_NAV_LINK_ID = 'reboot_nav_link';
  const SHUTDOWN_NAV_LINK_ID = 'shutdown_nav_link';
  const AP_NAV_LINK_ID = 'ap_mode_nav_link';
  const CLIENT_NAV_LINK_ID = 'client_mode_nav_link';
</script>
