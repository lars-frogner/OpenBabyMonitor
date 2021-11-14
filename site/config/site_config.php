<?php
include_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/env_config.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/network_config.php');
require_once(__DIR__ . '/database_config.php');
require_once(__DIR__ . '/control_config.php');
require_once(__DIR__ . '/monitoring_config.php');
require_once(SRC_DIR . '/security.php');
require_once(SRC_DIR . '/session.php');
require_once(SRC_DIR . '/database.php');
require_once(SRC_DIR . '/control.php');
require_once(__DIR__ . '/language_config.php');

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
  case 'system_settings.php':
    define('LOCATION', 'system_settings');
    break;
  case 'server_status.php':
    define('LOCATION', 'server_status');
    break;
  case 'debugging.php':
    define('LOCATION', 'debugging');
    break;
  default:
    define('LOCATION', 'login');
    break;
}

if (isset($_COOKIE['color_scheme'])) {
  define('COLOR_SCHEME', $_COOKIE['color_scheme']);
} else {
  define('COLOR_SCHEME', 'light');
}
