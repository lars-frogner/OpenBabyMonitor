<?php
require_once(dirname(__DIR__) . '/config/path_config.php');
require_once(SRC_DIR . '/session.php');
require_once(SRC_DIR . '/control.php');

abortIfSessionExpired();

if (isset($_POST)) {
  foreach ($_POST as $name => $value) {
    executeSettingOfEnvVar($name, $value);
  }
}
