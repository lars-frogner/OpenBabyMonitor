<?php
require_once(__DIR__ . '/path_config.php');
require_once(__DIR__ . '/env_config.php');
require_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/database_config.php');
require_once(SRC_DIR . '/database.php');

$_MEASURE_TEMPERATURE;
$_WARN_UNDER_VOLTAGE;
$_WARN_OVERHEAT;
$_VITALS_QUERY_INTERVAL;
function readMonitoringSettings() {
  global $_DATABASE, $_MEASURE_TEMPERATURE, $_WARN_UNDER_VOLTAGE, $_WARN_OVERHEAT, $_VITALS_QUERY_INTERVAL;
  $_MEASURE_TEMPERATURE = readValuesFromTable($_DATABASE, 'system_settings', 'measure_temperature', true);
  $_WARN_UNDER_VOLTAGE = readValuesFromTable($_DATABASE, 'system_settings', 'warn_under_voltage', true);
  $_WARN_OVERHEAT = readValuesFromTable($_DATABASE, 'system_settings', 'warn_overheat', true);
  $_VITALS_QUERY_INTERVAL = readValuesFromTable($_DATABASE, 'system_settings', 'query_interval', true);
}
readMonitoringSettings();

define('PING_INTERVAL', 10);
define('MODE_QUERY_INTERVAL_MICROSEC', 0.5 * 1e6);

define('UNDER_VOLTAGE_FLAG', 1);
define('FREQUENCY_CAPPED_FLAG', 2);
define('CURRENTLY_THROTTLED_FLAG', 4);
