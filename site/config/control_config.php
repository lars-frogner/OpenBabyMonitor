<?php
require_once(__DIR__ . '/config.php');

$_MODES_INFO = $_CONFIG['modes'];
define('STANDBY_MODE', $_MODES_INFO['standby']['value']);
define('LISTEN_MODE', $_MODES_INFO['listen']['value']);
define('AUDIOSTREAM_MODE', $_MODES_INFO['audiostream']['value']);
define('VIDEOSTREAM_MODE', $_MODES_INFO['videostream']['value']);

define('MODE_VALUES', array('standby' => STANDBY_MODE, 'listen' => LISTEN_MODE, 'audiostream' => AUDIOSTREAM_MODE, 'videostream' => VIDEOSTREAM_MODE));

$_MODE_START_COMMANDS = array();
$_MODE_STOP_COMMANDS = array();
$_MODE_RESTART_COMMANDS = array();
$_MODE_WAIT_FOR_FILE_PATHS = array();
foreach (MODE_VALUES as $mode => $value) {
  $_MODE_START_COMMANDS[$value] = $_MODES_INFO[$mode]['start_command'];
  $_MODE_STOP_COMMANDS[$value] = $_MODES_INFO[$mode]['stop_command'];
  $_MODE_RESTART_COMMANDS[$value] = $_MODES_INFO[$mode]['restart_command'];
  $wait_for_file_entry = $_MODES_INFO[$mode]['wait_for_file'];
  if (!is_null($wait_for_file_entry) && getenv($wait_for_file_entry)) {
    $wait_for_file_entry = getenv($wait_for_file_entry);
  }
  $_MODE_WAIT_FOR_FILE_PATHS[$value] = $wait_for_file_entry;
}
define('MODE_START_COMMANDS', $_MODE_START_COMMANDS);
define('MODE_STOP_COMMANDS', $_MODE_STOP_COMMANDS);
define('MODE_RESTART_COMMANDS', $_MODE_RESTART_COMMANDS);
define('MODE_WAIT_FOR_FILE_PATHS', $_MODE_WAIT_FOR_FILE_PATHS);

define('MODE_SWITCH_OK', 0);

$_CONTROL_INFO = $_CONFIG['control'];
define('MODE_QUERY_INTERVAL', intval($_CONTROL_INFO['mode_query_interval'] * 1e6)); // In microseconds
define('MODE_SWITCH_TIMEOUT', intval($_CONTROL_INFO['mode_switch_timeout'] * 1e6));

define('SERVER_ACTION_COMMANDS', $_CONTROL_INFO['server_actions']['commands']);

define('SERVER_LOCK_COMMANDS', $_CONTROL_INFO['lock_commands']);
