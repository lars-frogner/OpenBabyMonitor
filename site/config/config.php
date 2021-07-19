<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('TEMPLATES_PATH', realpath(dirname(__FILE__) . '/../templates'));

define('SRC_PATH', realpath(dirname(__FILE__) . '/../src'));

define('DATABASE_HOST', 'localhost');
define('DATABASE_USER', 'babymonitor');
define('DATABASE_PASSWORD', '');
define('DATABASE_NAME', 'babymonitor');

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

require_once(SRC_PATH . '/database.php');
require_once(SRC_PATH . '/session.php');

$_DATABASE = connectToDatabase(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
