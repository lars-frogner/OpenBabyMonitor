<?php
include_once('error_config.php');
require_once('config.php');
require_once('database_config.php');
require_once('mode_config.php');
require_once(SRC_PATH . '/security.php');
require_once(SRC_PATH . '/session.php');
require_once(SRC_PATH . '/control.php');

switch (basename($_SERVER['SCRIPT_NAME'])) {
  case 'main.php':
    define('LOCATION', 'main');
    break;
  case 'settings.php':
    define('LOCATION', 'settings');
    break;
  default:
    define('LOCATION', 'login');
    break;
}
