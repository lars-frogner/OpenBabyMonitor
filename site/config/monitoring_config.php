<?php
require_once(__DIR__ . '/path_config.php');
require_once(__DIR__ . '/env_config.php');
require_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/database_config.php');
require_once(SRC_DIR . '/database.php');

define('MEASURE_TEMPERATURE', readValuesFromTable($_DATABASE, 'system_settings', 'measure_temperature', true));
define('MONITORING_QUERY_INTERVAL', readValuesFromTable($_DATABASE, 'system_settings', 'query_interval', true));

define('UNDER_VOLTAGE_FLAG', 1);
define('FREQUENCY_CAPPED_FLAG', 2);
define('CURRENTLY_THROTTLED_FLAG', 4);
define('SOFT_TEMP_LIM_ACTIVE_FLAG', 8);
