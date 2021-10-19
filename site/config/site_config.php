<?php
include_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/env_config.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/network_config.php');
require_once(__DIR__ . '/database_config.php');
require_once(__DIR__ . '/control_config.php');
require_once(SRC_DIR . '/security.php');
require_once(SRC_DIR . '/session.php');
require_once(SRC_DIR . '/database.php');
require_once(SRC_DIR . '/control.php');

switch (basename($_SERVER['SCRIPT_NAME'])) {
  case 'main.php':
    define('LOCATION', 'main');
    break;
  case 'listen_settings.php':
    define('LOCATION', 'listen_settings');
    break;
  case 'audiostream_settings.php':
    define('LOCATION', 'audiostream_settings');
    break;
  case 'videostream_settings.php':
    define('LOCATION', 'videostream_settings');
    break;
  case 'server_settings.php':
    define('LOCATION', 'server_settings');
    break;
  default:
    define('LOCATION', 'login');
    break;
}
