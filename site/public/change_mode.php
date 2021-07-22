<?php
require_once('../config/site_config.php');
redirectIfLoggedOut('index.php');

if (isset($_POST['requested_mode'])) {
  $new_mode = intval($_POST['requested_mode']);
  echo switchMode($_DATABASE, $new_mode);
}
