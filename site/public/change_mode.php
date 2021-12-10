<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(dirname(__DIR__) . '/config/database_config.php');
require_once(SRC_DIR . '/session.php');
require_once(SRC_DIR . '/mode.php');

abortIfSessionExpired();

if (isset($_POST['requested_mode'])) {
  $new_mode = intval($_POST['requested_mode']);
  echo switchMode($_DATABASE, $new_mode);
}

if (isset($_POST['wait_for_mode_stream'])) {
  $mode = intval($_POST['wait_for_mode_stream']);
  echo waitForModeStream($mode);
}
