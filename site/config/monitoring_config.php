<?php
require_once(__DIR__ . '/path_config.php');
require_once(__DIR__ . '/env_config.php');
require_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/database_config.php');
require_once(SRC_DIR . '/database.php');

define('MEASURE_TEMPERATURE', readValuesFromTable($_DATABASE, 'system_settings', 'measure_temperature', true));
define('WARN_UNDER_VOLTAGE', readValuesFromTable($_DATABASE, 'system_settings', 'warn_under_voltage', true));
define('WARN_OVERHEAT', readValuesFromTable($_DATABASE, 'system_settings', 'warn_overheat', true));

define('VITALS_QUERY_INTERVAL', readValuesFromTable($_DATABASE, 'system_settings', 'query_interval', true));

define('PING_INTERVAL', 10);
define('MODE_QUERY_INTERVAL_MICROSEC', 0.5 * 1e6);

define('UNDER_VOLTAGE_FLAG', 1);
define('FREQUENCY_CAPPED_FLAG', 2);
define('CURRENTLY_THROTTLED_FLAG', 4);
