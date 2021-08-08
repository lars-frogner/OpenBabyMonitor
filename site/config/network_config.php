<?php
require_once(__DIR__ . '/error_config.php');
require_once(__DIR__ . '/path_config.php');
require_once(__DIR__ . '/env_config.php');
require_once(__DIR__ . '/config.php');

$output = null;
$result_code = null;
exec(SERVERCONTROL_DIR . '/get_wireless_operating_mode.sh', $output, $result_code);
if ($result_code != 0) {
  bm_error("Check for wireless operating mode failed with error code $result_code:\n" . join("\n", $output));
}

$_NETWORK_INFO = $_CONFIG['network'];

define('NETWORK_QUERY_INTERVAL', intval($_NETWORK_INFO['network_query_interval'] * 1e6)); // In microseconds
define('NETWORK_SWITCH_TIMEOUT', intval($_NETWORK_INFO['network_switch_timeout'] * 1e6));

define('ACCESS_POINT_ACTIVE', $output[0] == $_NETWORK_INFO['wireless_modes']['access_point']);
define('CONNECTED_TO_EXTERNAL_NETWORK', $output[0] == $_NETWORK_INFO['wireless_modes']['client']);

define('GET_CONNECTED_NETWORK_SSID_SCRIPT', getenv('BM_SERVERCONTROL_DIR') . '/' . $_NETWORK_INFO['connected_network_ssid_script_filename']);

define('SERVER_IP', trim(`hostname -I`));
