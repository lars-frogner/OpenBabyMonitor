<?php
require_once('config.php');

$_MODES_INFO = $_CONFIG['modes'];
define('STANDBY_MODE', $_MODES_INFO['standby']['value']);
define('LISTEN_MODE', $_MODES_INFO['listen']['value']);
define('AUDIOSTREAM_MODE', $_MODES_INFO['audiostream']['value']);
define('VIDEOSTREAM_MODE', $_MODES_INFO['videostream']['value']);

define('MODE_VALUES', array('standby' => STANDBY_MODE, 'listen' => LISTEN_MODE, 'audiostream' => AUDIOSTREAM_MODE, 'videostream' => VIDEOSTREAM_MODE));

$_MODE_START_COMMANDS = array();
$_MODE_STOP_COMMANDS = array();
$_MODE_RESTART_COMMANDS = array();
foreach (MODE_VALUES as $mode => $value) {
  $_MODE_START_COMMANDS[$value] = $_MODES_INFO[$mode]['start_command'];
  $_MODE_STOP_COMMANDS[$value] = $_MODES_INFO[$mode]['stop_command'];
  $_MODE_RESTART_COMMANDS[$value] = $_MODES_INFO[$mode]['restart_command'];
}

define('MODE_QUERY_INTERVAL', intval($_CONFIG['control']['mode_query_interval'] * 1e6)); // In microseconds
define('MODE_SWITCH_TIMEOUT', intval($_CONFIG['control']['mode_switch_timeout'] * 1e6));

define('MODE_SWITCH_OK', 0);
